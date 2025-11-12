<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\SearchLog;
use Illuminate\Support\Str;

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
        $query->orderByDesc('date_posted')->orderByDesc('created_at');

        // paginate
        $jobs = $query->paginate($perPage)->withQueryString();

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
            \Log::warning('SearchLog error: ' . $e->getMessage());
        }

        // Untuk tampilan, kita tampilkan q & lokasi dalam lowercase agar konsisten di UI,
        // tetapi DB search tetap menggunakan $qRaw / $lokasiRaw.
        return view('cari', [
            'jobs'     => $jobs,
            'q'        => mb_strtolower($qRaw),
            'lokasi'   => mb_strtolower($lokasiRaw),
            'qRaw'     => $qRaw,      // sediakan juga raw jika view butuh
            'lokasiRaw'=> $lokasiRaw, // sediakan juga raw jika view butuh
            'wfh'      => $wfh ? '1' : '0',
        ]);
    }
}

