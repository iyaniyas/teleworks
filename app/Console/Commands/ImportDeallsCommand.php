<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ImportDeallsCommand
 *
 * Import jobs from Dealls (Next.js). Features:
 *  - Accepts pre-rendered HTML via --rendered=/path/to/file
 *  - Parses __NEXT_DATA__ if present
 *  - Scans <script> tags to find JSON containing "docs" or "explore-job/job"
 *  - Filters non-job UI slugs (saved, applied)
 */
class ImportDeallsCommand extends Command
{
    protected $signature = 'jobs:import:dealls 
        {--pages=1} 
        {--rate=2} 
        {--limit=0}
        {--rendered=}
    ';

    protected $description = 'Import jobs from Dealls.com (Next.js scraper with resilient parsing)';

    protected string $listingUrl = 'https://dealls.com/?location=remote&sortParam=publishedAt';
    protected string $userAgent = 'TeleworksBot/1.0; (+https://teleworks.id)';

    public function handle()
    {
        $pages = (int) $this->option('pages');
        $rate = (int) $this->option('rate');
        $limit = (int) $this->option('limit');
        $renderedPath = $this->option('rendered');

        $this->info("Starting dealls importer — pages={$pages} rate={$rate}s limit={$limit} rendered={$renderedPath}");

        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        // If rendered HTML provided, parse it once
        if ($renderedPath && file_exists($renderedPath)) {
            $this->info("Reading rendered HTML from {$renderedPath}");
            $html = file_get_contents($renderedPath);

            $items = $this->parseListing($html);

            if (empty($items)) {
                $this->error("No items found in rendered HTML.");
                return 0;
            }

            foreach ($items as $card) {
                if ($limit > 0 && $processed >= $limit) break;

                try {
                    $record = $this->buildRecordFromCard($card);
                } catch (\Throwable $e) {
                    $this->error("Failed building record: " . $e->getMessage());
                    $skipped++; $processed++;
                    continue;
                }

                try {
                    $result = $this->upsertRecord($record);
                    if ($result === 'created') $created++;
                    elseif ($result === 'updated') $updated++;
                    else $skipped++;
                } catch (\Throwable $e) {
                    $this->error("Upsert failed: " . $e->getMessage());
                    $skipped++;
                }

                $processed++;
            }

            $this->info("Done. processed={$processed} created={$created} updated={$updated} skipped={$skipped}");
            return 0;
        }

        // Live fetch mode (best-effort; likely empty without JS rendering)
        for ($p = 1; $p <= $pages; $p++) {
            $url = $this->listingUrl . '&page=' . $p;
            $this->info("Fetching listing page: {$url}");

            try {
                $res = Http::withHeaders([
                    'User-Agent' => $this->userAgent,
                ])->timeout(30)->get($url);
            } catch (\Throwable $e) {
                $this->error("Failed fetching listing: " . $e->getMessage());
                sleep($rate);
                continue;
            }

            $html = $res->body();
            $items = $this->parseListing($html);

            if (empty($items)) {
                $savePath = storage_path('logs/dealls-listing-page-failed-' . now()->format('YmdHis') . '.html');
                file_put_contents($savePath, $html);
                $this->warn("No items found on page {$p}. Saved HTML to {$savePath}");
                sleep($rate);
                continue;
            }

            foreach ($items as $card) {
                if ($limit > 0 && $processed >= $limit) break 2;

                try {
                    $record = $this->buildRecordFromCard($card);
                } catch (\Throwable $e) {
                    $this->error("Record build failed: " . $e->getMessage());
                    $skipped++; $processed++;
                    continue;
                }

                try {
                    $result = $this->upsertRecord($record);
                    if ($result === 'created') $created++;
                    elseif ($result === 'updated') $updated++;
                    else $skipped++;
                } catch (\Throwable $e) {
                    $this->error("Upsert failed: " . $e->getMessage());
                    $skipped++;
                }

                $processed++;
                sleep($rate);
            }

            sleep($rate);
        }

        $this->info("Done. processed={$processed} created={$created} updated={$updated} skipped={$skipped}");
        return 0;
    }

