<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Services\TheirStackClient;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImportTheirStackJobs extends Command
{
    protected $signature = 'theirstack:import
        {--q=remote : Kata kunci pencarian}
        {--country=ID : Kode negara}
        {--pages=1 : Jumlah halaman (loop berbasis 0)}
        {--per-page=20 : Jumlah job per halaman (limit)}
        {--avoid=auto : Strategi menghindari duplikat: auto|discovered_at|job_id_not}
        {--exclude-limit=1000 : Batas jumlah id untuk job_id_not}
        {--debug : Tampilkan debug keys dari tiap job}
        {--posted-at-max-age-days= : Posted At Max Age Days (nullable). If 0 => only today; 1 => today + yesterday; etc.}';

    protected $description = 'Import job posting dari TheirStack API (mapping ke schema yang konsisten)';

    public function handle(TheirStackClient $api)
    {
        $pages = (int) $this->option('pages');
        $limit = (int) $this->option('per-page');
        $avoidStrategy = $this->option('avoid');
        $excludeLimit = (int) $this->option('exclude-limit');
        $debug = (bool) $this->option('debug');

        $postedAtMaxAgeOpt = $this->option('posted-at-max-age-days');
        $postedAtMaxAge = null;
        $earliestPostedDate = null;
        if ($postedAtMaxAgeOpt !== null && $postedAtMaxAgeOpt !== '') {
            $postedAtMaxAge = (int)$postedAtMaxAgeOpt;
            // earliest date to accept (startOfDay)
            $earliestPostedDate = Carbon::today()->subDays($postedAtMaxAge)->startOfDay();
            $this->info("Using posted_at_max_age_days = {$postedAtMaxAge} (accept jobs posted since {$earliestPostedDate->toDateString()})");
        }

        $params = [
            'q'            => $this->option('q'),
            'country_code' => $this->option('country'),
            'remote'       => true,
        ];

        // pilih strategi avoid duplicate (discovered_at_gte atau job_id_not)
        if ($avoidStrategy === 'auto' || $avoidStrategy === 'discovered_at') {
            $latest = Job::where('source', 'theirstack')->max('discovered_at');
            if ($latest) {
                $dt = Carbon::parse($latest)->addSecond();
                $params['discovered_at_gte'] = $dt->toIso8601String();
                $this->info("Using discovered_at_gte = {$params['discovered_at_gte']}");
            } elseif ($avoidStrategy === 'discovered_at') {
                $this->warn("No discovered_at found in DB; continuing without discovered_at_gte.");
            } else {
                $avoidStrategy = 'job_id_not';
            }
        }

        if ($avoidStrategy === 'job_id_not') {
            $ids = Job::where('source', 'theirstack')
                ->whereNotNull('identifier_value')
                ->orderBy('discovered_at', 'desc')
                ->limit($excludeLimit)
                ->pluck('identifier_value')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($ids)) {
                $params['job_id_not'] = implode(',', $ids);
                $this->info('Using job_id_not (count): ' . count($ids));
            } else {
                $this->warn('No identifier_value found for job_id_not; continuing without it.');
            }
        }

        $inserted = 0;
        $skipped = 0;

        for ($page = 0; $page < $pages; $page++) {
            $data = $api->searchJobs($params, $page, $limit);
            $jobs = $data['data'] ?? $data['results'] ?? [];

            if (empty($jobs)) {
                $this->warn('Page ' . ($page + 1) . ' empty.');
                continue;
            }

            foreach ($jobs as $j) {
                // debug keys if diminta
                if ($debug) {
                    $keys = array_keys(is_array($j) ? $j : []);
                    $this->line('Job keys: ' . implode(', ', $keys));
                }

                // ---- safety: treat all as remote per your project requirement ----
                $isRemote = true;

                // MAPPING — gunakan semua alternatif yang mungkin
                $sourceId = $j['id'] ?? $j['job_id'] ?? null;

                // Title
                $title = $j['job_title'] ?? $j['title'] ?? null;

                // Company / hiringOrganization
                $company = data_get($j, 'company_name')
                        ?? data_get($j, 'company.name')
                        ?? data_get($j, 'company_object.name')
                        ?? $j['company'] ?? null;

                // Company domain (jika tersedia)
                $companyDomain = $j['company_domain'] ?? data_get($j, 'company.domain') ?? data_get($j, 'company_object.domain') ?? null;

                // Job location: try structured fields, fallback ke strings
                $jobLocation = null;
                if (!empty($j['location'])) {
                    $jobLocation = $j['location'];
                } elseif (!empty($j['long_location'])) {
                    $jobLocation = $j['long_location'];
                } elseif (!empty($j['short_location'])) {
                    $jobLocation = $j['short_location'];
                } elseif (!empty($j['locations']) && is_array($j['locations']) && !empty($j['locations'][0])) {
                    $loc = $j['locations'][0];
                    $parts = [];
                    if (!empty($loc['address'])) $parts[] = $loc['address'];
                    if (!empty($loc['city'])) $parts[] = $loc['city'];
                    if (!empty($loc['state'])) $parts[] = $loc['state'];
                    if (!empty($loc['country'])) $parts[] = $loc['country'];
                    $jobLocation = implode(', ', array_filter($parts));
                    if (empty($jobLocation) && !empty($loc['display_name'])) $jobLocation = $loc['display_name'];
                }

                // Description (raw)
                $rawDescription = $j['description'] ?? data_get($j, 'description_html') ?? null;

                // Convert raw description => sanitized HTML with **Heading** -> <h3>Heading</h3>
                $descriptionHtml = $this->convertAsterisksHeadingsToHtml($rawDescription);
                // plain text fallback
                $descriptionPlain = trim(strip_tags($descriptionHtml));

                // Apply URL / final_url
                $applyUrl = $this->pickApplyUrl($j);
                $finalUrl = $j['final_url'] ?? $j['url'] ?? null;

                // normalize source_url candidate (used for uniqueness)
                $sourceUrlCandidate = $j['source_url'] ?? $j['final_url'] ?? $j['url'] ?? null;
                $sourceUrlNormalized = $this->normalizeUrl($sourceUrlCandidate);

                // datePosted
                $datePosted = !empty($j['date_posted']) ? Carbon::parse($j['date_posted'])->toDateString() : now()->toDateString();

                // Filter posted_at_max_age_days if option provided
                // (kept as-is)
                $postedAtMaxAgeOpt = $this->option('posted-at-max-age-days');
                if ($postedAtMaxAgeOpt !== null && $postedAtMaxAgeOpt !== '') {
                    $postedAtMaxAge = (int)$postedAtMaxAgeOpt;
                    $earliestPostedDate = Carbon::today()->subDays($postedAtMaxAge)->startOfDay();
                    try {
                        $datePostedCarbon = Carbon::parse($datePosted)->startOfDay();
                        if ($datePostedCarbon->lt($earliestPostedDate)) {
                            $skipped++;
                            if ($debug) $this->line("Skipped by posted_at_max_age_days: {$title} ({$datePosted})");
                            continue;
                        }
                    } catch (\Exception $e) {
                        $skipped++;
                        if ($debug) $this->line("Skipped due to invalid date_posted: " . ($j['date_posted'] ?? 'N/A'));
                        continue;
                    }
                }

                // applicantLocationRequirements: job_country_code_or or country_codes
                $appLocReq = $j['job_country_code_or'] ?? $j['job_country_code'] ?? $j['country_code'] ?? $j['country_codes'] ?? null;
                if ($appLocReq === null) {
                    $appLocReq = [];
                } elseif (!is_array($appLocReq)) {
                    $appLocReq = [$appLocReq];
                }

                // -----------------------------
                // SALARY extraction (priority + fallback)
                // -----------------------------
                // Prefer explicit salary fields from TheirStack
                $salaryString = $j['salary_string'] ?? $j['salary_str'] ?? $j['salary'] ?? null;
                $minAnnualRaw = $j['min_annual_salary'] ?? $j['min_annual_salary_usd'] ?? $j['min_salary'] ?? null;
                $maxAnnualRaw = $j['max_annual_salary'] ?? $j['max_annual_salary_usd'] ?? $j['max_salary'] ?? null;
                $salaryCurrency = $j['salary_currency'] ?? $j['currency'] ?? null;

                // Normalize numeric min/max if present (ensure floats/null)
                $baseMin = is_numeric($minAnnualRaw) ? (float)$minAnnualRaw : null;
                $baseMax = is_numeric($maxAnnualRaw) ? (float)$maxAnnualRaw : null;

                // Assume annual if numeric fields exist
                $salaryUnit = 'YEAR';

                // If salary_string exists but min/max numeric missing, try parse numbers from the string
                if ((empty($baseMin) && empty($baseMax)) && !empty($salaryString)) {
                    $s = $salaryString;
                    // normalize
                    $s_norm = preg_replace('/[^\d\-\.,kK ]+/', ' ', $s);
                    $s_norm = preg_replace('/[–—−]/u', '-', $s_norm);
                    if (preg_match_all('/(\d{1,3}(?:[,\.\d]{0,})\s*[kK]?)/', $s_norm, $m) && count($m[1]) >= 1) {
                        $parseNumber = function($t){
                            $t = trim($t);
                            $isK = false;
                            if ($t === '') return null;
                            if (str_ends_with(strtolower($t), 'k')) { $isK = true; $t = substr($t, 0, -1); }
                            $t = preg_replace('/[,\.\s]/','', $t);
                            if ($t === '' || !is_numeric($t)) return null;
                            $v = (float)$t;
                            if ($isK) $v *= 1000;
                            return $v;
                        };
                        $n0 = $parseNumber($m[1][0]);
                        $n1 = isset($m[1][1]) ? $parseNumber($m[1][1]) : null;
                        if ($n0 !== null) $baseMin = $n0;
                        if ($n1 !== null) $baseMax = $n1;
                    }
                }

                // If still no salary info, fallback to Indonesian UMR monthly range
                if (empty($salaryString) && empty($baseMin) && empty($baseMax)) {
                    $salaryUnit = 'MONTH';
                    $salaryCurrency = 'IDR';
                    $baseMin = 2000000.00;
                    $baseMax = 10000000.00;
                    $salaryString = 'IDR ' . number_format($baseMin, 0, ',', '.') . ' - IDR ' . number_format($baseMax, 0, ',', '.');
                } else {
                    // make sure salaryString exists (compose if only numeric present)
                    if (empty($salaryString) && ($baseMin || $baseMax)) {
                        $cur = $salaryCurrency ?? 'USD';
                        if ($baseMin && $baseMax) {
                            $salaryString = $cur . ' ' . number_format($baseMin) . ' - ' . $cur . ' ' . number_format($baseMax);
                        } elseif ($baseMin) {
                            $salaryString = $cur . ' ' . number_format($baseMin);
                        }
                    }
                    // Keep currency default
                    if (empty($salaryCurrency)) {
                        $salaryCurrency = $j['salary_currency'] ?? $j['currency'] ?? null;
                    }
                }

                // ensure numeric types
                $baseMin = is_numeric($baseMin) ? (float)$baseMin : null;
                $baseMax = is_numeric($baseMax) ? (float)$baseMax : null;

                // employmentType
                $employmentStatuses = $j['employment_statuses'] ?? $j['employment_type'] ?? $j['employment'] ?? null;
                if (is_array($employmentStatuses)) {
                    $employmentTypeFirst = $employmentStatuses[0] ?? null;
                    $employmentTypeRaw = json_encode($employmentStatuses);
                } else {
                    $employmentTypeFirst = $employmentStatuses;
                    $employmentTypeRaw = is_null($employmentStatuses) ? null : json_encode([$employmentStatuses]);
                }
                $employmentType = $employmentTypeFirst ?? null;

                // directApply
                $directApply = (bool)($j['direct_apply'] ?? $j['directApply'] ?? false);

                // jobLocationType
                $jobLocationType = 'Remote'; // all remote

                // validThrough: datePosted + 45 (remote)
                $validThrough = Carbon::parse($datePosted)->addDays(45)->toDateString();

                // fingerprint (title|company|location|datePosted)
                $fp = $this->makeFingerprint($title, $company, $jobLocation, $datePosted);

                // Prepare uniqueness key:
                // Prefer uniqueness on source + source_url (normalized). If source_url missing, fallback to fingerprint.
                $uniqueKey = ['source' => 'theirstack'];
                if (!empty($sourceUrlNormalized)) {
                    $uniqueKey['source_url'] = $sourceUrlNormalized;
                } else {
                    $uniqueKey['fingerprint'] = $fp;
                }

                // Save/update
                $values = [
                    'title'                           => $title,
                    'company'                         => $company,
                    'company_domain'                  => $companyDomain,
                    'location'                        => $jobLocation,
                    'is_remote'                       => true,
                    'description'                     => $descriptionPlain,
                    'description_html'                => $descriptionHtml,
                    'apply_url'                       => $applyUrl,
                    'final_url'                       => $finalUrl,
                    'source_url'                      => $sourceUrlNormalized,
                    'url_source'                      => $j['url_source'] ?? data_get($j, 'company_object.url_source'),
                    'date_posted'                     => $datePosted,
                    'hiring_organization'             => $company,
                    'job_location'                    => $jobLocation,
                    'applicant_location_requirements' => json_encode($appLocReq),
                    'base_salary_min'                 => $baseMin,
                    'base_salary_max'                 => $baseMax,
                    'base_salary_currency'            => $salaryCurrency,
                    'base_salary_unit'                => $salaryUnit,
                    'base_salary_string'              => $salaryString,
                    'direct_apply'                    => $directApply,
                    'employment_type'                 => $employmentType,
                    'employment_type_raw'             => $employmentTypeRaw,
                    'identifier_name'                 => 'id',
                    'identifier_value'                => $sourceId ? (string)$sourceId : null,
                    'job_location_type'               => $jobLocationType,
                    'valid_through'                   => $validThrough,
                    'source'                          => 'theirstack',
                    'discovered_at'                   => !empty($j['discovered_at']) ? Carbon::parse($j['discovered_at']) : now(),
                    'raw'                             => json_encode($j),
                    'fingerprint'                     => $fp,
                ];

                try {
                    $job = Job::updateOrCreate($uniqueKey, $values);
                    if ($job->wasRecentlyCreated) {
                        $inserted++;
                    } else {
                        // Not a new insert — we could count updates separately if desired
                        if ($debug) {
                            $sourceUrlForLog = isset($uniqueKey['source_url']) ? $uniqueKey['source_url'] : 'by-fp';
                            $this->line("Updated existing job: {$job->id} ({$sourceUrlForLog})");
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn("Failed to save job (skipped): " . ($title ?? 'n/a') . " — " . $e->getMessage());
                    $skipped++;
                    continue;
                }
            }

            $this->info('Page ' . ($page + 1) . ": inserted={$inserted}, skipped={$skipped}");
        }

        $this->info('✅ Import finished! Total inserted: ' . $inserted . ' (skipped non-remote/filtered: ' . $skipped . ')');
        return self::SUCCESS;
    }

    // helpers
    private function makeFingerprint($title, $company, $location, $date)
    {
        $plain = $this->normalize($title) . '|' . $this->normalize($company) . '|' . $this->normalize($location) . '|' . $this->normalize($date);
        return hash('sha256', $plain);
    }

    private function normalize($s)
    {
        if ($s === null) return '';
        $s = (string) $s;
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = preg_replace('/[^a-z0-9 ]/i', '', $s);
        return trim(Str::lower($s));
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (empty($url)) return null;
        $url = trim($url);
        if ($url === '') return null;
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        // optionally remove UTM and fragments for better dedupe
        $parts = parse_url($url);
        if ($parts === false) return $url;
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';
        // remove utm params if present
        if ($query) {
            parse_str($query, $qp);
            foreach ($qp as $k => $v) {
                if (stripos($k, 'utm_') === 0) unset($qp[$k]);
            }
            $query = http_build_query($qp);
        }
        $normalized = $scheme . '://' . $host . $path . ($query ? '?' . $query : '');
        return $normalized;
    }

    private function pickApplyUrl(array $item): ?string
    {
        $candidates = [
            $item['final_url'] ?? null,
            $item['source_url'] ?? null,
            $item['url_source'] ?? null,
            data_get($item, 'company_object.url_source'),
            $item['url'] ?? null,
        ];

        foreach ($candidates as $u) {
            if (!empty($u)) return $this->normalizeUrl($u);
        }

        $firstLink = data_get($item, 'links.0.href') ?? data_get($item, 'links.0.url');
        if (!empty($firstLink)) return $this->normalizeUrl($firstLink);

        $htmlCandidates = [
            data_get($item, 'description'),
            data_get($item, 'description_html'),
            data_get($item, 'how_to_apply'),
        ];
        foreach ($htmlCandidates as $html) {
            if (empty($html)) continue;
            if (preg_match('/href=["\']([^"\']+)["\']/i', $html, $m)) {
                return $this->normalizeUrl($m[1]);
            }
            if (preg_match('/https?:\/\/[^\s)"]+/i', $html, $m2)) {
                return $this->normalizeUrl($m2[0]);
            }
        }

        return null;
    }

    /**
     * Convert semua **text** menjadi <b>text</b>,
     * dan wrap ke dalam paragraf yang rapi tanpa h3.
     * Sanitize untuk keamanan HTML.
     */
    private function convertAsterisksHeadingsToHtml(?string $text): string
    {
        if (empty($text)) return '';

        // Decode HTML entities (jika ada)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        // Normalisasi baris
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Ubah semua **kata** (termasuk baris tunggal) jadi <b>kata</b>
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $text);

        // Pecah jadi paragraf per dua baris kosong
        $blocks = preg_split("/\n{2,}/", $text);
        $out = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;

            // Escape dan ubah newline tunggal jadi <br>
            $escaped = nl2br($block);

            // Pastikan aman (hapus tag selain yang diizinkan)
            $allowed = '<p><br><ul><ol><li><strong><em><b><i><a>';
            $clean = strip_tags($escaped, $allowed);

            // Bungkus jadi paragraf
            $out[] = "<p>{$clean}</p>";
        }

        $html = implode("\n", $out);

        // Sanitasi link
        $html = preg_replace_callback('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i', function ($m) {
            $href = trim($m[1]);
            $text = $m[2];
            if (preg_match('/^\s*javascript:/i', $href)) {
                return $text;
            }
            $href = e($href);
            return '<a href="' . $href . '" rel="nofollow noopener" target="_blank">' . $text . '</a>';
        }, $html);

        return $html;
    }
}

