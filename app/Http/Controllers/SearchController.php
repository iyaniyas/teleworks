<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\SearchLog;
use App\Services\CareerjetClient;
use App\Services\CareerjetParser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $qRaw      = trim($request->query('q', ''));
        $lokasiRaw = trim($request->query('lokasi', ''));
        $wfh       = $request->boolean('wfh');
        $perPage   = 21;

        // NEW FEATURE:
        // If client requests database-only search (query param `db_only=1`), use simplified DB-only search
        // that prioritizes paid/premium jobs first. Otherwise use the full multi-source performSearch.
        if ($request->boolean('db_only')) {
            $jobs = $this->performSearchDbOnly($qRaw, $lokasiRaw, $wfh, $perPage, $request);
        } else {
            $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);
        }

        $dbTotal = $this->countDbMatches($qRaw, $lokasiRaw);
        $fallbackHasResults = (method_exists($jobs, 'total')
            ? (int)$jobs->total()
            : (is_countable($jobs) ? count($jobs) : 0)) > 0;
        $external_rendered = ($dbTotal === 0 && $fallbackHasResults);

        $fallback_note = null;
        try {
            if ($dbTotal === 0 && $fallbackHasResults) {
                if (!empty($qRaw) && !empty($lokasiRaw)) {
                    $fallback_note = sprintf(
                        'Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di %s. Berikut hasil dari lokasi lain:',
                        $qRaw,
                        $lokasiRaw
                    );
                } elseif (!empty($qRaw)) {
                    $fallback_note = sprintf(
                        'Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di database. Berikut hasil di lokasi lain:',
                        $qRaw
                    );
                } elseif (!empty($lokasiRaw)) {
                    $fallback_note = sprintf(
                        'Hasil dari sumber lain karena tidak ditemukan lowongan di %s. Berikut hasil dari lokasi lain:',
                        $lokasiRaw
                    );
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
                        'q_present'       => $qRaw !== '' ? true : false,
                        'lokasi_present'  => $lokasiRaw !== '' ? true : false,
                        'ip'              => $request->ip(),
                        'ua'              => substr($request->header('User-Agent', ''), 0, 255),
                    ]);
                } else {
                    SearchLog::create([
                        'q'           => Str::limit($qRaw, 255),
                        'params'      => json_encode([
                            'lokasi' => $lokasiRaw,
                            'wfh'    => $wfh ? 1 : 0,
                        ]),
                        'result_count' => method_exists($jobs, 'total')
                            ? (int)$jobs->total()
                            : (is_countable($jobs) ? count($jobs) : 0),
                        'user_ip'     => $request->ip(),
                        'user_agent'  => substr($request->header('User-Agent', ''), 0, 255),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error: ' . $e->getMessage());
        }

        return view('cari', [
            'jobs'              => $jobs,
            'q'                 => mb_strtolower($qRaw),
            'lokasi'            => mb_strtolower($lokasiRaw),
            'qRaw'              => $qRaw,
            'lokasiRaw'         => $lokasiRaw,
            'wfh'               => $wfh ? '1' : '0',
            'fallback_note'     => $fallback_note,
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
        $perPage = 21;

        // Build cleaned slug for lokasi (map profanity => jakarta)
        $cleanLokSlug = $this->cleanAndSlug($lokasiRaw, 'lokasi');
        $origLokParam = Str::slug($lokasi, '-');

        if ($cleanLokSlug !== $origLokParam) {
            // redirect to cleaned /cari URL (map profanity -> jakarta)
            $target = $cleanLokSlug ? url('/cari/lokasi/' . $cleanLokSlug) : url('/cari');
            return redirect()->to($target)->setStatusCode(302);
        }

        // respect db_only param here as well
        if ($request && $request->boolean('db_only')) {
            $jobs = $this->performSearchDbOnly($qRaw, $lokasiRaw, $wfh, $perPage, $request);
        } else {
            $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);
        }

        $dbTotal = $this->countDbMatches($qRaw, $lokasiRaw);
        $fallbackHasResults = (method_exists($jobs, 'total')
            ? (int)$jobs->total()
            : (is_countable($jobs) ? count($jobs) : 0)) > 0;
        $external_rendered = ($dbTotal === 0 && $fallbackHasResults);

        $fallback_note = null;
        try {
            if ($dbTotal === 0 && $fallbackHasResults) {
                $fallback_note = sprintf(
                    'Hasil dari sumber lain karena tidak ditemukan lowongan di %s. Berikut hasil dari lokasi lain:',
                    $lokasiRaw
                );
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
                        'ip'             => request()->ip(),
                        'ua'             => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                } else {
                    \App\Models\SearchLog::create([
                        'q'           => '',
                        'params'      => json_encode(['lokasi' => $lokasiRaw, 'wfh' => $wfh ? 1 : 0]),
                        'result_count' => method_exists($jobs, 'total') ? (int)$jobs->total() : 0,
                        'user_ip'     => request()->ip(),
                        'user_agent'  => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error (slugLocation): ' . $e->getMessage());
        }

        return view('cari', [
            'jobs'              => $jobs,
            'q'                 => '',
            'lokasi'            => mb_strtolower($lokasiRaw),
            'qRaw'              => '',
            'lokasiRaw'         => $lokasiRaw,
            'wfh'               => $wfh ? '1' : '0',
            'fallback_note'     => $fallback_note,
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
        $perPage = 21;

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

        // respect db_only param here too
        if ($request && $request->boolean('db_only')) {
            $jobs = $this->performSearchDbOnly($qRaw, $lokasiRaw, $wfh, $perPage, $request);
        } else {
            $jobs = $this->performSearch($qRaw, $lokasiRaw, $wfh, $perPage, $request);
        }

        $dbTotal = $this->countDbMatches($qRaw, $lokasiRaw);
        $fallbackHasResults = (method_exists($jobs, 'total')
            ? (int)$jobs->total()
            : (is_countable($jobs) ? count($jobs) : 0)) > 0;
        $external_rendered = ($dbTotal === 0 && $fallbackHasResults);

        $fallback_note = null;
        try {
            if ($dbTotal === 0 && $fallbackHasResults) {
                if (!empty($qRaw) && !empty($lokasiRaw)) {
                    $fallback_note = sprintf(
                        'Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di %s. Berikut hasil dari lokasi lain:',
                        $qRaw,
                        $lokasiRaw
                    );
                } elseif (!empty($qRaw)) {
                    $fallback_note = sprintf(
                        'Hasil dari sumber lain karena tidak ditemukan lowongan "%s" di database. Berikut hasil di lokasi lain:',
                        $qRaw
                    );
                } elseif (!empty($lokasiRaw)) {
                    $fallback_note = sprintf(
                        'Hasil dari sumber lain karena tidak ditemukan lowongan di %s. Berikut hasil dari lokasi lain:',
                        $lokasiRaw
                    );
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
                        'q_present'      => $qRaw !== '' ? true : false,
                        'lokasi_present' => $lokasiRaw !== '' ? true : false,
                        'ip'             => request()->ip(),
                        'ua'             => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                } else {
                    SearchLog::create([
                        'q'           => Str::limit($qRaw, 255),
                        'params'      => json_encode([
                            'lokasi' => $lokasiRaw,
                            'wfh'    => $wfh ? 1 : 0,
                        ]),
                        'result_count' => method_exists($jobs, 'total') ? (int)$jobs->total() : 0,
                        'user_ip'     => request()->ip(),
                        'user_agent'  => substr(request()->header('User-Agent', ''), 0, 255),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SearchLog error (slug): ' . $e->getMessage());
        }

        return view('cari', [
            'jobs'              => $jobs,
            'q'                 => mb_strtolower($qRaw),
            'lokasi'            => mb_strtolower($lokasiRaw),
            'qRaw'              => $qRaw,
            'lokasiRaw'         => $lokasiRaw,
            'wfh'               => $wfh ? '1' : '0',
            'fallback_note'     => $fallback_note,
            'external_rendered' => $external_rendered,
        ]);
    }

    /**
     * performSearch — merges DB + Careerjet into unified paginator.
     * If DB empty => fallback to Careerjet (and Jooble if Careerjet empty).
     *
     * Additional behavior: if mapped Careerjet external results (after dedupe) < 10,
     * fetch Jooble and top-up external results up to 10 (or until Jooble exhausted).
     *
     * (original complex implementation — unchanged)
     */
    protected function performSearch($qRaw, $lokasiRaw, $wfh = false, $perPage = 21, Request $request = null)
    {
        $request = $request ?: request();
        $page = max(1, (int)$request->query('page', 1));

        $query = Job::query()
            ->with('company')
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

        // DB paginator for the requested page (fast)
        $dbPaginator = $query->paginate($perPage);
        $dbItems = collect($dbPaginator->items())->map(function ($job) {
            $datePosted = $job->date_posted ?? $job->created_at ?? null;
            try {
                $dateParsed = $datePosted ? Carbon::parse($datePosted) : null;
            } catch (\Throwable $e) {
                $dateParsed = null;
            }

            // sinkronisasi nama perusahaan: pakai tabel companies kalau ada
            $companyName = null;
            try {
                $companyModel = $job->company()->first();
                if ($companyModel && !empty($companyModel->name)) {
                    $companyName = $companyModel->name;
                }
            } catch (\Throwable $e) {
                $companyModel = null;
            }
            if (!$companyName && !empty($job->company)) {
                $companyName = $job->company;
            }

            return (object)[
                'id'         => $job->id,
                'title'      => $job->title,
                'company'    => $companyName,
                'location'   => $job->location ?? $job->job_location ?? null,
                'apply_url'  => url('/loker/' . $job->id),
                'url'        => url('/loker/' . $job->id),
                'description'=> null,
                'date_posted'=> $dateParsed,
                'source'     => 'db',
                'raw'        => $job,
                'is_external'=> false,
                'dedupe_key' => $job->final_url
                    ? (string)$job->final_url
                    : ($job->identifier_value ? (string)$job->identifier_value : null),
            ];
        });

        $dbTotal = method_exists($dbPaginator, 'total') ? (int)$dbPaginator->total() : $dbItems->count();

        // If DB has no results -> run fallback which tries careerjet then jooble
        if ($dbTotal === 0) {
            return $this->fallbackCareerjetSearchWithRetries($qRaw, $lokasiRaw, $page, $perPage, $request);
        }

        // DB has results -> enrich by fetching Careerjet to merge (dedupe + sort)
        $maxPagesCap = 5;
        $externalFetchLimit = $perPage * $maxPagesCap; // up to 105
        $careerjet = $this->fetchCareerjetResultsCached($qRaw, $lokasiRaw, 1, $externalFetchLimit);
        $externalItems = collect($careerjet['items'] ?? []);
        $externalTotalRaw = (int)($careerjet['total'] ?? $externalItems->count());

        // Build dedupe set from DB (final_url + identifier_value)
        $existingUrls = Job::query()
            ->whereNotNull('final_url')
            ->pluck('final_url')
            ->map(function ($u) {
                return (string)$u;
            })->filter()->values()->all();

        $existingIds = Job::query()
            ->whereNotNull('identifier_value')
            ->pluck('identifier_value')
            ->map(function ($v) {
                return (string)$v;
            })->filter()->values()->all();

        $existingSet = array_merge($existingUrls, $existingIds);

        // Map external items into same object shape and dedupe careerjet items first
        $mappedExternal = collect();
        $seenExternalKeys = [];

        foreach ($externalItems as $ext) {
            $dedupeKey = null;
            if (!empty($ext['raw']['apply_url'])) {
                $dedupeKey = (string)$ext['raw']['apply_url'];
            } elseif (!empty($ext['raw']['url'])) {
                $dedupeKey = (string)$ext['raw']['url'];
            } elseif (!empty($ext['raw']['id'])) {
                $dedupeKey = (string)$ext['raw']['id'];
            } elseif (!empty($ext['url'])) {
                $dedupeKey = (string)$ext['url'];
            }

            if ($dedupeKey && (in_array($dedupeKey, $existingSet, true) || in_array($dedupeKey, $seenExternalKeys, true))) {
                continue;
            }
            if ($dedupeKey) {
                $seenExternalKeys[] = $dedupeKey;
            }

            $dateParsed = null;
            if (!empty($ext['date_posted'])) {
                try {
                    $dateParsed = Carbon::parse($ext['date_posted']);
                } catch (\Throwable $e) {
                    $dateParsed = null;
                }
            }

            $mappedExternal->push((object)[
                'id'         => $ext['raw']['id'] ?? null,
                'title'      => $ext['title'] ?? 'No title',
                'company'    => $ext['company'] ?? null,
                'location'   => $ext['location'] ?? null,
                'apply_url'  => $ext['apply_url'] ?? $ext['url'] ?? ($ext['raw']['apply_url'] ?? $ext['raw']['url'] ?? null),
                'url'        => $ext['url'] ?? ($ext['raw']['apply_url'] ?? $ext['raw']['url'] ?? null),
                'description'=> isset($ext['description']) ? strip_tags($ext['description']) : null,
                'date_posted'=> $dateParsed,
                'source'     => $ext['source'] ?? 'careerjet',
                'raw'        => $ext['raw'] ?? null,
                'is_external'=> true,
                'dedupe_key' => $dedupeKey,
            ]);
        }

        // If careerjet external results (after dedupe) are fewer than 10 -> top-up from Jooble
        $minExternalTarget = 10;
        if ($mappedExternal->count() < $minExternalTarget) {
            try {
                $joobleResult = $this->fetchJoobleResultsCached($qRaw, $lokasiRaw, 1, $externalFetchLimit);
                $joobleItems = collect($joobleResult['items'] ?? []);
                foreach ($joobleItems as $j) {
                    if ($mappedExternal->count() >= $minExternalTarget) {
                        break;
                    }

                    // compute dedupe key for jooble item
                    $dedupeKey = null;
                    if (!empty($j['raw']['link'])) {
                        $dedupeKey = (string)$j['raw']['link'];
                    } elseif (!empty($j['raw']['url'])) {
                        $dedupeKey = (string)$j['raw']['url'];
                    } elseif (!empty($j['raw']['id'])) {
                        $dedupeKey = (string)$j['raw']['id'];
                    } elseif (!empty($j['url'])) {
                        $dedupeKey = (string)$j['url'];
                    } elseif (!empty($j['apply_url'])) {
                        $dedupeKey = (string)$j['apply_url'];
                    }

                    if ($dedupeKey && (in_array($dedupeKey, $existingSet, true) || in_array($dedupeKey, $seenExternalKeys, true))) {
                        continue;
                    }
                    if ($dedupeKey) {
                        $seenExternalKeys[] = $dedupeKey;
                    }

                    $dateParsed = null;
                    if (!empty($j['date_posted'])) {
                        try {
                            $dateParsed = Carbon::parse($j['date_posted']);
                        } catch (\Throwable $e) {
                            $dateParsed = null;
                        }
                    }

                    $mappedExternal->push((object)[
                        'id'         => $j['raw']['id'] ?? null,
                        'title'      => $j['title'] ?? 'No title',
                        'company'    => $j['company'] ?? null,
                        'location'   => $j['location'] ?? null,
                        'apply_url'  => $j['apply_url'] ?? $j['url'] ?? ($j['raw']['link'] ?? $j['raw']['url'] ?? null),
                        'url'        => $j['url'] ?? ($j['raw']['link'] ?? $j['raw']['url'] ?? null),
                        'description'=> isset($j['description']) ? strip_tags($j['description']) : null,
                        'date_posted'=> $dateParsed,
                        'source'     => $j['source'] ?? 'jooble',
                        'raw'        => $j['raw'] ?? null,
                        'is_external'=> true,
                        'dedupe_key' => $dedupeKey,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Top-up Jooble failed: ' . $e->getMessage());
            }
        }

        // Merge DB items and external items and sort by date_posted desc (nulls last)
        $merged = $dbItems->concat($mappedExternal)
            ->sortByDesc(function ($it) {
                return $it->date_posted ? $it->date_posted->getTimestamp() : 0;
            })->values();

        // Compute combined total — cap to maxPagesCap pages
        $combinedTotalEstimate = $dbTotal + $externalTotalRaw;
        $totalCapped = min($combinedTotalEstimate, $perPage * $maxPagesCap);

        // Now produce a paginator page slice from $merged
        $start = ($page - 1) * $perPage;
        $sliced = $merged->slice($start, $perPage)->values();

        // If slice is empty but DB had items on other pages, return dbPaginator to keep consistency.
        if ($sliced->isEmpty() && $dbTotal > 0) {
            return $dbPaginator;
        }

        // Build LengthAwarePaginator with merged slice
        $paginator = new LengthAwarePaginator($sliced, $totalCapped, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        return $paginator;
    }

    /**
     * NEW: performSearchDbOnly
     *
     * A simpler DB-only search implementation that:
     *  - searches published, non-expired jobs,
     *  - optionally filters by query and lokasi,
     *  - optionally filters WFH (is_remote),
     *  - orders by is_paid desc, then date_posted desc, created_at desc,
     *  - maps items into a lightweight object shape (matching front-end expectations).
     *
     * Use by passing ?db_only=1 in the request or by calling directly.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function performSearchDbOnly($qRaw, $lokasiRaw, $wfh = false, $perPage = 21, Request $request = null)
    {
        $request = $request ?: request();
        $page = max(1, (int)$request->query('page', 1));

        $query = Job::query()
            ->with('company')
            ->where('status', 'published')
            ->where(function ($qb) {
                $qb->whereNull('expires_at')
                   ->orWhere('expires_at', '>', now());
            });

        if (!empty($qRaw)) {
            $query->where(function ($qq) use ($qRaw) {
                $qq->where('title', 'like', "%{$qRaw}%")
                   ->orWhere('company', 'like', "%{$qRaw}%")
                   ->orWhere('description', 'like', "%{$qRaw}%");
            });
        }

        if (!empty($lokasiRaw)) {
            $query->where(function ($qq) use ($lokasiRaw) {
                $qq->where('location', 'like', "%{$lokasiRaw}%")
                   ->orWhere('job_location', 'like', "%{$lokasiRaw}%");
            });
        }

        if ($wfh) {
            $query->where('is_remote', 1);
        }

        // Prioritize paid/premium jobs first
        $query->orderByDesc('is_paid')->orderByDesc('date_posted')->orderByDesc('created_at');

        $paginator = $query->paginate($perPage);

        // Map items so views can access raw model as $job->raw (matching previous view expectations)
        $mapped = collect($paginator->items())->map(function ($job) {
            $datePosted = $job->date_posted ?? $job->created_at ?? null;
            try {
                $dateParsed = $datePosted ? Carbon::parse($datePosted) : null;
            } catch (\Throwable $e) {
                $dateParsed = null;
            }

            // sinkronisasi nama perusahaan: pakai tabel companies kalau ada
            $companyName = null;
            try {
                $companyModel = $job->company()->first();
                if ($companyModel && !empty($companyModel->name)) {
                    $companyName = $companyModel->name;
                }
            } catch (\Throwable $e) {
                $companyModel = null;
            }
            if (!$companyName && !empty($job->company)) {
                $companyName = $job->company;
            }

            return (object)[
                'id'         => $job->id,
                'title'      => $job->title,
                'company'    => $companyName,
                'location'   => $job->location ?? $job->job_location ?? null,
                'apply_url'  => url('/loker/' . $job->id),
                'url'        => url('/loker/' . $job->id),
                'date_posted'=> $dateParsed,
                'source'     => 'db',
                'raw'        => $job,
                'is_external'=> false,
                'description'=> null,
            ];
        });

        // Set new collection to paginator and return
        $paginator->setCollection($mapped);
        return $paginator;
    }

    /**
     * Fetch Careerjet results with cache. Returns ['items'=>[], 'total'=>int]
     */
    protected function fetchCareerjetResultsCached(string $q, string $lokasi, int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = 'careerjet_search:' . md5("q={$q}|lokasi={$lokasi}|page={$page}|per={$pageSize}");
        $cacheStore = Cache::store('redis');

        try {
            $cached = $cacheStore->get($cacheKey);
        } catch (\Throwable $e) {
            Log::warning('fetchCareerjetResultsCached: cache get failed: ' . $e->getMessage());
            $cached = null;
        }

        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        // Query Careerjet (returns normalized structure)
        $result = $this->fetchCareerjetResults($q, $lokasi, $page, $pageSize);

        if (!empty($result['items'])) {
            try {
                // **Cache changed to 12 hours**
                $cacheStore->put($cacheKey, $result, now()->addHours(12));
            } catch (\Throwable $e) {
                Log::warning('fetchCareerjetResultsCached: failed caching: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Direct Careerjet call (stateless). Returns normalized array.
     * We request up to $pageSize items (we will call with pageSize up to perPage*maxPages).
     */
    protected function fetchCareerjetResults(string $q, string $lokasi, int $page = 1, int $pageSize = 20): array
    {
        $client = new CareerjetClient();
        $params = [
            'keywords'   => $q ?: '',
            'location'   => $lokasi ?: '',
            'page'       => $page,
            'page_size'  => $pageSize,
            'user_ip'    => request()->ip() ?: env('CAREERJET_USER_IP', '127.0.0.1'),
            'user_agent' => request()->header('User-Agent', env('CAREERJET_USER_AGENT', 'TeleworksBot/1.0')),
            'locale_code'=> 'id_ID',
            'sort'       => 'date',
        ];

        $resp = $client->query($params, config('app.url'), $params['user_agent']);

        if (!is_array($resp) || (isset($resp['__error']) && $resp['__error'])) {
            Log::warning("Careerjet API ERROR", ['resp' => $resp, 'params' => $params]);
            return ['items' => [], 'total' => 0];
        }

        $jobsRaw = $resp['jobs'] ?? [];
        $totalHits = is_numeric($resp['hits'] ?? null) ? (int)$resp['hits'] : count($jobsRaw);

        $items = [];
        $count = 0;
        foreach ($jobsRaw as $j) {
            if ($count >= $pageSize) {
                break;
            }

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
                try {
                    $datePosted = Carbon::parse($dateRaw)->toDateTimeString();
                } catch (\Throwable $e) {
                    $datePosted = null;
                }
            }

            $items[] = [
                'title'       => $title,
                'company'     => $company,
                'location'    => $location,
                'apply_url'   => $applyUrl,
                'url'         => $applyUrl,
                'description' => $desc,
                'date_posted' => $datePosted,
                'source'      => 'careerjet',
                'raw'         => $j,
            ];

            $count++;
        }

        return ['items' => $items, 'total' => $totalHits];
    }

    /**
     * Try Careerjet (multiple location guesses). If all Careerjet tries empty -> fallback to Jooble.
     * This method returns a LengthAwarePaginator.
     */
    protected function fallbackCareerjetSearchWithRetries(string $q, string $lokasi, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        $tries = [$lokasi, '', 'Indonesia'];
        foreach ($tries as $tryLok) {
            $p = $this->fallbackCareerjetSearchSingle($q, $tryLok, $page, $perPage, $request);
            $total = method_exists($p, 'total') ? (int)$p->total() : (is_countable($p) ? count($p) : 0);
            if ($total > 0) {
                return $p;
            }
        }

        // Careerjet gave nothing -> try Jooble as last resort
        $maxPages = 5;
        $fetchLimit = max(1, $perPage * $maxPages); // up to 105
        $joobleResult = $this->fetchJoobleResultsCached($q, $lokasi, 1, $fetchLimit);

        $rawItems = $joobleResult['items'] ?? [];
        $totalHits = (int)($joobleResult['total'] ?? count($rawItems));

        if (empty($rawItems)) {
            return new LengthAwarePaginator(collect([]), 0, $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
        }

        // Map to objects
        $items = collect($rawItems)->map(function ($it) {
            $dateParsed = null;
            if (!empty($it['date_posted'])) {
                try {
                    $dateParsed = Carbon::parse($it['date_posted']);
                } catch (\Throwable $e) {
                    $dateParsed = null;
                }
            }
            return (object)[
                'title'      => $it['title'] ?? 'No title',
                'company'    => $it['company'] ?? null,
                'location'   => $it['location'] ?? null,
                'apply_url'  => $it['apply_url'] ?? $it['url'] ?? null,
                'url'        => $it['url'] ?? $it['apply_url'] ?? null,
                'description'=> $it['description'] ?? null,
                'date_posted'=> $dateParsed,
                'source'     => 'jooble',
                'raw'        => $it['raw'] ?? null,
                'is_external'=> true,
            ];
        });

        $totalCapped = min($totalHits, $perPage * $maxPages);

        return new LengthAwarePaginator($items, $totalCapped, $perPage, $page, [
            'path' => $request->url(),
            'query'=> $request->query(),
        ]);
    }

    protected function fallbackCareerjetSearchSingle(string $q, string $lokasi, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        $perFetch = max(1, $perPage * 5);
        $cacheKey = 'careerjet_fallback:' . md5("q={$q}|lokasi={$lokasi}|page={$page}|per={$perFetch}");
        $cacheStore = Cache::store('redis');

        Log::debug('fallbackCareerjetSearchSingle called', ['q' => $q, 'lokasi' => $lokasi, 'page' => $page, 'per' => $perFetch]);

        $cached = null;
        try {
            $cached = $cacheStore->get($cacheKey);
        } catch (\Throwable $e) {
            Log::warning('fallbackCareerjetSearchSingle: cache get failed: ' . $e->getMessage());
        }

        if (is_array($cached) && !empty($cached)) {
            $rawItems = $cached['items'] ?? [];
            $total = (int)($cached['total'] ?? count($rawItems));
            $items = collect($rawItems)->map(function ($it) {
                $title = $it['title'] ?? ($it['job_title'] ?? 'No title');
                $company = $it['company'] ?? null;
                $location = $it['location'] ?? null;
                $applyUrl = $it['apply_url'] ?? ($it['url'] ?? null);
                $datePosted = null;
                if (!empty($it['date_posted'])) {
                    try {
                        $datePosted = Carbon::parse($it['date_posted']);
                    } catch (\Throwable $e) {
                        $datePosted = null;
                    }
                }
                $obj = (object)[
                    'title'      => $title,
                    'company'    => $company,
                    'location'   => $location,
                    'apply_url'  => $applyUrl,
                    'url'        => $applyUrl,
                    'description'=> $it['description'] ?? null,
                    'date_posted'=> $datePosted,
                    'source'     => $it['source'] ?? 'careerjet',
                    'raw'        => $it['raw'] ?? null,
                ];
                $obj->is_external = true;
                return $obj;
            });

            // Cap paginator total to at most maxPages (5)
            $maxPages = 5;
            $totalCapped = min($total, $perPage * $maxPages);

            return new LengthAwarePaginator($items, $totalCapped, $perPage, $page, [
                'path' => $request->url(),
                'query'=> $request->query(),
            ]);
        }

        // Call Careerjet client requesting up to $perFetch items (server-side)
        $client = new CareerjetClient();
        $params = [
            'keywords'   => $q ?: '',
            'location'   => $lokasi ?: '',
            'page'       => 1,
            'page_size'  => $perFetch,
            'user_ip'    => $request->ip() ?: env('CAREERJET_USER_IP', '127.0.0.1'),
            'user_agent' => $request->header('User-Agent', env('CAREERJET_USER_AGENT', 'TeleworksBot/1.0')),
            'locale_code'=> 'id_ID',
            'sort'       => 'date',
        ];

        $resp = $client->query($params, config('app.url'), $params['user_agent']);

        if (!is_array($resp) || (isset($resp['__error']) && $resp['__error'])) {
            Log::warning("Careerjet fallback API ERROR", ['resp' => $resp, 'params' => $params]);
            return new LengthAwarePaginator(collect([]), 0, $perPage, $page, [
                'path' => $request->url(),
                'query'=> $request->query(),
            ]);
        }

        $jobsRaw = $resp['jobs'] ?? [];
        $totalHits = is_numeric($resp['hits'] ?? null) ? (int)$resp['hits'] : count($jobsRaw);

        $items = collect();
        $count = 0;
        foreach ($jobsRaw as $j) {
            if ($count >= $perFetch) {
                break;
            }

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
                try {
                    $datePosted = Carbon::parse($dateRaw);
                } catch (\Throwable $e) {
                    $datePosted = null;
                }
            }

            $obj = (object)[
                'title'      => $title,
                'company'    => $company,
                'location'   => $location,
                'apply_url'  => $applyUrl,
                'url'        => $applyUrl,
                'description'=> $desc,
                'date_posted'=> $datePosted,
                'source'     => 'careerjet',
                'raw'        => $j,
                'is_external'=> true,
            ];
            $items->push($obj);
            $count++;
        }

        // Cap total to at most maxPages (5)
        $maxPages = 5;
        $totalCapped = min(is_numeric($totalHits) ? (int)$totalHits : count($jobsRaw), $perPage * $maxPages);

        $paginator = new LengthAwarePaginator($items, $totalCapped, $perPage, $page, [
            'path' => $request->url(),
            'query'=> $request->query(),
        ]);

        if ($items->count() > 0) {
            try {
                $cachePayload = [
                    'items' => $items->map(function ($it) {
                        return [
                            'title'       => $it->title,
                            'company'     => $it->company,
                            'location'    => $it->location,
                            'apply_url'   => $it->apply_url,
                            'url'         => $it->url,
                            'description' => $it->description,
                            'date_posted' => $it->date_posted ? $it->date_posted->toDateTimeString() : null,
                            'source'      => $it->source,
                            'raw'         => $it->raw,
                        ];
                    })->toArray(),
                    'total' => $totalCapped,
                ];

                // **Cache changed to 12 hours**
                Cache::store('redis')->put($cacheKey, $cachePayload, now()->addHours(12));
                Log::debug("Careerjet fallback: cached key {$cacheKey}");
            } catch (\Throwable $e) {
                Log::warning("Careerjet fallback: FAILED caching {$cacheKey} : " . $e->getMessage());
            }
        } else {
            Log::debug("Careerjet fallback: empty result for params", ['params' => $params]);
        }

        return $paginator;
    }

    /**
     * Jooble fetch + cache helpers
     */
    protected function fetchJoobleResultsCached(string $q, string $lokasi, int $page = 1, int $pageSize = 20): array
    {
        $cacheKey = 'jooble_search:' . md5("q={$q}|lokasi={$lokasi}|page={$page}|per={$pageSize}");
        $cache = Cache::store('redis');

        try {
            $cached = $cache->get($cacheKey);
        } catch (\Throwable $e) {
            Log::warning('fetchJoobleResultsCached: redis get failed: ' . $e->getMessage());
            $cached = null;
        }

        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $result = $this->fetchJoobleResults($q, $lokasi, $page, $pageSize);

        if (!empty($result['items'])) {
            try {
                // **Cache changed to 12 hours**
                $cache->put($cacheKey, $result, now()->addHours(12));
            } catch (\Throwable $e) {
                Log::warning('fetchJoobleResultsCached: failed caching: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Direct Jooble call (stateless). Returns normalized array: ['items'=>[], 'total'=>int]
     * Uses POST https://jooble.org/api/{apiKey}
     */
    protected function fetchJoobleResults(string $q, string $lokasi, int $page = 1, int $pageSize = 20): array
    {
        $apiKey = env('JOOBLE_API_KEY', null);
        if (empty($apiKey)) {
            Log::warning('Jooble API key not configured (JOOBLE_API_KEY). Aborting external fetch.');
            return ['items' => [], 'total' => 0];
        }

        $endpoint = "https://jooble.org/api/{$apiKey}";

        // Use provided lokasi; if empty, use 'Indonesia'
        $locForApi = trim($lokasi) !== '' ? $lokasi : 'Indonesia';

        $payload = [
            'keywords'     => $q ?: '',
            'location'     => $locForApi,
            'radius'       => '80',
            'page'         => (string)$page,
            'ResultOnPage' => (string)$pageSize,
            'SearchMode'   => '0',
            'companysearch'=> 'false',
        ];

        try {
            $res = Http::timeout(12)->acceptJson()->post($endpoint, $payload);
        } catch (\Throwable $e) {
            Log::warning('fetchJoobleResults: Jooble API request failed: ' . $e->getMessage(), ['q' => $q, 'lokasi' => $lokasi]);
            return ['items' => [], 'total' => 0];
        }

        if (!$res->ok()) {
            Log::warning('fetchJoobleResults: Jooble API non-200: ' . $res->status(), ['q' => $q, 'lokasi' => $lokasi]);
            return ['items' => [], 'total' => 0];
        }

        $resp = $res->json();
        if (!is_array($resp) || empty($resp)) {
            return ['items' => [], 'total' => 0];
        }

        $jobsRaw = $resp['jobs'] ?? [];
        $totalHits = is_numeric($resp['totalCount'] ?? null) ? (int)$resp['totalCount'] : count($jobsRaw);

        $items = [];
        $count = 0;
        foreach ($jobsRaw as $j) {
            if ($count >= $pageSize) {
                break;
            }

            $title = trim($j['title'] ?? $j['position'] ?? 'No title');
            $company = $j['company'] ?? null;
            $location = $j['location'] ?? ($j['locations'] ?? null);
            $applyUrl = $j['link'] ?? ($j['url'] ?? null);
            $dateRaw = $j['updated'] ?? ($j['date'] ?? null);
            $snippet = $j['snippet'] ?? null;

            $datePosted = null;
            if (!empty($dateRaw)) {
                try {
                    $datePosted = Carbon::parse($dateRaw)->toDateTimeString();
                } catch (\Throwable $e) {
                    $datePosted = null;
                }
            }

            $items[] = [
                'title'       => $title,
                'company'     => $company,
                'location'    => $location,
                'apply_url'   => $applyUrl,
                'url'         => $applyUrl,
                'description' => $snippet ? strip_tags($snippet) : null,
                'date_posted' => $datePosted,
                'source'      => 'jooble',
                'raw'         => $j,
            ];
            $count++;
        }

        return ['items' => $items, 'total' => $totalHits];
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

    private function loadProfanityList(): array
    {
        static $list = null;
        if ($list !== null) {
            return $list;
        }

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

        $list = array_values(array_filter(array_map(function ($w) {
            if (!is_string($w)) {
                return null;
            }
            $v = trim(mb_strtolower($w, 'UTF-8'));
            return $v === '' ? null : $v;
        }, $arr)));

        return $list;
    }

    private function normalizeForProfanity(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, ['0' => 'o', '1' => 'i', '3' => 'e', '@' => 'a', '4' => 'a', '$' => 's', '5' => 's', '7' => 't']);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function containsProfanity(?string $text): bool
    {
        if (!$text) {
            return false;
        }

        $textNorm = ' ' . $this->normalizeForProfanity($text) . ' ';
        $words = $this->loadProfanityList();
        if (empty($words)) {
            return false;
        }

        foreach ($words as $bad) {
            if ($bad === '') {
                continue;
            }

            $pattern = '/\b' . preg_quote($bad, '/') . '\b/iu';
            if (preg_match($pattern, $textNorm)) {
                return true;
            }

            if (mb_strpos($textNorm, ' ' . $bad . ' ') !== false) {
                return true;
            }
        }

        return false;
    }

    private function cleanAndSlug(?string $text, string $type = 'q'): string
    {
        if (!$text) {
            return '';
        }

        if ($this->containsProfanity($text)) {
            return $type === 'lokasi' ? 'jakarta' : 'sales';
        }

        return Str::slug($text, '-');
    }
}