    /**
     * Robust parse listing:
     * - attempt __NEXT_DATA__ id or other script tags
     * - locate JSON containing 'docs' or 'explore-job/job'
     * - map docs via mapDocToCard()
     */
    protected function parseListing(string $html): array
    {
        $items = [];

        // 1) Try __NEXT_DATA__ by id or window.__NEXT_DATA__
        if (preg_match('/<script[^>]*id=["\']?__NEXT_DATA__["\']?[^>]*>(.*?)<\/script>/is', $html, $m)) {
            $json = trim($m[1]);
            $decoded = json_decode(html_entity_decode($json), true);
            if (is_array($decoded)) {
                $docs = $this->recursiveFindKey($decoded, 'docs');
                if (is_array($docs)) {
                    foreach ($docs as $doc) {
                        if ($card = $this->mapDocToCard($doc)) $items[] = $card;
                    }
                    return $this->uniqueByUrl($items);
                }
            }
        }

        // 2) Try window.__NEXT_DATA__ assignment (some pages embed differently)
        if (preg_match('/window\.__NEXT_DATA__\s*=\s*(\{.+?\})\s*;/is', $html, $m2)) {
            $json = $m2[1];
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $docs = $this->recursiveFindKey($decoded, 'docs');
                if (is_array($docs)) {
                    foreach ($docs as $doc) {
                        if ($card = $this->mapDocToCard($doc)) $items[] = $card;
                    }
                    return $this->uniqueByUrl($items);
                }
            }
        }

        // 3) Scan all <script> tags heuristically
        if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $scripts)) {
            foreach ($scripts[1] as $scriptContent) {
                $scriptContent = trim($scriptContent);
                if (empty($scriptContent)) continue;

                // quick filter
                if (strlen($scriptContent) < 80) continue;

                // if script contains markers it's worth extraction
                if (strpos($scriptContent, 'explore-job/job') !== false || strpos($scriptContent, '"docs"') !== false) {
                    // try to extract large JSON substring between first { and last }
                    $first = strpos($scriptContent, '{');
                    $last = strrpos($scriptContent, '}');
                    if ($first !== false && $last !== false && $last > $first) {
                        $candidate = substr($scriptContent, $first, $last - $first + 1);
                        $decoded = json_decode($candidate, true);
                        if (is_array($decoded)) {
                            $docs = $this->recursiveFindKey($decoded, 'docs');
                            if (is_array($docs)) {
                                foreach ($docs as $doc) {
                                    if ($card = $this->mapDocToCard($doc)) $items[] = $card;
                                }
                                // continue scanning for more runs
                                continue;
                            }
                        }

                        // fallback: try to decode the whole script content
                        $decoded2 = json_decode($scriptContent, true);
                        if (is_array($decoded2)) {
                            $docs = $this->recursiveFindKey($decoded2, 'docs');
                            if (is_array($docs)) {
                                foreach ($docs as $doc) {
                                    if ($card = $this->mapDocToCard($doc)) $items[] = $card;
                                }
                                continue;
                            }
                        }
                    }
                } else {
                    // try generic decode (script could be JSON)
                    $decoded = json_decode($scriptContent, true);
                    if (is_array($decoded)) {
                        $docs = $this->recursiveFindKey($decoded, 'docs');
                        if (is_array($docs)) {
                            foreach ($docs as $doc) {
                                if ($card = $this->mapDocToCard($doc)) $items[] = $card;
                            }
                        }
                    }
                }
            }
        }

        return $this->uniqueByUrl($items);
    }

    /**
     * Map a Dealls doc JSON to a card usable by importer.
     * Filters UI slugs (saved/applied) and requires title/status check.
     */
    protected function mapDocToCard($doc)
    {
        if (!is_array($doc)) return null;

        $slug = $doc['slug'] ?? null;
        $id = $doc['id'] ?? null;
        $title = $doc['role'] ?? ($doc['title'] ?? null);
        $publishedAt = $doc['publishedAt'] ?? ($doc['createdAt'] ?? null);
        $status = $doc['status'] ?? null;

        // Skip non-job UI routes
        $badSlugs = ['saved', 'applied', 'saved-list', 'applied-list'];
        if ($slug && in_array(strtolower($slug), $badSlugs, true)) {
            return null;
        }

        // Title required
        if (empty($title)) return null;

        // If status provided, require active
        if ($status !== null && strtolower((string)$status) !== 'active') return null;

        // Validate slug shape; fallback to id if slug looks invalid
        if ($slug && !preg_match('/[A-Za-z0-9\-~_]+/', $slug)) {
            if (empty($id) || !preg_match('/^[0-9a-fA-F\-]+$/', (string)$id)) {
                return null;
            }
        }

        // Build detail url
        $detailUrl = null;
        if ($slug && !in_array(strtolower($slug), $badSlugs, true)) {
            $detailUrl = 'https://dealls.com/loker/' . ltrim($slug, '/');
        } elseif ($id) {
            $detailUrl = 'https://dealls.com/loker/' . $id;
        } else {
            return null;
        }

        return [
            'title' => $title,
            'detail_url' => $detailUrl,
            'snippet' => $doc['summary'] ?? null,
            'date_raw' => $publishedAt,
            'raw_doc' => $doc,
        ];
    }

