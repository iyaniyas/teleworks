<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\SearchLog; // opsional: aman jika tidak ada
use Illuminate\Support\Str;
use Carbon\Carbon;

class SearchController extends Controller
{
    /**
     * Halaman pencarian /cari
     * Query params: q, lokasi, kategori (abaikan jika tidak ada kolom), wfh, page
     */
    public function index(Request $request)
    {
        // ambil input
        $q        = trim($request->query('q', ''));
        $lokasi   = trim($request->query('lokasi', ''));
        $wfh      = $request->boolean('wfh'); // true jika ?wfh=1
        $perPage  = 15;
        $page     = max(1, (int) $request->query('page', 1));

        // --- Build base query (tampilkan hanya published & belum expired) ---
        $query = Job::query()
            ->where('status', 'published')
            ->where(function($qb) {
                $qb->whereNull('expires_at')
                   ->orWhere('expires_at', '>', now());
            });

        // pencarian sederhana (LIKE) di title/company/description
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                   ->orWhere('company', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // filter lokasi (jika diberikan)
        if ($lokasi !== '') {
            $query->where(function($qq) use ($lokasi) {
                $qq->where('location', 'like', "%{$lokasi}%")
                   ->orWhere('job_location', 'like', "%{$lokasi}%");
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

        // paginate (tidak di-cache agar selalu fresh selama debugging)
        $jobs = $query->paginate($perPage)->withQueryString();

        // optional: logging pencarian (aman jika model tidak ada)
        try {
            if (class_exists(SearchLog::class)) {
                SearchLog::create([
                    'q'             => Str::limit($q, 255),
                    'filters'       => json_encode([
                        'lokasi' => $lokasi,
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

        // render view
        return view('cari', [
            'jobs'     => $jobs,
            'q'        => $q,
            'lokasi'   => $lokasi,
            'wfh'      => $wfh ? '1' : '0',
        ]);
    }
}

