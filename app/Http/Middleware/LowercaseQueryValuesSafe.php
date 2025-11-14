<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LowercaseQueryValuesSafe
{
    /**
     * Recursively lowercase all string values in array.
     */
    protected function lowercaseRecursive($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->lowercaseRecursive($v);
            }
            return $out;
        }
        return mb_strtolower((string) $value, 'UTF-8');
    }

    /**
     * Recursively normalize values to string for comparison.
     */
    protected function stringifyRecursive($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $this->stringifyRecursive($v);
            }
            return $out;
        }
        return (string) $value;
    }

    /**
     * Compare two arrays recursively for equality.
     */
    protected function arraysEqual($a, $b)
    {
        return $this->stringifyRecursive($a) === $this->stringifyRecursive($b);
    }

    public function handle(Request $request, Closure $next)
    {
        // only apply to GET requests (search form), skip AJAX and console
        if (!$request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return $next($request);
        }

        $original = $request->query(); // array of key => value or []

        if (empty($original)) {
            return $next($request);
        }

        $normalized = [];
        foreach ($original as $k => $v) {
            $normalized[$k] = $this->lowercaseRecursive($v);
        }

        // if arraysEqual -> nothing changed -> continue
        if ($this->arraysEqual($original, $normalized)) {
            return $next($request);
        }

        // build stable query string from normalized array
        $newQuery = http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);

        $base = $request->url();
        $newUrl = $base . ($newQuery ? ('?' . $newQuery) : '');

        // 301 redirect only when different
        return redirect()->to($newUrl, 301);
    }
}