protected function buildRecordFromCard(array $card): array
{
    $raw = $card['raw_doc'] ?? [];

    $identifier = $raw['id'] ?? ($card['detail_url'] ?? null);
    $company = $raw['company']['name'] ?? ($raw['company'] ?? null);
    $location = $raw['city'] ?? ($raw['country'] ?? null) ?: 'Remote';
    $isRemote = (isset($raw['workplaceType']) && strtolower($raw['workplaceType']) === 'remote') ? 1 : 0;

    // posted date
    $datePosted = null;
    if (!empty($card['date_raw'])) {
        try {
            $datePosted = Carbon::parse($card['date_raw'])->toDateString();
        } catch (\Throwable $e) {
            $datePosted = Carbon::now()->toDateString();
        }
    } else {
        $datePosted = Carbon::now()->toDateString();
    }

    // salary
    $salaryMin = $raw['salaryRange']['start'] ?? null;
    $salaryMax = $raw['salaryRange']['end'] ?? null;
    $salaryText = $salaryMin ? ($salaryMin . ($salaryMax ? " - {$salaryMax}" : '')) : 'Perkiraan gaji';

    // employment types
    $employmentTypes = $raw['employmentTypes'] ?? ($raw['employmentType'] ? [$raw['employmentType']] : []);

    // applicant_location_requirements must be valid JSON (because of CHECK(json_valid(...)))
    // Try to use any available field (candidatePreference.region, applicantLocationRequirements, etc.)
    $applicantReqValue = null;
    if (!empty($raw['candidatePreference']['region'])) {
        $applicantReqValue = $raw['candidatePreference']['region'];
    } elseif (!empty($raw['applicantLocationRequirements'])) {
        $applicantReqValue = $raw['applicantLocationRequirements'];
    } elseif (!empty($raw['candidatePreference']['country'])) {
        $applicantReqValue = $raw['candidatePreference']['country'];
    }

    // Normalize into JSON string (array preferred)
    if ($applicantReqValue === null) {
        $applicantReqJson = json_encode(['Indonesia'], JSON_UNESCAPED_UNICODE);
    } else {
        if (is_array($applicantReqValue)) {
            // ensure values are simple array (no nested objects)
            $flat = array_values($applicantReqValue);
            $applicantReqJson = json_encode($flat, JSON_UNESCAPED_UNICODE);
        } else {
            // single scalar -> make array with one element
            $applicantReqJson = json_encode([ (string) $applicantReqValue ], JSON_UNESCAPED_UNICODE);
        }
    }

    $fingerprint = sha1(json_encode($raw));

    $record = [
        'title' => $card['title'],
        'description' => $card['snippet'] ?? '',
        'description_html' => $card['snippet'] ?? '',
        'company' => $company,
        'location' => $location,
        'type' => is_array($employmentTypes) ? implode(',', $employmentTypes) : ($employmentTypes ?: null),
        'is_wfh' => $isRemote ? 1 : 0,
        'search' => null,
        'source_url' => $card['detail_url'],
        'final_url' => $card['detail_url'],
        'url_source' => 'dealls',
        'raw_html' => null,
        'is_imported' => 1,
        'import_hash' => null,
        'status' => 'published',
        'expires_at' => null,
        'discovered_at' => Carbon::now()->toDateTimeString(),
        'posted_at' => ($datePosted ? $datePosted . ' 00:00:00' : null),
        'source' => 'dealls',
        'date_posted' => $datePosted,
        'hiring_organization' => $company,
        'job_location' => $location,
        // <-- IMPORTANT: JSON string value for applicant_location_requirements
        'applicant_location_requirements' => $applicantReqJson,
        'base_salary_min' => $salaryMin,
        'base_salary_max' => $salaryMax,
        'base_salary_currency' => $raw['salaryCurrency'] ?? null,
        'base_salary_unit' => null,
        'base_salary_string' => $salaryText,
        'direct_apply' => empty($raw['externalPlatformApplyUrl']) ? 1 : 0,
        'employment_type' => is_array($employmentTypes) ? implode(',', $employmentTypes) : $employmentTypes,
        'employment_type_raw' => json_encode($employmentTypes),
        'identifier_name' => 'job_id',
        'identifier_value' => $identifier,
        'job_location_type' => $isRemote ? 'REMOTE' : 'ONSITE',
        'valid_through' => Carbon::parse($datePosted)->addDays(45)->toDateString(),
        'is_remote' => $isRemote ? 1 : 0,
        'apply_url' => $raw['externalPlatformApplyUrl'] ?? $card['detail_url'],
        'easy_apply' => empty($raw['externalPlatformApplyUrl']) ? 1 : 0,
        'raw' => json_encode($raw, JSON_UNESCAPED_UNICODE),
        'fingerprint' => $fingerprint,
    ];

    return $record;
}



    /**
     * Upsert into jobs table. Returns 'created'|'updated'|'skipped'.
     */
    protected function upsertRecord(array $r)
    {
        $existing = DB::table('jobs')
            ->where('source', 'dealls')
            ->where('identifier_value', $r['identifier_value'])
            ->first();

        if ($existing) {
            // if unchanged, skip
            if (isset($existing->fingerprint) && $existing->fingerprint === ($r['fingerprint'] ?? null)) {
                return 'skipped';
            }

            // update a subset of columns
            DB::table('jobs')->where('id', $existing->id)->update([
                'title' => $r['title'],
                'description' => $r['description'],
                'description_html' => $r['description_html'],
                'company' => $r['company'],
                'location' => $r['location'],
                'type' => $r['type'],
                'is_wfh' => $r['is_wfh'],
                'raw' => $r['raw'],
                'fingerprint' => $r['fingerprint'],
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]);

            return 'updated';
        }

        // Insert: keep only columns that exist in table
        $columnsRaw = DB::select("SHOW COLUMNS FROM jobs");
        $columns = array_map(function ($c) { return $c->Field ?? ($c->COLUMN_NAME ?? null); }, $columnsRaw);
        $safe = [];
        foreach ($r as $k => $v) {
            if (in_array($k, $columns, true)) $safe[$k] = $v;
        }
        $safe['created_at'] = Carbon::now()->toDateTimeString();
        $safe['updated_at'] = Carbon::now()->toDateTimeString();

        $id = DB::table('jobs')->insertGetId($safe);
        \Log::info('Inserted job', ['id' => $id, 'identifier' => $r['identifier_value']]);

        return 'created';
    }

    /**
     * Recursively find the first occurrence of a key inside nested arrays/objects.
     */
    protected function recursiveFindKey($data, string $key)
    {
        if (is_array($data)) {
            if (array_key_exists($key, $data)) return $data[$key];
            foreach ($data as $v) {
                $found = $this->recursiveFindKey($v, $key);
                if ($found !== null) return $found;
            }
        }
        return null;
    }

    /**
     * Deduplicate by detail_url
     */
    protected function uniqueByUrl(array $items): array
    {
        $seen = [];
        $out = [];
        foreach ($items as $it) {
            $url = $it['detail_url'] ?? null;
            if (!$url) continue;
            if (isset($seen[$url])) continue;
            $seen[$url] = true;
            $out[] = $it;
        }
        return $out;
    }
}

