<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\SearchLog; // opsional: kalau belum ada, abaikan atau hapus blok logging
use Illuminate\Support\Facades\Cache;
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

        // cache key per kombinasi parameter & halaman
        $cacheKey = 'search:' . md5("q:$q|lok:$lokasi|wfh:".($wfh?1:0)."|page:" . $request->query('page', 1));

        $jobs = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($q, $lokasi, $wfh, $perPage) {

            $query = Job::query()
                // status aktif (tabel kamu ada kolom `status`)
               // ->where('status', 1)
                // jangan tampilkan yang kadaluarsa
                ->where(function($qb) {
                    $qb->whereNull('expires_at')
                       ->orWhere('expires_at', '>', Carbon::now());
                });

            // pencarian sederhana (LIKE) di title/company/description
            if ($q !== '') {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                       ->orWhere('company', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%");
                });
            }

            // filter lokasi
            if ($lokasi !== '') {
                $query->where('location', 'like', "%{$lokasi}%");
            }

            // filter WFH (kolom kamu: is_wfh)
           // if ($wfh) {
           //     $query->where('is_wfh', 1);
           // }

            // urutan terbaru
            $query->orderBy('created_at', 'desc');

            return $query->paginate($perPage)->withQueryString();
        });

        // logging pencarian (opsional; aman jika tabel/model SearchLog ada)
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

        return view('cari', [
            'jobs'     => $jobs,
            'q'        => $q,
            'lokasi'   => $lokasi,
            'wfh'      => $wfh ? '1' : '0',
        ]);
    }
}

