<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\SearchLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\CareerjetClient;
use App\Services\CareerjetParser;
use Carbon\Carbon;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $qRaw      = trim($request->query('q', ''));
        $lokasiRaw = trim($request->query('lokasi', ''));
        $wfh       = $request->boolean('wfh');
        $perPage   = 15;

        $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);

        // generic fallback note: jika DB kosong tapi fallback mengembalikan hasil,
        // beri keterangan generik tanpa menyebut sumber eksternal.
        $fallback_note = null;
        try {
            $dbTotal = Job::where('status', 'published')
                ->where(function ($qb) {
                    $qb->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->when($qRaw !== '', function ($q) use ($qRaw) {
                    $q->where(function ($qq) use ($qRaw) {
                        $qq->where('title', 'like', "%{$qRaw}%")
                           ->orWhere('company', 'like', "%{$qRaw}%")
                           ->orWhere('description', 'like', "%{$qRaw}%");
                    });
                })
                ->when($lokasiRaw !== '', function ($q) use ($lokasiRaw) {
                    $q->where(function ($qq) use ($lokasiRaw) {
                        $qq->where('location', 'like', "%{$lokasiRaw}%")
                           ->orWhere('job_location', 'like', "%{$lokasiRaw}%");
                    });
                })
                ->count();

            $fallbackHasResults = (method_exists($jobs,'total') ? (int)$jobs->total() : (is_countable($jobs) ? count($jobs) : 0)) > 0;

            if ($dbTotal === 0 && $fallbackHasResults) {
                // set generic note (don't mention Careerjet)
                if (!empty($qRaw) && !empty($lokasiRaw)) {
                    $fallback_note = sprintf('Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di %s. Berikut hasil dari lokasi lain:', $qRaw, $lokasiRaw);
                } elseif (!empty($qRaw)) {
                    $fallback_note = sprintf('Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di database. Berikut hasil di lokasi lain:', $qRaw);
                } elseif (!empty($lokasiRaw)) {
                    $fallback_note = sprintf('Hasil dari sumber lain karena tidak ditemukan lowongan di %s. Berikut hasil dari lokasi lain:', $lokasiRaw);
                } else {
                    $fallback_note = 'Hasil dari sumber lain karena tidak ditemukan di database.';
                }
            }
        } catch (\Throwable $e) {
            Log::debug('fallback_note check failed: ' . $e->getMessage());
        }

        try {
            if (class_exists(SearchLog::class)) {
                SearchLog::create([
                    'q' => Str::limit($qRaw, 255),
                    'filters' => json_encode([
                        'lokasi' => $lokasiRaw,
                        'wfh'    => $wfh ? 1 : 0,
                    ]),
                    'results_count' => method_exists($jobs, 'total')
                        ? $jobs->total()
                        : (is_countable($jobs) ? count($jobs) : 0),
                    'ip'         => $request->ip(),
                    'user_agent' => substr($request->header('User-Agent', ''), 0, 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error: ' . $e->getMessage());
        }

        return view('cari', [
            'jobs'      => $jobs,
            'q'         => mb_strtolower($qRaw),
            'lokasi'    => mb_strtolower($lokasiRaw),
            'qRaw'      => $qRaw,
            'lokasiRaw' => $lokasiRaw,
            'wfh'       => $wfh ? '1' : '0',
            'fallback_note' => $fallback_note,
        ]);
    }

    public function slugLocation($lokasi, Request $request = null)
    {
        $lokasiRaw = trim(str_replace('-', ' ', $lokasi));
        $qRaw = '';
        $wfh = $request ? $request->boolean('wfh') : false;
        $perPage = 15;

        $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);

        // same generic fallback note logic for slugLocation
        $fallback_note = null;
        try {
            $dbTotal = Job::where('status', 'published')
                ->where(function ($qb) {
                    $qb->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->when($lokasiRaw !== '', function ($q) use ($lokasiRaw) {
                    $q->where(function ($qq) use ($lokasiRaw) {
                        $qq->where('location', 'like', "%{$lokasiRaw}%")
                           ->orWhere('job_location', 'like', "%{$lokasiRaw}%");
                    });
                })
                ->count();

            $fallbackHasResults = (method_exists($jobs,'total') ? (int)$jobs->total() : (is_countable($jobs) ? count($jobs) : 0)) > 0;

            if ($dbTotal === 0 && $fallbackHasResults) {
                $fallback_note = sprintf('Hasil dari sumber lain karena tidak ditemukan lowongan di %s. Berikut hasil dari lokasi lain:', $lokasiRaw);
            }
        } catch (\Throwable $e) {
            Log::debug('fallback_note check failed (slugLocation): ' . $e->getMessage());
        }

        if (method_exists($jobs, 'withPath')) {
            $jobs->withPath(url('/cari/lokasi/' . $lokasi));
        }

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
            Log::warning('SearchLog error (slugLocation): ' . $e->getMessage());
        }

        return view('cari', [
            'jobs' => $jobs,
            'q' => '',
            'lokasi' => mb_strtolower($lokasiRaw),
            'qRaw' => '',
            'lokasiRaw' => $lokasiRaw,
            'wfh' => $wfh ? '1' : '0',
            'fallback_note' => $fallback_note,
        ]);
    }

    public function slug($kata, $lokasi = null, Request $request = null)
    {
        $qRaw = trim(str_replace('-', ' ', $kata));
        $lokasiRaw = $lokasi ? trim(str_replace('-', ' ', $lokasi)) : '';
        $wfh = $request ? $request->boolean('wfh') : false;
        $perPage = 15;

        $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);

        // generic fallback note
        $fallback_note = null;
        try {
            $dbTotal = Job::where('status', 'published')
                ->where(function ($qb) {
                    $qb->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->when($qRaw !== '', function ($q) use ($qRaw) {
                    $q->where(function ($qq) use ($qRaw) {
                        $qq->where('title', 'like', "%{$qRaw}%")
                           ->orWhere('company', 'like', "%{$qRaw}%")
                           ->orWhere('description', 'like', "%{$qRaw}%");
                    });
                })
                ->when($lokasiRaw !== '', function ($q) use ($lokasiRaw) {
                    $q->where(function ($qq) use ($lokasiRaw) {
                        $qq->where('location', 'like', "%{$lokasiRaw}%")
                           ->orWhere('job_location', 'like', "%{$lokasiRaw}%");
                    });
                })
                ->count();

            $fallbackHasResults = (method_exists($jobs,'total') ? (int)$jobs->total() : (is_countable($jobs) ? count($jobs) : 0)) > 0;

            if ($dbTotal === 0 && $fallbackHasResults) {
                if (!empty($qRaw) && !empty($lokasiRaw)) {
                    $fallback_note = sprintf('Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di %s. Berikut hasil dari lokasi lain:', $qRaw, $lokasiRaw);
                } elseif (!empty($qRaw)) {
                    $fallback_note = sprintf('Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di database. Berikut hasil di lokasi lain:', $qRaw);
                } elseif (!empty($lokasiRaw)) {
                    $fallback_note = sprintf('Hasil dari sumber lain karena tidak ditemukan lowongan di %s. Berikut hasil dari lokasi lain:', $lokasiRaw);
                } else {
                    $fallback_note = 'Hasil dari sumber lain karena tidak ditemukan di database.';
                }
            }
        } catch (\Throwable $e) {
            Log::debug('fallback_note check failed (slug): ' . $e->getMessage());
        }

        if (method_exists($jobs, 'withPath')) {
            $base = url('/cari/' . $kata . ($lokasi ? '/' . $lokasi : ''));
            $jobs->withPath($base);
        }

        try {
            if (class_exists(SearchLog::class)) {
                SearchLog::create([
                    'q' => Str::limit($qRaw, 255),
                    'filters' => json_encode([
                        'lokasi' => $lokasiRaw,
                        'wfh'    => $wfh ? 1 : 0,
                    ]),
                    'results_count' => method_exists($jobs, 'total') ? $jobs->total() : 0,
                    'ip' => request()->ip(),
                    'user_agent' => substr(request()->header('User-Agent', ''), 0, 1000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error (slug): ' . $e->getMessage());
        }

        return view('cari', [
            'jobs'      => $jobs,
            'q'         => mb_strtolower($qRaw),
            'lokasi'    => mb_strtolower($lokasiRaw),
            'qRaw'      => $qRaw,
            'lokasiRaw' => $lokasiRaw,
            'wfh'       => $wfh ? '1' : '0',
            'fallback_note' => $fallback_note,
        ]);
    }

    // performSearch and fallbackCareerjet... (same as previous, unchanged)
    protected function performSearch($qRaw, $lokasiRaw, $wfh = false, $perPage = 15, Request $request = null)
    {
        $request = $request ?: request();
        $page = max(1, (int) $request->query('page', 1));

        $query = Job::query()
            ->where('status', 'published')
            ->where(function ($qb) {
                $qb->whereNull('expires_at')
                   ->orWhere('expires_at', '>', now());
            });

        if ($qRaw !== '') {
            $query->where(function ($qq) use ($qRaw) {
                $qq->where('title', 'like', "%{$qRaw}%")
                   ->orWhere('company', 'like', "%{$qRaw}%")
                   ->orWhere('description', 'like', "%{$qRaw}%");
            });
        }

        if ($lokasiRaw !== '') {
            $query->where(function ($qq) use ($lokasiRaw) {
                $qq->where('location', 'like', "%{$lokasiRaw}%")
                   ->orWhere('job_location', 'like', "%{$lokasiRaw}%");
            });
        }

        if ($wfh) {
            $query->where(function ($qq) {
                $qq->where('is_wfh', 1)
                   ->orWhere('is_remote', 1);
            });
        }

        $query->orderByDesc('date_posted')->orderByDesc('created_at');

        $dbPaginator = $query->paginate($perPage);

        $dbTotal = method_exists($dbPaginator, 'total') ? (int)$dbPaginator->total() : $dbPaginator->count();

        if ($dbTotal > 0) {
            return $dbPaginator;
        }

        return $this->fallbackCareerjetSearchWithRetries($qRaw, $lokasiRaw, $page, $perPage, $request);
    }

    protected function fallbackCareerjetSearchWithRetries(string $q, string $lokasi, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        $tries = [$lokasi, '', 'Indonesia'];
        foreach ($tries as $tryLok) {
            $p = $this->fallbackCareerjetSearchSingle($q, $tryLok, $page, $perPage, $request);
            $total = method_exists($p, 'total') ? (int)$p->total() : (is_countable($p) ? count($p) : 0);
            if ($total > 0) return $p;
        }

        return new LengthAwarePaginator(collect([]), 0, $perPage, $page, ['path'=>$request->url(), 'query'=>$request->query()]);
    }

    protected function fallbackCareerjetSearchSingle(string $q, string $lokasi, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        $cacheKey = 'careerjet_search:' . md5("q={$q}|lokasi={$lokasi}|page={$page}|per={$perPage}");
        $cacheStore = Cache::store('redis');

        Log::debug('fallbackCareerjetSearch called', ['q'=>$q, 'lokasi'=>$lokasi, 'page'=>$page, 'per'=>$perPage]);

        $cached = $cacheStore->get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            $rawItems = $cached['items'] ?? [];
            $total = (int) ($cached['total'] ?? count($rawItems));
            $items = collect($rawItems)->map(function ($it) {
                $title = $it['title'] ?? ($it['job_title'] ?? 'No title');
                $company = $it['company'] ?? null;
                $location = $it['location'] ?? null;
                $applyUrl = $it['apply_url'] ?? ($it['url'] ?? null);
                $datePosted = null;
                if (!empty($it['date_posted'])) {
                    try { $datePosted = Carbon::parse($it['date_posted']); } catch (\Throwable $e) { $datePosted = null; }
                }
                $obj = (object)[
                    'title'=>$title,'company'=>$company,'location'=>$location,'apply_url'=>$applyUrl,
                    'url'=>$applyUrl,'description'=>$it['description'] ?? null,'date_posted'=>$datePosted,
                    'source'=>$it['source'] ?? 'careerjet','raw'=>$it['raw'] ?? null
                ];
                $obj->is_external = true;
                return $obj;
            });
            return new LengthAwarePaginator($items, $total, $perPage, $page, ['path'=>$request->url(), 'query'=>$request->query()]);
        }

        $client = new CareerjetClient();
        $params = [
            'keywords' => $q ?: '',
            'location' => $lokasi ?: '',
            'page' => $page,
            'page_size' => $perPage,
            'user_ip' => $request->ip() ?: '127.0.0.1',
            'user_agent' => $request->header('User-Agent', 'TeleworksBot/1.0'),
            'locale_code' => 'id_ID',
            'sort' => 'date',
        ];

        $resp = $client->query($params, config('app.url'), $params['user_agent']);

        if (!is_array($resp) || (isset($resp['__error']) && $resp['__error'])) {
            Log::warning("Careerjet fallback API ERROR", ['resp' => $resp, 'params'=>$params]);
            return new LengthAwarePaginator(collect([]), 0, $perPage, $page, ['path'=>$request->url(), 'query'=>$request->query()]);
        }

        $jobsRaw = $resp['jobs'] ?? [];
        $totalHits = is_numeric($resp['hits'] ?? null) ? (int)$resp['hits'] : count($jobsRaw);

        $items = collect();
        foreach ($jobsRaw as $j) {
            $title = trim($j['title'] ?? 'No title');
            $company = $j['company'] ?? ($j['site'] ?? null);
            $location = $j['locations'] ?? ($j['location'] ?? null);
            $applyUrl = $j['apply_url'] ?? ($j['url'] ?? null);
            $dateRaw = $j['date'] ?? null;
            $desc = '';
            if (!empty($j['description'])) {
                $desc = CareerjetParser::sanitizeHtml($j['description']);
            } elseif (!empty($j['description_excerpt'])) {
                $desc = CareerjetParser::sanitizeHtml('<p>' . htmlentities($j['description_excerpt']) . '</p>');
            }
            $datePosted = null;
            if (!empty($dateRaw)) {
                try { $datePosted = Carbon::parse($dateRaw); } catch (\Throwable $e) { $datePosted = null; }
            }
            $obj = (object)[
                'title'=>$title,'company'=>$company,'location'=>$location,'apply_url'=>$applyUrl,
                'url'=>$applyUrl,'description'=>$desc,'date_posted'=>$datePosted,
                'source'=>'careerjet','raw'=>$j,'is_external'=>true
            ];
            $items->push($obj);
        }

        $paginator = new LengthAwarePaginator($items, $totalHits, $perPage, $page, ['path'=>$request->url(), 'query'=>$request->query()]);

        if ($items->count() > 0) {
            try {
                $cachePayload = [
                    'items' => $items->map(function ($it) {
                        return [
                            'title' => $it->title,
                            'company' => $it->company,
                            'location' => $it->location,
                            'apply_url' => $it->apply_url,
                            'url' => $it->url,
                            'description' => $it->description,
                            'date_posted' => $it->date_posted ? $it->date_posted->toDateTimeString() : null,
                            'source' => $it->source,
                            'raw' => $it->raw,
                        ];
                    })->toArray(),
                    'total' => $totalHits,
                ];
                Cache::store('redis')->put($cacheKey, $cachePayload, now()->addMinutes(30));
                Log::debug("Careerjet fallback: cached key {$cacheKey}");
            } catch (\Throwable $e) {
                Log::warning("Careerjet fallback: FAILED caching {$cacheKey} : " . $e->getMessage());
            }
        } else {
            Log::debug("Careerjet fallback: empty result for params", ['params'=>$params]);
        }

        return $paginator;
    }
}

