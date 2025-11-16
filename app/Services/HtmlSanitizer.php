<?php

namespace App\Services;

class HtmlSanitizer
{
    /**
     * Allowed tags and attributes
     * - Will strip scripts, iframes, forms, onclick/on* attributes, styles (optional)
     */
    protected array $allowedTags = [
        'p' => [''],
        'br' => [''],
        'ul' => [''],
        'ol' => [''],
        'li' => [''],
        'strong' => [''],
        'b' => [''],
        'em' => [''],
        'i' => [''],
        'a' => ['href', 'title', 'rel', 'target'],
        'h1' => [''],
        'h2' => [''],
        'h3' => [''],
        'h4' => [''],
        'h5' => [''],
        'h6' => [''],
        'blockquote' => [''],
        'code' => [''],
        'pre' => [''],
    ];

    /**
     * Sanitize raw HTML and return sanitized HTML string.
     *
     * @param string|null $html
     * @return string|null
     */
    public function sanitize(?string $html): ?string
    {
        if (empty($html)) return null;

        // Normalize
        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        // Suppress warnings from malformed HTML
        $loaded = $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        if (!$loaded) {
            // fallback: strip tags with allowed list using strip_tags
            $allowed = implode('', array_keys($this->allowedTags));
            $stripped = strip_tags($html, $allowed);
            return $this->stripOnAttributes($stripped);
        }

        $this->removeNodesByTag($doc, ['script', 'iframe', 'style', 'form', 'noscript', 'meta', 'link', 'input', 'button']);
        $this->sanitizeAttributes($doc);

        // Get body innerHTML
        $body = $doc->getElementsByTagName('body')->item(0);
        $inner = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $inner .= $doc->saveHTML($child);
            }
        } else {
            $inner = $doc->saveHTML();
        }

        // Final cleanup - remove javascript: links etc
        $inner = preg_replace('/href=["\']\s*javascript:[^"\']*["\']/i', 'href="#"', $inner);

        // Trim
        return trim($inner);
    }

    protected function removeNodesByTag(\DOMDocument $doc, array $tags)
    {
        foreach ($tags as $tag) {
            $elements = $doc->getElementsByTagName($tag);
            // iterate backward because NodeList is live
            for ($i = $elements->length - 1; $i >= 0; $i--) {
                $el = $elements->item($i);
                if ($el) {
                    $el->parentNode->removeChild($el);
                }
            }
        }
    }

    protected function sanitizeAttributes(\DOMDocument $doc)
    {
        $xpath = new \DOMXPath($doc);
        // find all elements
        $nodes = $xpath->query('//*');

        foreach ($nodes as $node) {
            $tag = strtolower($node->nodeName);
            // remove nodes not in allowedTags (but keep their children)
            if (!array_key_exists($tag, $this->allowedTags)) {
                // unwrap element: replace node with its children
                $this->unwrapNode($node);
                continue;
            }

            // allowed attributes
            $allowedAttrs = $this->allowedTags[$tag] ?? [];

            // remove disallowed attrs and dangerous ones
            if ($node->hasAttributes()) {
                $attrs = [];
                foreach ($node->attributes as $attr) {
                    $attrs[$attr->name] = $attr->value;
                }
                // remove all and re-add allowed ones
                foreach (iterator_to_array($node->attributes) as $a) {
                    $node->removeAttribute($a->name);
                }
                foreach ($attrs as $name => $value) {
                    $lower = strtolower($name);
                    // drop event handlers and style attributes
                    if (str_starts_with($lower, 'on') || $lower === 'style') {
                        continue;
                    }
                    if (in_array($lower, $allowedAttrs, true)) {
                        // for href ensure no javascript:
                        if ($lower === 'href' && preg_match('/^\s*javascript:/i', $value)) {
                            continue;
                        }
                        $node->setAttribute($name, $value);
                    }
                }
            }
        }
    }

    protected function unwrapNode(\DOMNode $node)
    {
        $parent = $node->parentNode;
        if (!$parent) return;
        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }
        $parent->removeChild($node);
    }

    /**
     * Fallback simple attribute stripper for raw HTML if DOM parsing fails.
     */
    protected function stripOnAttributes(string $html): string
    {
        // remove on* attributes
        $html = preg_replace('/(<[a-zA-Z0-9]+)([^>]*)(on[a-zA-Z]+\s*=\s*["\'][^"\']*["\'])/i', '$1$2', $html);
        // remove javascript: in href
        $html = preg_replace('/href=["\']\s*javascript:[^"\']*["\']/i', 'href="#"', $html);
        return $html;
    }
}

