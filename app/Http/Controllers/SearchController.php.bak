<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\SearchLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
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

        $dbTotal = $this->countDbMatches($qRaw, $lokasiRaw);
        $fallbackHasResults = (method_exists($jobs,'total') ? (int)$jobs->total() : (is_countable($jobs) ? count($jobs) : 0)) > 0;
        $external_rendered = ($dbTotal === 0 && $fallbackHasResults);

        $fallback_note = null;
        try {
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
            Log::debug('fallback_note check failed: ' . $e->getMessage());
        }

        // Attempt to save search log — but block-save if profanity detected.
        try {
            if (class_exists(SearchLog::class)) {
                $hasProfanity = $this->containsProfanity($qRaw) || $this->containsProfanity($lokasiRaw);

                if ($hasProfanity) {
                    // Block saving the raw search. Log the event (no raw words saved).
                    Log::info('Blocked profane search (not saved)', [
                        'q_present' => $qRaw !== '' ? true : false,
                        'lokasi_present' => $lokasiRaw !== '' ? true : false,
                        'ip' => $request->ip(),
                        'ua' => substr($request->header('User-Agent', ''), 0, 255),
                    ]);
                } else {
                    SearchLog::create([
                        'q' => Str::limit($qRaw, 255),
                        'params' => json_encode([
                            'lokasi' => $lokasiRaw,
                            'wfh'    => $wfh ? 1 : 0,
                        ]),
                        'result_count' => method_exists($jobs, 'total')
                            ? (int) $jobs->total()
                            : (is_countable($jobs) ? count($jobs) : 0),
                        'user_ip' => $request->ip(),
                        'user_agent' => substr($request->header('User-Agent', ''), 0, 255),
                        // timestamps handled by Eloquent
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error: ' . $e->getMessage());
        }

        return view('cari', [
            'jobs'             => $jobs,
            'q'                => mb_strtolower($qRaw),
            'lokasi'           => mb_strtolower($lokasiRaw),
            'qRaw'             => $qRaw,
            'lokasiRaw'        => $lokasiRaw,
            'wfh'              => $wfh ? '1' : '0',
            'fallback_note'    => $fallback_note,
            'external_rendered' => $external_rendered,
        ]);
    }

    /**
     * /cari/lokasi/{lokasi}
     */
    public function slugLocation($lokasi, Request $request = null)
    {
        $lokasiRaw = trim(str_replace('-', ' ', $lokasi));
        $qRaw = '';
        $wfh = $request ? $request->boolean('wfh') : false;
        $perPage = 15;

        // Build cleaned slug for lokasi (map profanity => jakarta)
        $cleanLokSlug = $this->cleanAndSlug($lokasiRaw, 'lokasi');
        $origLokParam = Str::slug($lokasi, '-');

        if ($cleanLokSlug !== $origLokParam) {
            // redirect to cleaned /cari URL (map profanity -> jakarta)
            $target = $cleanLokSlug ? url('/cari/lokasi/' . $cleanLokSlug) : url('/cari');
            return redirect()->to($target)->setStatusCode(302);
        }

        $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);

        $dbTotal = $this->countDbMatches($qRaw, $lokasiRaw);
        $fallbackHasResults = (method_exists($jobs,'total') ? (int)$jobs->total() : (is_countable($jobs) ? count($jobs) : 0)) > 0;
        $external_rendered = ($dbTotal === 0 && $fallbackHasResults);

        $fallback_note = null;
        try {
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
                $hasProfanity = $this->containsProfanity($lokasiRaw);

                if ($hasProfanity) {
                    Log::info('Blocked profane search (slugLocation not saved)', [
                        'lokasi_present' => $lokasiRaw !== '' ? true : false,
                        'ip' => request()->ip(),
                        'ua' => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                } else {
                    \App\Models\SearchLog::create([
                        'q' => '',
                        'params' => json_encode(['lokasi' => $lokasiRaw, 'wfh' => $wfh ? 1 : 0]),
                        'result_count' => method_exists($jobs, 'total') ? (int)$jobs->total() : 0,
                        'user_ip' => request()->ip(),
                        'user_agent' => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                }
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
            'external_rendered' => $external_rendered,
        ]);
    }

    /**
     * /cari/{kata}/{lokasi?}
     * If kata or lokasi contain profanity, redirect to cleaned /cari URL.
     */
    public function slug($kata, $lokasi = null, Request $request = null)
    {
        $qRaw = trim(str_replace('-', ' ', $kata));
        $lokasiRaw = $lokasi ? trim(str_replace('-', ' ', $lokasi)) : '';
        $wfh = $request ? $request->boolean('wfh') : false;
        $perPage = 15;

        // Clean and build slugs — profanity mapping:
        // q -> sales, lokasi -> jakarta when profanity detected
        $cleanQSlug = $this->cleanAndSlug($qRaw, 'q');
        $cleanLokSlug = $this->cleanAndSlug($lokasiRaw, 'lokasi');

        $origQSlug = Str::slug($kata, '-');
        $origLokParam = $lokasi ? Str::slug($lokasi, '-') : null;

        // If original slug differs from cleaned slug, redirect to cleaned /cari URL
        if ($cleanQSlug !== $origQSlug || ($lokasi && $cleanLokSlug !== $origLokParam)) {
            if ($cleanQSlug && $cleanLokSlug) {
                $target = url('/cari/' . $cleanQSlug . '/' . $cleanLokSlug);
            } elseif ($cleanQSlug) {
                $target = url('/cari/' . $cleanQSlug);
            } elseif ($cleanLokSlug) {
                $target = url('/cari/lokasi/' . $cleanLokSlug);
            } else {
                $target = url('/cari');
            }
            return redirect()->to($target)->setStatusCode(302);
        }

        $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);

        $dbTotal = $this->countDbMatches($qRaw, $lokasiRaw);
        $fallbackHasResults = (method_exists($jobs,'total') ? (int)$jobs->total() : (is_countable($jobs) ? count($jobs) : 0)) > 0;
        $external_rendered = ($dbTotal === 0 && $fallbackHasResults);

        $fallback_note = null;
        try {
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
            // use original route params (they are already clean here due to redirect above if needed)
            $base = url('/cari/' . $kata . ($lokasi ? '/' . $lokasi : ''));
            $jobs->withPath($base);
        }

        try {
            if (class_exists(SearchLog::class)) {
                $hasProfanity = $this->containsProfanity($qRaw) || $this->containsProfanity($lokasiRaw);

                if ($hasProfanity) {
                    Log::info('Blocked profane search (slug not saved)', [
                        'q_present' => $qRaw !== '' ? true : false,
                        'lokasi_present' => $lokasiRaw !== '' ? true : false,
                        'ip' => request()->ip(),
                        'ua' => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                } else {
                    SearchLog::create([
                        'q' => Str::limit($qRaw, 255),
                        'params' => json_encode([
                            'lokasi' => $lokasiRaw,
                            'wfh'    => $wfh ? 1 : 0,
                        ]),
                        'result_count' => method_exists($jobs, 'total') ? (int)$jobs->total() : 0,
                        'user_ip' => request()->ip(),
                        'user_agent' => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                }
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
            'external_rendered' => $external_rendered,
        ]);
    }

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

        // DB empty -> fallback. server-side fallback will use perPage capped to 20,
        // and paginator total will be capped to at most 2 pages.
        $perFallback = min(20, max(1, $perPage));
        return $this->fallbackCareerjetSearchWithRetries($qRaw, $lokasiRaw, $page, $perFallback, $request);
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
        // perPage here is already capped by performSearch (<=20)
        $cacheKey = 'careerjet_search:' . md5("q={$q}|lokasi={$lokasi}|page={$page}|per={$perPage}");
        $cacheStore = Cache::store('redis');

        Log::debug('fallbackCareerjetSearch called', ['q'=>$q, 'lokasi'=>$lokasi, 'page'=>$page, 'per'=>$perPage]);

        $cached = null;
        try {
            $cached = $cacheStore->get($cacheKey);
        } catch (\Throwable $e) {
            Log::warning('fallbackCareerjetSearch: cache get failed: ' . $e->getMessage());
        }

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

            // Ensure paginator total is capped to 2 pages
            $maxPages = 2;
            $totalCapped = min($total, $perPage * $maxPages);

            return new LengthAwarePaginator($items, $totalCapped, $perPage, $page, ['path'=>$request->url(), 'query'=>$request->query()]);
        }

        $client = new CareerjetClient();
        $params = [
            'keywords' => $q ?: '',
            'location' => $lokasi ?: '',
            'page' => $page,
            'page_size' => $perPage,
            'user_ip' => $request->ip() ?: env('CAREERJET_USER_IP', '127.0.0.1'),
            'user_agent' => $request->header('User-Agent', env('CAREERJET_USER_AGENT', 'TeleworksBot/1.0')),
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
        $count = 0;
        foreach ($jobsRaw as $j) {
            if ($count >= $perPage) break; // ensure cap per page
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
            $count++;
        }

        // Cap total to at most 2 pages (perPage * 2)
        $maxPages = 2;
        $totalCapped = min(is_numeric($totalHits) ? (int)$totalHits : count($jobsRaw), $perPage * $maxPages);

        $paginator = new LengthAwarePaginator($items, $totalCapped, $perPage, $page, ['path'=>$request->url(), 'query'=>$request->query()]);

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
                    'total' => $totalCapped,
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

    /**
     * AJAX endpoint: ambil hasil eksternal (Careerjet) maksimal 20 job, cache 30 menit.
     * Response JSON: { items: [...], total: int }
     */
    public function externalJobsAjax(Request $request)
    {
        $q = trim($request->query('q', ''));
        $lokasi = trim($request->query('lokasi', ''));
        $perLimit = 20;

        $cacheKey = 'careerjet_ajax:' . md5("q={$q}|lokasi={$lokasi}|per={$perLimit}");
        $cache = Cache::store('redis');

        try {
            $cached = $cache->get($cacheKey);
        } catch (\Throwable $e) {
            Log::warning("externalJobsAjax: Redis/cache get failed: " . $e->getMessage());
            $cached = null;
        }

        if (is_array($cached) && !empty($cached)) {
            return response()->json([
                'items' => $cached['items'],
                'total' => (int)($cached['total'] ?? count($cached['items'])),
                'cached' => true,
            ]);
        }

        $client = new CareerjetClient();
        $params = [
            'keywords' => $q ?: '',
            'location' => $lokasi ?: '',
            'page' => 1,
            'page_size' => $perLimit,
            'user_ip' => $request->ip() ?: env('CAREERJET_USER_IP', '127.0.0.1'),
            'user_agent' => $request->header('User-Agent', env('CAREERJET_USER_AGENT', 'TeleworksBot/1.0')),
            'locale_code' => 'id_ID',
            'sort' => 'date',
        ];

        $resp = $client->query($params, config('app.url'), $params['user_agent']);

        if (!is_array($resp) || (isset($resp['__error']) && $resp['__error'])) {
            Log::warning('externalJobsAjax: Careerjet API error', ['resp' => $resp, 'params' => $params]);
            return response()->json(['items' => [], 'total' => 0, 'cached' => false]);
        }

        $jobsRaw = $resp['jobs'] ?? [];
        $totalHits = is_numeric($resp['hits'] ?? null) ? (int)$resp['hits'] : count($jobsRaw);

        $items = [];
        $count = 0;

        // dedupe using DB final_url and identifier_value
        $existingUrls = Job::query()->whereNotNull('final_url')->pluck('final_url')->map(fn($u)=> (string)$u)->filter()->values()->all();
        $existingIds = Job::query()->whereNotNull('identifier_value')->pluck('identifier_value')->map(fn($v)=> (string)$v)->filter()->values()->all();

        foreach ($jobsRaw as $j) {
            if ($count >= $perLimit) break;

            $title = trim($j['title'] ?? 'No title');
            $company = $j['company'] ?? ($j['site'] ?? null);
            $location = $j['locations'] ?? ($j['location'] ?? null);
            $applyUrl = $j['apply_url'] ?? ($j['url'] ?? null);
            $dateRaw = $j['date'] ?? null;

            $isDup = false;
            if ($applyUrl) {
                $applyStr = (string)$applyUrl;
                if (in_array($applyStr, $existingUrls, true) || in_array($applyStr, $existingIds, true)) {
                    $isDup = true;
                }
            }
            if ($isDup) continue;

            $datePosted = null;
            if (!empty($dateRaw)) {
                try { $datePosted = Carbon::parse($dateRaw)->toDateTimeString(); } catch (\Throwable $e) { $datePosted = null; }
            }

            $items[] = [
                'title' => $title,
                'company' => $company,
                'location' => $location,
                'apply_url' => $applyUrl,
                'date_posted' => $datePosted,
                'raw' => $j,
            ];
            $count++;
        }

        if (!empty($items)) {
            try {
                $cache->put($cacheKey, ['items' => $items, 'total' => $totalHits], now()->addMinutes(30));
                Log::debug('externalJobsAjax: cached key ' . $cacheKey);
            } catch (\Throwable $e) {
                Log::warning('externalJobsAjax: failed caching ' . $cacheKey . ' : ' . $e->getMessage());
            }
        } else {
            Log::debug('externalJobsAjax: empty external result', ['params' => $params]);
        }

        return response()->json([
            'items' => $items,
            'total' => count($items),
            'cached' => false,
        ]);
    }

    protected function countDbMatches(string $qRaw = '', string $lokasiRaw = ''): int
    {
        try {
            $qRaw = (string)$qRaw;
            $lokasiRaw = (string)$lokasiRaw;
            $query = Job::query()->where('status', 'published')
                ->where(function ($qb) {
                    $qb->whereNull('expires_at')->orWhere('expires_at', '>', now());
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
            return (int)$query->count();
        } catch (\Throwable $e) {
            Log::warning('countDbMatches failed: ' . $e->getMessage());
            return 0;
        }
    }

    /* ============================
       Profanity helpers (block-save + clean slug mapping)
       ============================ */

    /**
     * Load profanity list from storage/profanity.json (cached per request).
     *
     * @return array
     */
    private function loadProfanityList(): array
    {
        static $list = null;
        if ($list !== null) return $list;

        $path = storage_path('app/profanity.json');
        if (!File::exists($path)) {
            $list = [];
            return $list;
        }

        try {
            $json = File::get($path);
            $arr = json_decode($json, true) ?: [];
        } catch (\Throwable $e) {
            Log::warning('Failed to load profanity list: ' . $e->getMessage());
            $arr = [];
        }

        $list = array_values(array_filter(array_map(function($w) {
            if (!is_string($w)) return null;
            $v = trim(mb_strtolower($w, 'UTF-8'));
            return $v === '' ? null : $v;
        }, $arr)));

        return $list;
    }

    /**
     * Normalize input for profanity checking: lowercase, simple leet mapping,
     * remove punctuation and collapse spaces.
     *
     * @param string $s
     * @return string
     */
    private function normalizeForProfanity(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        // simple leet substitutions
        $s = strtr($s, ['0'=>'o','1'=>'i','3'=>'e','@'=>'a','4'=>'a','$'=>'s','5'=>'s','7'=>'t']);
        // remove non letter/number/space
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    /**
     * Check whether given text contains any profane word/phrase.
     *
     * @param string|null $text
     * @return bool
     */
    private function containsProfanity(?string $text): bool
    {
        if (!$text) return false;
        $textNorm = ' ' . $this->normalizeForProfanity($text) . ' ';
        $words = $this->loadProfanityList();
        if (empty($words)) return false;

        foreach ($words as $bad) {
            if ($bad === '') continue;
            // word boundary match (unicode, case-insensitive)
            $pattern = '/\b' . preg_quote($bad, '/') . '\b/iu';
            if (preg_match($pattern, $textNorm)) {
                return true;
            }
            // fallback: strpos on normalized text (for multiword phrases)
            if (mb_strpos($textNorm, ' ' . $bad . ' ') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clean and slugify text. If profanity detected, map to defaults:
     * - type 'q'      => 'sales'
     * - type 'lokasi' => 'jakarta'
     *
     * @param string|null $text
     * @param string $type 'q' or 'lokasi'
     * @return string
     */
    private function cleanAndSlug(?string $text, string $type = 'q'): string
    {
        if (!$text) return '';

        // if contains profanity -> map to preset
        if ($this->containsProfanity($text)) {
            return $type === 'lokasi' ? 'jakarta' : 'sales';
        }

        // safe: produce normal slug from original text
        return Str::slug($text, '-');
    }
}

