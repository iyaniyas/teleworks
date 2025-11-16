<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Job;
use Exception;
use Illuminate\Support\Facades\DB;
use Purifier;

class ImportJsaJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example:
     * php artisan jsa:import --q="remote" --location="indonesia" --results=10 --sites="linkedin,indeed" --hours=72 --limit=50
     */
    protected $signature = 'jsa:import
        {--q=remote : Kata kunci pencarian}
        {--location= : Lokasi (mis. indonesia)}
        {--results=10 : results_wanted per panggilan}
        {--sites=linkedin,indeed,glassdoor,zip_recruiter : comma separated site names}
        {--hours=72 : hours_old (berapa jam terakhir)}
        {--limit=100 : maximal total jobs to process (safety)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import jobs from Jobs Search API (RapidAPI - jobs-search-api.p.rapidapi.com)';

    protected $serviceConfig = null;

    public function __construct()
    {
        parent::__construct();

        // read config (put these in config/services.php or .env)
        $this->serviceConfig = [
            'base' => config('services.jsa.base', env('JSA_BASE', 'https://jobs-search-api.p.rapidapi.com/getjobs')),
            'key'  => config('services.jsa.key', env('JSA_KEY')),
            'host' => config('services.jsa.host', env('JSA_HOST', 'jobs-search-api.p.rapidapi.com')),
        ];
    }

    public function handle()
    {
        $q = $this->option('q');
        $location = $this->option('location') ?: '';
        $resultsWanted = (int)$this->option('results') ?: 10;
        $sites = array_filter(array_map('trim', explode(',', $this->option('sites'))));
        $hoursOld = (int)$this->option('hours') ?: 72;
        $limit = (int)$this->option('limit') ?: 100;

        $this->info("JSA import start — q={$q} location={$location} results={$resultsWanted} sites=".implode(',', $sites)." hours={$hoursOld} limit={$limit}");

        if (empty($this->serviceConfig['key'])) {
            $this->error("Missing JSA API key (config('services.jsa.key') or env JSA_KEY). Aborting.");
            return 1;
        }

        $payload = [
            'search_term' => $q,
            'location' => $location,
            'results_wanted' => $resultsWanted,
            'site_name' => $sites,
            'distance' => 50,
            'job_type' => 'fulltime',
            'is_remote' => true,
            'linkedin_fetch_description' => true,
            'hours_old' => $hoursOld,
        ];

        // perform request
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-rapidapi-host' => $this->serviceConfig['host'],
                'x-rapidapi-key' => $this->serviceConfig['key'],
            ])->post($this->serviceConfig['base'], $payload);
        } catch (Exception $e) {
            Log::error('JSA request failed: '.$e->getMessage(), ['exception' => $e]);
            $this->error('HTTP request failed: '.$e->getMessage());
            return 1;
        }

        if (!$response->ok()) {
            $this->error('JSA API response error: HTTP '.$response->status());
            Log::error('JSA API response not ok', ['status' => $response->status(), 'body' => $response->body()]);
            return 1;
        }

        $resp = $response->json();

        if (empty($resp) || empty($resp['jobs']) || !is_array($resp['jobs'])) {
            $this->info('No jobs returned from JSA.');
            return 0;
        }

        $jobs = $resp['jobs'];
        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($jobs as $item) {
            if ($processed >= $limit) break;

            try {
                // map fields
                $site = isset($item['site']) ? strtolower(trim($item['site'])) : 'jsa';
                $apiId = $item['id'] ?? null;
                $title = isset($item['title']) ? trim($item['title']) : null;
                $company = isset($item['company']) ? trim($item['company']) : ($item['hiring_organization'] ?? null);
                $companyDomain = $item['company_url'] ?? ($item['company_url_direct'] ?? null);
                $jobUrl = $item['job_url'] ?? $item['job_url_direct'] ?? null;

                // -------------------------
                // DESCRIPTION formatting + sanitization
                // -------------------------
                $descriptionRaw = $item['description'] ?? null;
                if (!empty($descriptionRaw)) {

                    // Step 1 — decode
                    $decoded = html_entity_decode((string)$descriptionRaw, ENT_QUOTES | ENT_HTML5);

                    // Step 2 — format into HTML (headings, lists, bold, paragraphs)
                    $formattedHtml = $this->formatDescriptionForHtml($decoded);

                    // Step 3 — defensive pre-clean: remove on* and style attributes (defense-in-depth)
                    $formattedHtml = preg_replace('/\s(on[a-z]+\s*=\s*(["\']).*?\2)/iu', ' ', $formattedHtml);
                    $formattedHtml = preg_replace('/\s(style\s*=\s*(["\']).*?\2)/iu', ' ', $formattedHtml);

                    // Step 4 — sanitize after formatting
                    try {
                        $descriptionHtml = \Purifier::clean($formattedHtml, 'teleworks');
                    } catch (\Exception $e) {
                        // fallback to default profile
                        $descriptionHtml = \Purifier::clean($formattedHtml);
                    }

                    // Step 5 — ensure links are safe (rel/target) if any remain
                    if (!empty($descriptionHtml)) {
                        $descriptionHtml = preg_replace_callback('/<a\s+([^>]+)>(.*?)<\/a>/i', function($m){
                            $attrs = $m[1];
                            $text = $m[2];
                            if (preg_match('/href=(["\'])(.*?)\\1/i', $attrs, $h)) {
                                $href = trim($h[2]);
                                if (preg_match('/^\s*(javascript:|data:)/i', $href)) {
                                    return $text;
                                }
                                $hrefEsc = e($href);
                                return '<a href="' . $hrefEsc . '" rel="nofollow noopener" target="_blank">' . $text . '</a>';
                            }
                            return $m[0];
                        }, $descriptionHtml);
                    }

                    // --- robust removal of <strong> (and encoded forms) ---
                    $descriptionHtml = (string) ($descriptionHtml ?? '');
                    // decode any remaining entities to catch encoded tags
                    $descriptionHtml = html_entity_decode($descriptionHtml, ENT_QUOTES | ENT_HTML5);
                    // remove <strong> and <b> tags (keep inner text)
                    $descriptionHtml = preg_replace('/<\/?strong\b[^>]*>/i', '', $descriptionHtml);
                    $descriptionHtml = preg_replace('/<\/?b\b[^>]*>/i', '', $descriptionHtml);
                    // remove encoded literal forms if present
                    $descriptionHtml = str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;b&gt;','&lt;/b&gt;'], ['','','',''], $descriptionHtml);

                    // re-run purifier to normalize final HTML
                    try {
                        $descriptionHtml = \Purifier::clean($descriptionHtml, 'teleworks');
                    } catch (\Exception $e) {
                        $descriptionHtml = \Purifier::clean($descriptionHtml);
                    }

                    // Step 6 — plain text fallback
                    $descriptionPlain = $descriptionHtml ? trim(strip_tags($descriptionHtml)) : trim(strip_tags($decoded));
                } else {
                    $descriptionHtml = null;
                    $descriptionPlain = null;
                }

                $locationRaw = $item['location'] ?? ($item['job_location'] ?? null);
                $datePosted = $item['date_posted'] ?? null; // often in YYYY-MM-DD
                $isRemote = (isset($item['is_remote']) && ($item['is_remote'] === true || strtolower((string)$item['is_remote']) === 'true')) ? true : false;
                $employmentType = $item['job_type'] ?? $item['employment_type'] ?? null;
                $salaryMin = $item['min_amount'] ?? null;
                $salaryMax = $item['max_amount'] ?? null;
                $salaryCurrency = $item['currency'] ?? ($item['base_salary_currency'] ?? null);
                $salaryInterval = $item['interval'] ?? ($item['base_salary_unit'] ?? null);
                $baseSalaryString = $item['salary_source'] ?? $item['base_salary_string'] ?? null;
                $directApply = isset($item['direct_apply']) ? (bool)$item['direct_apply'] : false;

                // identifier fields
                $identifierName = $site ? "{$site}_id" : 'job_id';
                $identifierValue = $apiId ? (string)$apiId : null;

                // build fingerprint (prefer final_url)
                $fingerprintSource = $jobUrl ?: ($identifierValue ?: ($title . '|' . $company . '|' . $datePosted));
                $fingerprint = $fingerprintSource ? sha1($fingerprintSource) : null;

                // normalize employment type
                $employmentTypeNormalized = null;
                if ($employmentType) {
                    $et = strtolower((string)$employmentType);
                    if (strpos($et, 'full') !== false) $employmentTypeNormalized = 'Full time';
                    elseif (strpos($et, 'part') !== false) $employmentTypeNormalized = 'Part time';
                    elseif (strpos($et, 'contract') !== false) $employmentTypeNormalized = 'Contract';
                    elseif (strpos($et, 'intern') !== false) $employmentTypeNormalized = 'Internship';
                    else $employmentTypeNormalized = Str::title(str_replace('_',' ',$et));
                } else {
                    $employmentTypeNormalized = 'Full time';
                }

                // normalize dates
                $datePostedDate = null;
                $postedAt = null;
                if (!empty($datePosted)) {
                    try {
                        $datePostedDate = Carbon::parse($datePosted)->toDateString();
                        $postedAt = Carbon::parse($datePosted)->startOfDay();
                    } catch (Exception $e) {
                        $datePostedDate = null;
                        $postedAt = null;
                    }
                }

                // valid_through: if not provided, date_posted + 45
                $validThrough = null;
                if (!empty($item['valid_through'])) {
                    try {
                        $validThrough = Carbon::parse($item['valid_through'])->toDateString();
                    } catch (Exception $e) {
                        $validThrough = null;
                    }
                }
                if (!$validThrough) {
                    if ($datePostedDate) {
                        $validThrough = Carbon::parse($datePostedDate)->addDays(45)->toDateString();
                    } else {
                        $validThrough = Carbon::now()->addDays(45)->toDateString();
                    }
                }

                // applicantLocationRequirements fallback
                $applicantLocationRequirements = $item['applicant_location_requirements'] ?? $item['applicantLocationRequirements'] ?? null;
                if (is_string($applicantLocationRequirements) && $applicantLocationRequirements !== '') {
                    // try to parse JSON or comma separated
                    $decodedArr = json_decode($applicantLocationRequirements, true);
                    if (is_array($decodedArr)) {
                        $appReqArr = $decodedArr;
                    } else {
                        $appReqArr = array_filter(array_map('trim', explode(',', $applicantLocationRequirements)));
                    }
                } elseif (is_array($applicantLocationRequirements)) {
                    $appReqArr = $applicantLocationRequirements;
                } else {
                    $appReqArr = [];
                }
                if (empty($appReqArr)) {
                    $appReqArr = ['Indonesia'];
                }

                // base salary numeric normalization
                $baseSalaryMin = null;
                $baseSalaryMax = null;
                if (!empty($salaryMin) || !empty($salaryMax)) {
                    if (!empty($salaryMin)) {
                        $baseSalaryMin = $this->normalizeSalaryNumber($salaryMin);
                    }
                    if (!empty($salaryMax)) {
                        $baseSalaryMax = $this->normalizeSalaryNumber($salaryMax);
                    }
                } elseif (!empty($baseSalaryString)) {
                    // attempt to extract a number
                    if (preg_match('/(\d{1,3}(?:[,\.\d]{0,})\s*[kK]?)/', $baseSalaryString, $m)) {
                        $num = $m[1];
                        $isK = false;
                        if (str_ends_with(strtolower($num), 'k')) { $isK = true; $num = substr($num, 0, -1); }
                        $num = preg_replace('/[,\.\s]/','', $num);
                        if (is_numeric($num)) {
                            $val = (float)$num;
                            if ($isK) $val *= 1000;
                            $baseSalaryMin = $val;
                        }
                    }
                }

                // job_location_type
                $jobLocationType = $isRemote ? 'TELECOMMUTE' : ($item['job_location_type'] ?? $item['jobLocationType'] ?? null);

                // prepare DB data array (only relevant fields)
                $data = [
                    'title' => $title ?: 'Posisi belum disebut',
                    'description' => $descriptionPlain ?: '',
                    'description_html' => $descriptionHtml ?: null,
                    'company' => $company ?: null,
                    'company_domain' => $companyDomain ?: null,
                    'location' => $locationRaw ?: null,
                    'type' => $employmentTypeNormalized,
                    'is_wfh' => $isRemote ? 1 : 0,
                    'source_url' => $jobUrl ?: null,
                    'final_url' => $jobUrl ?: null,
                    'raw_html' => null,
                    'is_imported' => 1,
                    'status' => 'published',
                    'discovered_at' => Carbon::now(),
                    'posted_at' => $postedAt,
                    'date_posted' => $datePostedDate,
                    'hiring_organization' => $company ?: null,
                    'job_location' => $locationRaw ?: null,
                    'applicant_location_requirements' => $appReqArr,
                    'base_salary_min' => $baseSalaryMin,
                    'base_salary_max' => $baseSalaryMax,
                    'base_salary_currency' => $salaryCurrency ?: null,
                    'base_salary_unit' => $salaryInterval ?: null,
                    'base_salary_string' => $baseSalaryString ?: null,
                    'direct_apply' => $directApply ? 1 : 0,
                    'employment_type' => $employmentTypeNormalized,
                    'employment_type_raw' => json_encode([
                        'job_type' => $item['job_type'] ?? null,
                        'job_level' => $item['job_level'] ?? null,
                        'job_function' => $item['job_function'] ?? null,
                    ]),
                    'identifier_name' => $identifierName,
                    'identifier_value' => $identifierValue,
                    'job_location_type' => $jobLocationType ?: ($isRemote ? 'TELECOMMUTE' : null),
                    'valid_through' => $validThrough,
                    'is_remote' => $isRemote ? 1 : 0,
                    'apply_url' => $jobUrl ?: null,
                    'easy_apply' => isset($item['easy_apply']) ? (bool)$item['easy_apply'] : 0,
                    'raw' => $item,
                    'fingerprint' => $fingerprint,
                ];

                // Dedup checks:
                $existing = null;
                if (!empty($identifierValue)) {
                    $existing = Job::where('identifier_value', $identifierValue)
                        ->where('source', $site)
                        ->first();
                }

                if (!$existing && $fingerprint) {
                    $existing = Job::where('fingerprint', $fingerprint)->first();
                }

                if ($existing) {
                    // update selected fields only (do not overwrite fields user may have edited)
                    $existing->fill(array_filter($data, function($v) { return $v !== null && $v !== ''; }));
                    // ensure source is set
                    $existing->source = $existing->source ?: $site;
                    $existing->raw = array_merge(is_array($existing->raw ?? []) ? $existing->raw : [], $item);
                    $existing->save();
                    $updated++;
                    $this->info("Updated job [{$existing->id}] {$existing->title}");
                } else {
                    // create new
                    $data['source'] = $site;
                    // ensure identifier_value exists
                    if (empty($data['identifier_value'])) {
                        // fallback to generated unique id
                        $data['identifier_value'] = 'jsa-'.Str::random(10);
                        $data['identifier_name'] = 'jsa_generated';
                    }
                    // create within DB transaction for safety
                    DB::beginTransaction();
                    try {
                        $createdJob = Job::create($data);
                        DB::commit();
                        $created++;
                        $this->info("Created job [{$createdJob->id}] {$createdJob->title}");
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('Failed creating job from JSA', ['error' => $e->getMessage(), 'data' => $data]);
                        $this->error('Failed creating job: '.$e->getMessage());
                        $skipped++;
                    }
                }

                $processed++;
            } catch (Exception $e) {
                Log::error('JSA job processing failed', ['exception' => $e, 'item' => $item]);
                $this->error('Error processing job: '.$e->getMessage());
                $skipped++;
            }
        } // end foreach

        // Summary
        $this->info("JSA import finished. Processed: {$processed} | Created: {$created} | Updated: {$updated} | Skipped: {$skipped}");
        Log::info('JSA import summary', ['processed' => $processed, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped]);

        return 0;
    }

    /**
     * Normalize salary numeric string to float (remove commas/dots and handle k)
     *
     * @param mixed $val
     * @return float|null
     */
    protected function normalizeSalaryNumber($val)
    {
        if ($val === null || $val === '') return null;
        $s = (string)$val;
        // handle "10k" or "10 K"
        $isK = false;
        if (preg_match('/k$/i', trim($s))) {
            $isK = true;
            $s = preg_replace('/k$/i', '', trim($s));
        }
        // remove non-numeric except dot and comma
        $s = preg_replace('/[^\d\.,\-]/', '', $s);
        // unify decimal separators: if both comma and dot exist, assume dot is decimal else remove commas
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            // remove commas
            $s = str_replace(',', '', $s);
        } elseif (strpos($s, ',') !== false && strpos($s, '.') === false) {
            // treat comma as decimal separator
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }
        if (!is_numeric($s)) return null;
        $num = (float)$s;
        if ($isK) $num *= 1000;
        return $num;
    }

    /**
     * Improved formatter: handles escaped chars, headings, bullets,
     * and auto-listing short/frasa lines after headings.
     */
    private function formatDescriptionForHtml(string $text): string
    {
        // normalize newlines
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = trim($text);

        // remove unwanted single-line headings like "Job Description"
        $text = preg_replace('/^(Job Description|Job description|JOB DESCRIPTION)\s*:?\s*$/mi', '', $text);

        // unescape common escapes like \| \& \- \_ and double backslashes
        $text = preg_replace(['/\\\\([|&\-_\\\\])/','/\\\\n/'], ['$1', "\n"], $text);

        // convert markdown-style bold: **text**
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

        // split into raw lines
        $lines = preg_split("/\n/", $text);

        $outBlocks = [];
        $i = 0;
        $n = count($lines);

        while ($i < $n) {
            $line = trim($lines[$i]);

            // skip empty lines
            if ($line === '') {
                $i++;
                continue;
            }

            // Heading detection: lines that look like headings (end with colon or Title Case and short)
            $isHeading = false;
            if (substr($line, -1) === ':' || preg_match('/^[A-Z][A-Za-z0-9 \'\-,]{3,}$/', $line)) {
                // ensure not a long paragraph (heuristic: heading length <= 100 and words <= 10)
                if (mb_strlen($line) <= 100 && str_word_count($line) <= 10) {
                    $isHeading = true;
                }
            }

            if ($isHeading) {
                // normalize heading text (remove trailing colon)
                $headingText = rtrim($line, ':');
                $outBlocks[] = '<p><strong>' . e($headingText) . '</strong></p>';

                // gather following short/frasa lines as list items (heuristic)
                $listItems = [];
                $j = $i + 1;
                while ($j < $n) {
                    $next = trim($lines[$j]);
                    // stop on blank line or next detected heading or long paragraph
                    if ($next === '') break;
                    // if next line is an explicit bullet, break (we'll handle separately)
                    if (preg_match('/^\*\s+/', $next)) break;
                    // heuristics for "short/frasa" lines suitable as list items:
                    // - length <= 140 chars AND
                    // - either contains few words OR contains verbs like 'Get','Work','Stay','Apply'
                    $isShort = mb_strlen($next) <= 140;
                    $wordCount = str_word_count($next);
                    $startsWithVerb = preg_match('/^(Get|Work|Stay|Apply|Discover|Upload|Use)\b/i', $next);
                    $looksLikeItem = ($isShort && ($wordCount <= 18 || $startsWithVerb));

                    if ($looksLikeItem) {
                        $listItems[] = $next;
                        $j++;
                        continue;
                    } else {
                        break;
                    }
                }

                if (!empty($listItems)) {
                    // convert to <ul><li>...</li></ul>
                    $out = ['<ul>'];
                    foreach ($listItems as $li) {
                        $out[] = '<li>' . e($li) . '</li>';
                    }
                    $out[] = '</ul>';
                    $outBlocks[] = implode("\n", $out);
                    $i = $j;
                    continue;
                } else {
                    // no list follow — just advance one line
                    $i++;
                    continue;
                }
            }

            // Explicit bullet lines starting with "* " — collect contiguous bullets
            if (preg_match('/^\*\s+(.+)$/', $line)) {
                $list = [];
                while ($i < $n && preg_match('/^\*\s+(.+)$/', trim($lines[$i]), $m)) {
                    $list[] = $m[1];
                    $i++;
                }
                $out = ['<ul>'];
                foreach ($list as $li) {
                    $out[] = '<li>' . e($li) . '</li>';
                }
                $out[] = '</ul>';
                $outBlocks[] = implode("\n", $out);
                continue;
            }

            // Lines that look like a run-on series separated by punctuation (commas, bullet char, semicolon)
            // Use \x{2022} for Unicode bullet and /u modifier
            if (preg_match('/[,\x{2022};\t]/u', $line) && mb_strlen($line) <= 500) {
                // try split by punctuation but keep meaningful groups
                $parts = preg_split('/\s{2,}|,\s+|\s?\x{2022}\s?|\;\s+/u', $line);
                $parts = array_map('trim', array_filter($parts, fn($v) => $v !== ''));
                if (count($parts) >= 2 && count($parts) <= 15) {
                    $out = ['<ul>'];
                    foreach ($parts as $p) {
                        $out[] = '<li>' . e($p) . '</li>';
                    }
                    $out[] = '</ul>';
                    $outBlocks[] = implode("\n", $out);
                    $i++;
                    continue;
                }
            }

            // default: collect consecutive normal lines into a paragraph until blank or special block
            $paraLines = [$line];
            $j = $i + 1;
            while ($j < $n) {
                $next = trim($lines[$j]);
                if ($next === '') break;
                // stop when next is heading-like or bullet
                if (substr($next, -1) === ':' || preg_match('/^\*\s+/', $next)) break;
                // heuristics: if next is very short and likely an item, stop paragraph
                if (mb_strlen($next) <= 80 && str_word_count($next) <= 6 && preg_match('/^(Get|Work|Stay|Apply|Discover|Upload|Use)\b/i', $next)) break;
                $paraLines[] = $next;
                $j++;
            }
            $paraText = implode("\n", $paraLines);
            // escape and convert single newlines to <br>
            $paraHtml = nl2br(e($paraText));
            $outBlocks[] = '<p>' . $paraHtml . '</p>';
            $i = $j;
        }

        // final join
        $html = implode("\n", $outBlocks);

        return $html;
    }
}

