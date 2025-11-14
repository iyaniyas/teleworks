<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\SearchLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    /**
     * Halaman pencarian /cari
     * Query params: q, lokasi, kategori (abaikan jika tidak ada kolom), wfh, page
     */
    public function index(Request $request)
    {
        // ambil input (raw — tidak di-lowercase untuk keperluan pencarian DB)
        $qRaw      = trim($request->query('q', ''));
        $lokasiRaw = trim($request->query('lokasi', ''));
        $wfh       = $request->boolean('wfh');
        $perPage   = 15;

        // lakukan pencarian (mengembalikan LengthAwarePaginator)
        $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage);

        // optional: logging pencarian (aman jika SearchLog ada)
        try {
            if (class_exists(SearchLog::class)) {
                SearchLog::create([
                    'q'             => Str::limit($qRaw, 255),
                    'filters'       => json_encode([
                        'lokasi' => $lokasiRaw,
                        'wfh'    => $wfh ? 1 : 0,
                    ]),
                    'results_count' => method_exists($jobs, 'total') ? $jobs->total() : 0,
                    'ip'            => $request->ip(),
                    'user_agent'    => substr($request->header('User-Agent', ''), 0, 1000),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error: ' . $e->getMessage());
        }

        // Untuk tampilan, kita tampilkan q & lokasi dalam lowercase agar konsisten di UI,
        // tetapi DB search tetap menggunakan $qRaw / $lokasiRaw.
        return view('cari', [
            'jobs'      => $jobs,
            'q'         => mb_strtolower($qRaw),
            'lokasi'    => mb_strtolower($lokasiRaw),
            'qRaw'      => $qRaw,      // sediakan juga raw jika view butuh
            'lokasiRaw' => $lokasiRaw, // sediakan juga raw jika view butuh
            'wfh'       => $wfh ? '1' : '0',
        ]);
    }

    /**
     * SEO-friendly slug route: /cari/{kata}/{lokasi?}
     * kata & lokasi are slugs (hyphen-separated). Convert back to space-separated terms.
     */
/**
 * Route handler for /cari/lokasi/{lokasi}
 * Treats the incoming slug as a location only (no keyword).
 */
public function slugLocation($lokasi, Request $request = null)
{
    // lokasi slug -> readable lokasi text
    $lokasiRaw = trim(str_replace('-', ' ', $lokasi));
    $qRaw = ''; // no keyword
    $wfh = $request ? $request->boolean('wfh') : false;
    $perPage = 15;

    // perform search with lokasi only
    $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage);

    // ensure pagination links keep the location-only base path
    if (method_exists($jobs, 'withPath')) {
        $jobs->withPath(url('/cari/lokasi/' . $lokasi));
    }

    // optional logging
    try {
        if (class_exists(\App\Models\SearchLog::class)) {
            \App\Models\SearchLog::create([
                'q' => '',
                'filters' => json_encode(['lokasi' => $lokasiRaw, 'wfh' => $wfh ? 1 : 0]),
                'results_count' => method_exists($jobs, 'total') ? $jobs->total() : 0,
                'ip' => request()->ip(),
                'user_agent' => substr(request()->header('User-Agent', ''), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    } catch (\Throwable $e) {
        \Log::warning('SearchLog error (slugLocation): ' . $e->getMessage());
    }

    // return view (same keys as other methods)
    return view('cari', [
        'jobs' => $jobs,
        'q' => '', // lowercase keyword for UI (empty)
        'lokasi' => mb_strtolower($lokasiRaw),
        'qRaw' => '',
        'lokasiRaw' => $lokasiRaw,
        'wfh' => $wfh ? '1' : '0',
    ]);
}


    public function slug($kata, $lokasi = null, Request $request = null)
    {
        // konversi slug -> istilah pencarian
        $qRaw = trim(str_replace('-', ' ', $kata));
        $lokasiRaw = $lokasi ? trim(str_replace('-', ' ', $lokasi)) : '';

        $wfh = $request ? $request->boolean('wfh') : false;
        $perPage = 15;

        // lakukan pencarian
        $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage);

        // Pastikan pagination links menggunakan path /cari/{kata}/{lokasi?}
        if (method_exists($jobs, 'withPath')) {
            $basePath = url('/cari/' . $kata . ($lokasi ? '/' . $lokasi : ''));
            $jobs->withPath($basePath);
        }

        // logging (opsional, mencerminkan pencarian via slug)
        try {
            if (class_exists(SearchLog::class)) {
                SearchLog::create([
                    'q'             => Str::limit($qRaw, 255),
                    'filters'       => json_encode([
                        'lokasi' => $lokasiRaw,
                        'wfh'    => $wfh ? 1 : 0,
                    ]),
                    'results_count' => method_exists($jobs, 'total') ? $jobs->total() : 0,
                    'ip'            => request()->ip(),
                    'user_agent'    => substr(request()->header('User-Agent', ''), 0, 1000),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error (slug): ' . $e->getMessage());
        }

        // Prepare display variants: lowercase for UI consistency (as in index)
        return view('cari', [
            'jobs'      => $jobs,
            'q'         => mb_strtolower($qRaw),
            'lokasi'    => mb_strtolower($lokasiRaw),
            'qRaw'      => $qRaw,
            'lokasiRaw' => $lokasiRaw,
            'wfh'       => $wfh ? '1' : '0',
        ]);
    }

    /**
     * Centralized search implementation used by both index() and slug()
     *
     * @param string $qRaw
     * @param string $lokasiRaw
     * @param bool $wfh
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
     */
    protected function performSearch($qRaw, $lokasiRaw, $wfh = false, $perPage = 15)
    {
        // --- Build base query (tampilkan hanya published & belum expired) ---
        $query = Job::query()
            ->where('status', 'published')
            ->where(function($qb) {
                $qb->whereNull('expires_at')
                   ->orWhere('expires_at', '>', now());
            });

        // pencarian menggunakan apa yang diketik user (case depends on DB collation)
        if ($qRaw !== '') {
            $query->where(function ($qq) use ($qRaw) {
                $qq->where('title', 'like', "%{$qRaw}%")
                   ->orWhere('company', 'like', "%{$qRaw}%")
                   ->orWhere('description', 'like', "%{$qRaw}%");
            });
        }

        // filter lokasi (pakai raw)
        if ($lokasiRaw !== '') {
            $query->where(function($qq) use ($lokasiRaw) {
                $qq->where('location', 'like', "%{$lokasiRaw}%")
                   ->orWhere('job_location', 'like', "%{$lokasiRaw}%");
            });
        }

        // filter WFH: periksa kedua kolom is_wfh atau is_remote
        if ($wfh) {
            $query->where(function($qq) {
                $qq->where('is_wfh', 1)
                   ->orWhere('is_remote', 1);
            });
        }

        // urutan: prioritaskan date_posted jika ada, fallback created_at
        // Note: if date_posted might be NULL, ordering by DESC places NULLS last in many DBs
        $query->orderByDesc('date_posted')->orderByDesc('created_at');

        // paginate
        return $query->paginate($perPage);
    }
}

