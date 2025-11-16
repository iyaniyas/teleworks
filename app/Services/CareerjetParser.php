<?php

namespace App\Services;

class CareerjetParser
{
    /**
     * Return inner HTML of a DOMNode.
     */
    public static function innerHtml(\DOMNode $node): string
    {
        $doc = $node->ownerDocument;
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }
        return $html;
    }

    /**
     * Sanitize HTML: allow limited tags and remove scripts/styles.
     * Allowed tags: p, br, b, strong, i, em, ul, ol, li, a
     */
    public static function sanitizeHtml(string $html): string
    {
        // remove script/style
        $html = preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);

        // allowlist
        $allowed = '<p><br><b><strong><i><em><ul><ol><li><a>';

        // use strip_tags with allowed and then tidy some whitespace
        $clean = strip_tags($html, $allowed);

        // clean attributes on <a> only allow href + target rel
        $clean = preg_replace_callback('#<a\s+([^>]+?)>#i', function($m) {
            $attrs = $m[1];
            $href = '';
            if (preg_match('/href\s*=\s*([\'"])(.*?)\\1/i', $attrs, $h)) {
                $href = $h[2];
                // sanitize javascript: or data:
                if (preg_match('#^\s*(javascript:|data:)#i', $href)) {
                    $href = '#';
                }
                $href = htmlspecialchars($href, ENT_QUOTES|ENT_SUBSTITUTE);
                return '<a href="'.$href.'" rel="nofollow noopener" target="_blank">';
            }
            return '<a href="#" rel="nofollow noopener" target="_blank">';
        }, $clean);

        // Remove any leftover attributes on other tags
        $clean = preg_replace('#<(\/?)(\w+)[^>]*>#', '<$1$2>', $clean);

        // Trim
        $clean = trim($clean);
        return $clean;
    }

    /**
     * Extract description from HTML body: look for section/div with class contains 'content'
     * or fallback to first big <section> or <article> or the body text.
     *
     * Returns sanitized HTML string.
     */
    public static function extractDescription(string $html): string
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $loaded = $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($doc);

        // candidates: section[contains(@class,'content')], div[contains(@class,'content')], article
        $queries = [
            "//section[contains(@class,'content')]",
            "//div[contains(@class,'content')]",
            "//article[contains(@class,'content')]",
            "//section",
            "//article",
            "//div[contains(@class,'job-description') or contains(@id,'job') or contains(@class,'description')]",
        ];

        foreach ($queries as $q) {
            $nodes = $xpath->query($q);
            if ($nodes && $nodes->length) {
                // pick the longest node innerHTML
                $best = null;
                $bestLen = 0;
                foreach ($nodes as $n) {
                    $inner = self::innerHtml($n);
                    $len = mb_strlen(trim(strip_tags($inner)));
                    if ($len > $bestLen) {
                        $bestLen = $len;
                        $best = $inner;
                    }
                }
                if ($best && $bestLen > 10) {
                    return self::sanitizeHtml($best);
                }
            }
        }

        // fallback: try main paragraphs
        $paras = $xpath->query('//p');
        if ($paras && $paras->length) {
            $collected = '';
            foreach ($paras as $p) {
                $collected .= '<p>' . trim($p->textContent) . '</p>';
            }
            if (mb_strlen(strip_tags($collected)) > 20) {
                return self::sanitizeHtml($collected);
            }
        }

        // last fallback: strip tags from entire body
        $text = strip_tags($html);
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        // split into paragraphs
        $chunks = preg_split('/\n{2,}/', $text);
        $out = '';
        foreach ($chunks as $c) {
            $c = trim($c);
            if ($c !== '') $out .= '<p>' . htmlspecialchars($c, ENT_QUOTES|ENT_SUBSTITUTE) . '</p>';
            if (mb_strlen(strip_tags($out)) > 4000) break;
        }
        return self::sanitizeHtml($out);
    }

    /**
     * Normalize applicant location requirements array or string.
     * Return JSON-serializable array, default ['Indonesia'].
     */
    public static function normalizeApplicantReq($raw): array
    {
        if (empty($raw)) return ['Indonesia'];
        if (is_string($raw)) {
            $decoded = @json_decode($raw, true);
            if (is_array($decoded)) $raw = $decoded;
            else $raw = array_filter(array_map('trim', preg_split('/[,;|\/]+/', $raw)));
        }
        if (!is_array($raw)) $raw = [$raw];
        $map = [
            'ID'=>'Indonesia','IDN'=>'Indonesia','IND'=>'Indonesia',
            'ID'=>'Indonesia','IN'=>'India','US'=>'United States','UK'=>'United Kingdom','GB'=>'United Kingdom'
        ];
        $out = [];
        foreach ($raw as $r) {
            $rTrim = trim((string)$r);
            if ($rTrim === '') continue;
            $u = strtoupper($rTrim);
            if (isset($map[$u])) $out[] = $map[$u];
            else $out[] = $rTrim;
        }
        if (empty($out)) $out = ['Indonesia'];
        return array_values(array_unique($out));
    }

    /**
     * Normalize salary: if we cannot parse numeric, return base string default
     */
    public static function normalizeSalary($jobArray): array
    {
        // prefer explicit fields
        $min = $jobArray['salary_min'] ?? null;
        $max = $jobArray['salary_max'] ?? null;
        $currency = $jobArray['salary_currency_code'] ?? ($jobArray['base_salary_currency'] ?? 'IDR');
        $unit = 'MONTH';
        if (!empty($jobArray['salary_type'])) {
            $t = strtoupper($jobArray['salary_type']);
            $map = ['Y'=>'YEAR','M'=>'MONTH','W'=>'WEEK','D'=>'DAY','H'=>'HOUR'];
            if (isset($map[$t])) $unit = $map[$t];
        }

        if ($min || $max) {
            return [
                'base_salary_min' => $min ? (float)$min : null,
                'base_salary_max' => $max ? (float)$max : null,
                'base_salary_currency' => $currency,
                'base_salary_unit' => $unit,
                'base_salary_string' => ($min || $max) ? trim(($min ? $min : '') . ($min && $max ? ' - ' : '') . ($max ? $max : '')) : null,
            ];
        }

        // fallback: Careerjet returns excerpt 'salary' string maybe empty
        $s = $jobArray['salary'] ?? '';
        if (trim($s) !== '') {
            return [
                'base_salary_min' => null,
                'base_salary_max' => null,
                'base_salary_currency' => $currency,
                'base_salary_unit' => $unit,
                'base_salary_string' => $s,
            ];
        }

        // default estimate
        return [
            'base_salary_min' => null,
            'base_salary_max' => null,
            'base_salary_currency' => 'IDR',
            'base_salary_unit' => 'MONTH',
            'base_salary_string' => 'Perkiraan gaji: 10.000.000',
        ];
    }
}

