<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NormalizeSearch
{
    /**
     * Handle an incoming request.
     * Redirect legacy query ?q=...&lokasi=... to slug-style:
     * - both q + lokasi -> /cari/{kataSlug}/{lokasiSlug}
     * - q only           -> /cari/{kataSlug}
     * - lokasi only      -> /cari/lokasi/{lokasiSlug}
     *
     * Only act on the legacy route path '/cari' (no segments).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only handle exact /cari path (no segments). This avoids interfering with
        // slug routes like /cari/abc or /cari/lokasi/xyz.
        if (trim($request->path(), '/') === 'cari') {
            $q = (string) $request->query('q', '');
            $lokasi = (string) $request->query('lokasi', '');

            // If either q or lokasi present, build appropriate slug target
            if ($q !== '' || $lokasi !== '') {
                $kataSlug = $q ? Str::slug($q, '-') : null;
                $lokasiSlug = $lokasi ? Str::slug($lokasi, '-') : null;

                if (!$kataSlug && $lokasiSlug) {
                    // lokasi-only -> /cari/lokasi/{lokasiSlug}
                    $target = url('/cari/lokasi/' . $lokasiSlug);
                } elseif ($kataSlug) {
                    // q present (with or without lokasi) -> /cari/{kataSlug}/{lokasiSlug?}
                    $target = url('/cari/' . $kataSlug . ($lokasiSlug ? '/' . $lokasiSlug : ''));
                } else {
                    $target = url('/cari');
                }

                // Redirect if current URL differs from target
                if (rtrim($request->url(), '/') !== rtrim($target, '/')) {
                    return redirect()->to($target, 301);
                }
            }
        }

        return $next($request);
    }
}

