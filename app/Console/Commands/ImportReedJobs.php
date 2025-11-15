<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImportReedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan reed:import --keywords="remote" --take=20
     */
    protected $signature = 'reed:import
                            {--keywords=remote : Keywords to search}
                            {--take=20 : Number of results to take (resultsToTake)}
                            {--page=1 : Page number (if needed)}
                            {--force-detail : Force fetching detail for all items even if snippet appears long}';

    protected $description = 'Import jobs from Reed.co.uk (API) with detail fetch and fallback scraping';

    // Retry config for details endpoint
    protected int $maxDetailRetries = 3;
    protected int $detailRetryBaseSleepMs = 500; // exponential backoff base (ms)

    public function handle()
    {
        $keywords = $this->option('keywords') ?? 'remote';
        $take = (int) $this->option('take') ?: 20;
        $page = (int) $this->option('page') ?: 1;
        $forceDetail = (bool) $this->option('force-detail');

        $this->info("Start Reed import — keywords={$keywords} take={$take} page={$page} forceDetail=" . ($forceDetail ? 'yes' : 'no'));

        $base = config('services.reed.base', 'https://www.reed.co.uk/api/1.0');
        $apiKey = config('services.reed.key') ?? env('REED_API_KEY');

        if (empty($apiKey)) {
            $this->error('REED API key not configured. Set REED_API_KEY in .env or services.reed.key.');
            return 1;
        }

        $searchEndpoint = rtrim($base, '/') . '/search';
        $params = [
            'keywords' => $keywords,
            'resultsToTake' => $take,
            'pageNumber' => $page,
        ];

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(30)
                ->get($searchEndpoint, $params);
        } catch (\Throwable $e) {
            Log::error('reed.import.request_error', ['message' => $e->getMessage()]);
            $this->error('Request failed: ' . $e->getMessage());
            return 1;
        }

        if (! $response->successful()) {
            $this->error("Reed API returned HTTP {$response->status()}");
            Log::warning('reed.import.http_status', ['status' => $response->status(), 'body' => $response->body()]);
            return 1;
        }

        $payload = $response->json();

        if (!is_array($payload) || !isset($payload['results'])) {
            $this->error('Unexpected API response structure.');
            Log::warning('reed.import.bad_response', ['body' => $response->body()]);
            return 1;
        }

        $results = $payload['results'];
        $total = $payload['totalResults'] ?? null;
        $this->info("Fetched " . count($results) . " results" . ($total ? " (totalResults={$total})" : ''));

        $imported = $updated = $skipped = 0;
        $detailFetched = $detailSkipped = 0;
        $scrapeAttempts = 0;
        $scrapeSuccess = 0;

        foreach ($results as $item) {
            $rawJson = $item;

            $jobId = isset($item['jobId']) ? (string) $item['jobId'] : null;
            $jobUrl = $item['jobUrl'] ?? null;
            $source = 'reed';
            $fingerprint = hash('sha256', $source . '|' . ($jobId ?? '') . '|' . ($jobUrl ?? '') . '|' . ($item['jobTitle'] ?? ''));

            // Basic fields from search result
            $title = trim($item['jobTitle'] ?? '') ?: null;
            $snippetDesc = $item['jobDescription'] ?? '';
            $descriptionText = trim(strip_tags($snippetDesc));
            $descriptionHtml = $snippetDesc ? nl2br(e($snippetDesc)) : null;
            $employer = $item['employerName'] ?? $item['employerProfileName'] ?? null;

            // parse date posted if present (Reed format d/m/Y)
            $datePosted = null;
            if (!empty($item['date'])) {
                try {
                    $datePosted = Carbon::createFromFormat('d/m/Y', $item['date'])->toDateString();
                } catch (\Exception $e) {
                    try { $datePosted = Carbon::parse($item['date'])->toDateString(); } catch (\Throwable $ee) { $datePosted = null; }
                }
            }

            // expiration from search result if present
            $validThrough = null;
            if (!empty($item['expirationDate'])) {
                try {
                    $validThrough = Carbon::createFromFormat('d/m/Y', $item['expirationDate'])->toDateString();
                } catch (\Exception $e) {
                    try { $validThrough = Carbon::parse($item['expirationDate'])->toDateString(); } catch (\Throwable $ee) { $validThrough = null; }
                }
            }

            // Remote detection
            $isRemoteDetected = false;
            $haystack = strtolower(($title ?? '') . ' ' . ($descriptionText ?? '') . ' ' . $keywords);
            if (Str::contains($haystack, ['remote', 'work from home', 'wfh', 'telecommute'])) {
                $isRemoteDetected = true;
            }

            // applicantLocationRequirements default for Reed imports:
            // per your request, default to United Kingdom when source = Reed and field absent
            $applicantLocationRequirements = ['United Kingdom'];
            if (!empty($item['applicantLocationRequirements'])) {
                if (is_array($item['applicantLocationRequirements'])) {
                    $applicantLocationRequirements = $item['applicantLocationRequirements'];
                } else {
                    $dec = json_decode($item['applicantLocationRequirements'], true);
                    $applicantLocationRequirements = is_array($dec) ? $dec : [$item['applicantLocationRequirements']];
                }
            }

            // salary parse from search result, but primary source is detail API
            $baseMin = null;
            $baseMax = null;
            $baseCurrency = null;
            $baseUnit = 'YEAR';
            $baseString = null;

            if (isset($item['minimumSalary']) || isset($item['maximumSalary'])) {
                if (isset($item['minimumSalary']) && is_numeric($item['minimumSalary'])) {
                    $baseMin = (float) $item['minimumSalary'];
                }
                if (isset($item['maximumSalary']) && is_numeric($item['maximumSalary'])) {
                    $baseMax = (float) $item['maximumSalary'];
                }
                $baseCurrency = $item['currency'] ?? null;
            }

            // directApply detection
            $directApply = 0;
            if (!empty($item['directApply']) || !empty($item['easyApply'])) {
                $directApply = (bool) ($item['directApply'] ?? $item['easyApply'] ?? false) ? 1 : 0;
            }

            $employmentType = $item['jobType'] ?? $item['employmentType'] ?? $item['type'] ?? 'Full time';
            $jobLocationType = $isRemoteDetected ? 'TELECOMMUTE' : ($item['jobLocationType'] ?? null);

            // validThrough fallback later
            if (empty($validThrough)) {
                if (!empty($datePosted)) {
                    try {
                        $validThrough = Carbon::parse($datePosted)->addDays(45)->toDateString();
                    } catch (\Throwable $e) {
                        $validThrough = Carbon::now()->addDays(45)->toDateString();
                    }
                } else {
                    $validThrough = Carbon::now()->addDays(45)->toDateString();
                }
            }

            // Decide whether to fetch detail:
            $needDetail = $forceDetail;
            $snippetLen = mb_strlen(strip_tags($snippetDesc ?? ''));
            if (!$needDetail) {
                if ($snippetLen < 250) $needDetail = true;
                if (Str::endsWith(trim($snippetDesc), ['...', '…'])) $needDetail = true;
                if (Str::contains($snippetDesc, ['[',']','[...]','see more','read more'])) $needDetail = true;
            }

            // Also fetch detail if salary missing
            $salaryMissing = (empty($baseMin) && empty($baseMax) && empty($baseString));
            if ($salaryMissing) {
                $needDetail = true;
            }

            $detailedDescriptionHtml = null;
            $detailSalary = null;
            $salaryHidden = false;

            // 1) Try fetch detail via API if we have jobId and needDetail
            if ($needDetail && !empty($jobId)) {
                $detailFetchedItem = $this->fetchReedJobDetailById($jobId, $apiKey, $base);
                if (!empty($detailFetchedItem) && is_array($detailFetchedItem)) {
                    $detailFetched++;

                    // attempt to read jobDescription from detail API
                    $detailDesc = $detailFetchedItem['jobDescription'] ?? $detailFetchedItem['description'] ?? null;
                    if (!empty($detailDesc) && mb_strlen(strip_tags($detailDesc)) > 50) {
                        $detailedDescriptionHtml = $detailDesc;
                        $descriptionText = trim(strip_tags($detailedDescriptionHtml));
                        $descriptionHtml = $detailedDescriptionHtml;
                    }

                    // salary info in detail
                    if (isset($detailFetchedItem['minimumSalary']) || isset($detailFetchedItem['maximumSalary'])) {
                        if (isset($detailFetchedItem['minimumSalary']) && is_numeric($detailFetchedItem['minimumSalary'])) {
                            $baseMin = (float) $detailFetchedItem['minimumSalary'];
                        }
                        if (isset($detailFetchedItem['maximumSalary']) && is_numeric($detailFetchedItem['maximumSalary'])) {
                            $baseMax = (float) $detailFetchedItem['maximumSalary'];
                        }
                        $baseCurrency = $detailFetchedItem['currency'] ?? $baseCurrency;
                    } else {
                        // Reed docs: if salary hidden on site, detail API will not show salary
                        if (array_key_exists('minimumSalary', $detailFetchedItem) === false && array_key_exists('maximumSalary', $detailFetchedItem) === false) {
                            $salaryHidden = true;
                        }
                    }

                    // expiration / date updates from detail if available
                    if (!empty($detailFetchedItem['date'])) {
                        try {
                            $datePosted = Carbon::createFromFormat('d/m/Y', $detailFetchedItem['date'])->toDateString();
                        } catch (\Throwable $e) {
                            try { $datePosted = Carbon::parse($detailFetchedItem['date'])->toDateString(); } catch (\Throwable $ee) {}
                        }
                    }
                    if (!empty($detailFetchedItem['expirationDate'])) {
                        try {
                            $validThrough = Carbon::createFromFormat('d/m/Y', $detailFetchedItem['expirationDate'])->toDateString();
                        } catch (\Throwable $e) {
                            try { $validThrough = Carbon::parse($detailFetchedItem['expirationDate'])->toDateString(); } catch (\Throwable $ee) {}
                        }
                    }

                    // If detail contains applicantLocationRequirements, override default
                    if (!empty($detailFetchedItem['applicantLocationRequirements'])) {
                        if (is_array($detailFetchedItem['applicantLocationRequirements'])) {
                            $applicantLocationRequirements = $detailFetchedItem['applicantLocationRequirements'];
                        } else {
                            $dec2 = json_decode($detailFetchedItem['applicantLocationRequirements'], true);
                            $applicantLocationRequirements = is_array($dec2) ? $dec2 : [$detailFetchedItem['applicantLocationRequirements']];
                        }
                    }
                } else {
                    $detailSkipped++;
                }
            }

            // 2) If still no good full description, fallback to scraping jobUrl (if allowed)
            if (empty($detailedDescriptionHtml) && !empty($jobUrl)) {
                $scrapeAttempts++;
                $scraped = $this->fetchDescriptionFromJobUrl($jobUrl);
                if (!empty($scraped)) {
                    $scrapeSuccess++;
                    $detailedDescriptionHtml = $scraped;
                    $descriptionHtml = $detailedDescriptionHtml;
                    $descriptionText = trim(strip_tags($detailedDescriptionHtml));
                }
            }

            // If no salary info at all and salaryHidden flagged, set base_string default as requested
            if (empty($baseMin) && empty($baseMax) && empty($baseString)) {
                if ($salaryHidden) {
                    $baseString = 'Gaji disembunyikan';
                } else {
                    $baseString = 'Perkiraan gaji: 10.000.000';
                }
            }

            // Build data for DB upsert
            $data = [
                'title' => $title ?? ('Lowongan ' . substr($fingerprint, 0, 8)),
                'description' => $descriptionText ?? null,
                'description_html' => $descriptionHtml,
                'company' => $employer ?? null,
                'company_domain' => $item['employerProfileName'] ?? null,
                'location' => $item['locationName'] ?? ($isRemoteDetected ? 'Remote' : null),
                'type' => $employmentType,
                'is_wfh' => $isRemoteDetected ? 1 : 0,
                'source_url' => $jobUrl ?? null,
                'final_url' => $jobUrl ?? null,
                'url_source' => $jobUrl ?? null,
                'is_imported' => 1,
                'status' => 'published',
                'discovered_at' => Carbon::now(),
                'posted_at' => $datePosted ? Carbon::parse($datePosted) : null,
                'date_posted' => $datePosted ? $datePosted : null,
                'hiring_organization' => $employer ?? null,
                'job_location' => $item['locationName'] ?? ($isRemoteDetected ? 'Remote' : null),
                'applicant_location_requirements' => $applicantLocationRequirements,
                'base_salary_min' => $baseMin,
                'base_salary_max' => $baseMax,
                'base_salary_currency' => $baseCurrency ?? null,
                'base_salary_unit' => $baseUnit,
                'base_salary_string' => $baseString,
                'direct_apply' => $directApply ? 1 : 0,
                'employment_type' => $employmentType,
                'employment_type_raw' => null,
                'identifier_name' => 'job_id',
                'identifier_value' => $jobId ?? null,
                'job_location_type' => $jobLocationType,
                'valid_through' => $validThrough,
                'is_remote' => $isRemoteDetected ? 1 : 0,
                'apply_url' => $jobUrl ?? null,
                'easy_apply' => 0,
                'raw' => json_encode($rawJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'fingerprint' => $fingerprint,
            ];

            // Upsert
            try {
                if (!empty($jobId)) {
                    $jobModel = Job::updateOrCreate(
                        ['identifier_value' => (string) $jobId],
                        $data
                    );
                    $imported++;
                } else {
                    $existing = Job::where('fingerprint', $fingerprint)->first();
                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        $jobModel = Job::create($data);
                        $imported++;
                    }
                }
            } catch (\Throwable $e) {
                $skipped++;
                Log::error('reed.import.save_error', ['message' => $e->getMessage(), 'item' => $rawJson]);
                $this->warn("Skipped one job due to save error: " . $e->getMessage());
                continue;
            }
        } // end foreach results

        $this->info("Import complete. imported={$imported} updated={$updated} skipped={$skipped}");
        $this->info("detailsFetched={$detailFetched} detailSkipped={$detailSkipped} scrapeAttempts={$scrapeAttempts} scrapeSuccess={$scrapeSuccess}");
        Log::info('reed.import.summary', [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'details_fetched' => $detailFetched,
            'detail_skipped' => $detailSkipped,
            'scrape_attempts' => $scrapeAttempts,
            'scrape_success' => $scrapeSuccess,
        ]);

        return 0;
    }

    /**
     * Fetch job detail from Reed API: /jobs/{id}
     * Returns decoded JSON or null.
     */
    protected function fetchReedJobDetailById(string $jobId, string $apiKey, string $baseUrl)
    {
        $endpoint = rtrim($baseUrl, '/') . '/jobs/' . rawurlencode($jobId);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxDetailRetries) {
            try {
                $attempt++;
                $resp = Http::withBasicAuth($apiKey, '')
                    ->timeout(20)
                    ->get($endpoint);

                if ($resp->successful()) {
                    $json = $resp->json();
                    // Reed returns blank object for no match — treat empty array/object as null
                    if (is_array($json) && empty($json)) {
                        return null;
                    }
                    return $json;
                }

                // For 429 or 5xx, we retry with backoff
                $status = $resp->status();
                if (in_array($status, [429, 500, 502, 503, 504])) {
                    $sleep = $this->detailRetryBaseSleepMs * (2 ** ($attempt - 1));
                    usleep($sleep * 1000);
                    continue;
                }

                // For 401 -> invalid API key: break and log
                if ($status === 401) {
                    Log::error('reed.detail.unauthorized', ['jobId' => $jobId]);
                    return null;
                }

                // other client errors — no retry
                Log::warning('reed.detail.non_success', ['status' => $status, 'jobId' => $jobId, 'body' => $resp->body()]);
                return null;

            } catch (\Throwable $e) {
                $lastException = $e;
                $sleep = $this->detailRetryBaseSleepMs * (2 ** ($attempt - 1));
                usleep($sleep * 1000);
            }
        }

        if ($lastException) {
            Log::error('reed.detail.error', ['jobId' => $jobId, 'message' => $lastException->getMessage()]);
        }

        return null;
    }

    /**
     * Fallback: fetch jobUrl HTML and try to extract job description.
     * Returns HTML string or null.
     */
    protected function fetchDescriptionFromJobUrl(string $url)
    {
        try {
            $resp = Http::timeout(25)
                ->withHeaders(['User-Agent' => 'TeleworksBot/1.0 (+https://teleworks.id)'])
                ->get($url);

            if (! $resp->successful()) {
                Log::warning('reed.scrape.failed_response', ['url' => $url, 'status' => $resp->status()]);
                return null;
            }

            $html = $resp->body();

            // If symfony DomCrawler exists, use it for robust selection
            if (class_exists(\Symfony\Component\DomCrawler\Crawler::class)) {
                $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

                $selectors = [
                    'div.job-description',
                    'div#job-description',
                    '.jobDescription',
                    'section.job-description',
                    'div#job-result',
                    'div#job-description > div',
                ];

                foreach ($selectors as $sel) {
                    try {
                        $nodes = $crawler->filter($sel);
                        if ($nodes->count()) {
                            $out = '';
                            foreach ($nodes as $node) {
                                $out .= $node->ownerDocument->saveHTML($node);
                            }
                            $san = $this->sanitizeHtml($out);
                            if ($san) return $san;
                        }
                    } catch (\InvalidArgumentException $e) {
                        // invalid selector — ignore and continue
                        continue;
                    }
                }

                // fallback: try to find by common heading then sibling content
                $possibleHeading = $crawler->filterXPath("//h2[contains(translate(.,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'job description')]");
                if ($possibleHeading->count()) {
                    $node = $possibleHeading->first();
                    $sibling = $node->nextAll()->first();
                    if ($sibling->count()) {
                        $htmlOut = $sibling->html();
                        $san = $this->sanitizeHtml($htmlOut);
                        if ($san) return $san;
                    }
                }

                // final fallback: return body innerHTML
                $body = $crawler->filter('body')->first();
                if ($body->count()) {
                    $bhtml = $body->html();
                    return $this->sanitizeHtml($bhtml);
                }

                return null;
            }

            // If DomCrawler not installed, use DOMDocument fallback
            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $xpath = new \DOMXPath($doc);
            $candidates = [
                "//div[contains(@class,'job-description')]",
                "//*[@id='job-description']",
                "//*[contains(@class,'jobDescription')]",
                "//section[contains(@class,'job-description')]",
            ];

            foreach ($candidates as $xp) {
                $nodes = $xpath->query($xp);
                if ($nodes->length) {
                    $out = '';
                    foreach ($nodes as $n) {
                        $out .= $doc->saveHTML($n);
                    }
                    $san = $this->sanitizeHtml($out);
                    if ($san) return $san;
                }
            }

            // fallback: body innerHTML
            $bodyNodes = $doc->getElementsByTagName('body');
            if ($bodyNodes->length) {
                $b = $bodyNodes->item(0);
                $inner = '';
                foreach ($b->childNodes as $child) {
                    $inner .= $doc->saveHTML($child);
                }
                return $this->sanitizeHtml($inner);
            }

            return null;

        } catch (\Throwable $e) {
            Log::error('reed.scrape.error', ['url' => $url, 'message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Very small sanitize: remove <script>, <style>, on* attributes and javascript: href
     * Returns cleaned HTML string.
     */
    protected function sanitizeHtml(?string $html): ?string
    {
        if (empty($html)) return null;

        // Use DOMDocument to parse and sanitize
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        // remove script and style
        $tagsToRemove = ['script', 'style', 'iframe', 'noscript'];
        foreach ($tagsToRemove as $tag) {
            $nodes = $doc->getElementsByTagName($tag);
            // getElementsByTagName is live NodeList - iterate backwards
            for ($i = $nodes->length - 1; $i >= 0; $i--) {
                $node = $nodes->item($i);
                if ($node) $node->parentNode->removeChild($node);
            }
        }

        // remove on* attributes and javascript: href/src
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query('//@*');
        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $attr = $nodes->item($i);
            $name = $attr->nodeName;
            $value = $attr->nodeValue;
            if (stripos($name, 'on') === 0) {
                // remove attribute
                $attr->ownerElement->removeAttributeNode($attr);
            } else {
                // sanitize href/src with javascript:
                if (in_array(strtolower($name), ['href','src'])) {
                    if (stripos($value, 'javascript:') === 0) {
                        $attr->ownerElement->removeAttributeNode($attr);
                    }
                }
            }
        }

        // Optionally remove style attributes (uncomment if desired)
        // $nodes = $xpath->query('//@style');
        // foreach ($nodes as $n) { $n->ownerElement->removeAttributeNode($n); }

        // Return innerHTML of body if present else whole document
        $bodyNodes = $doc->getElementsByTagName('body');
        if ($bodyNodes->length) {
            $body = $bodyNodes->item(0);
            $inner = '';
            foreach ($body->childNodes as $child) {
                $inner .= $doc->saveHTML($child);
            }
            return trim($inner);
        }

        return trim($doc->saveHTML());
    }
}

