<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CareerjetClient;
use App\Services\CareerjetParser;
use App\Models\Job;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportCareerjetJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Note: avoid unescaped apostrophes inside the signature string.
     *
     * @var string
     */
    protected $signature = 'careerjet:import
        {--q= : Kata kunci pencarian}
        {--location= : Lokasi pencarian (kota/negara)}
        {--pages=1 : Berapa halaman API yang diambil}
        {--page_size=20 : size per page (1..100)}
        {--user_ip= : user_ip (wajib menurut docs)}
        {--user_agent=TeleworksBot/1.0 : user_agent string}
        {--referer=https://teleworks.id : Referer header to send}
        {--limit= : optional max total jobs to import}
        {--render-js : try to render JS (placeholder)}
        {--dry : dry run (do not persist)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import jobs from Careerjet API';

    protected CareerjetClient $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new CareerjetClient();
    }

    public function handle()
    {
        $q = trim($this->option('q') ?? 'remote');
        $location = trim($this->option('location') ?? 'Indonesia');
        $pages = max(1, (int) $this->option('pages'));
        $pageSize = max(1, min(100, (int) $this->option('page_size')));
        $userIp = $this->option('user_ip') ?? null;
        $userAgent = $this->option('user_agent') ?? 'TeleworksBot/1.0';
        $referer = $this->option('referer') ?? config('app.url', 'https://teleworks.id');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;
        $dry = (bool)$this->option('dry');

        $this->info("Careerjet import start — q={$q} location={$location} pages={$pages} page_size={$pageSize} limit=" . ($limit ?? 'none'));

        if (!$userIp) {
            $this->error("Option --user_ip is required by Careerjet API.");
            return 1;
        }

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $processedTotal = 0;

        // build base query params
        $baseParams = [
            'keywords' => $q,
            'locale_code' => 'id_ID',
            'location' => $location,
            'user_ip' => $userIp,
            'user_agent' => $userAgent,
            'sort' => 'date',
            'page_size' => $pageSize,
        ];

        for ($page = 1; $page <= $pages; $page++) {
            $this->info("Querying Careerjet page {$page}...");
            $params = array_merge($baseParams, ['page' => $page, 'offset' => 0]);

            $resp = $this->client->query($params, $referer, $userAgent);

            if (is_array($resp) && isset($resp['__error']) && $resp['__error']) {
                $this->warn("No response or non-200 from Careerjet for page {$page}. Body: " . substr($resp['body'] ?? '', 0, 400));
                continue;
            }

            if (empty($resp) || !isset($resp['jobs']) || !is_array($resp['jobs'])) {
                $this->warn("No jobs key found in Careerjet response for page {$page}.");
                continue;
            }

            $jobs = $resp['jobs'];
            $this->info("Careerjet returned " . count($jobs) . " jobs (hits=" . ($resp['hits'] ?? 'unknown') . ")");

            foreach ($jobs as $j) {
                if ($limit && $processedTotal >= $limit) {
                    $this->info("Reached global limit {$limit}. Stopping.");
                    break 2;
                }

                $processedTotal++;

                $trackingUrl = $j['url'] ?? $j['apply_url'] ?? null;
                $excerpt = $j['description'] ?? ($j['description_excerpt'] ?? '');
                $title = trim($j['title'] ?? 'No title');
                $company = $j['company'] ?? ($j['site'] ?? null);

                $fingerprintSource = $trackingUrl ?: ($title . '|' . $company . '|' . ($j['date'] ?? '') );
                $fingerprint = hash('sha256', $fingerprintSource);

                try {
                    $finalBody = null;
                    $finalUrl = null;
                    if ($trackingUrl) {
                        $f = $this->client->fetchFinalUrlAndBody($trackingUrl, $referer, $userAgent);
                        $finalUrl = $f['final_url'] ?? null;
                        $finalBody = $f['body'] ?? null;

                        if (empty($finalUrl) && !empty($finalBody)) {
                            if (preg_match('#/jobad/(id[0-9a-f]{8,})#i', $finalBody, $m)) {
                                $finalUrl = 'https://www.careerjet.co.id/jobad/' . $m[1];
                            }
                        }
                    }

                    if (empty($finalBody) && $trackingUrl && preg_match('#/jobad/(id[0-9a-f]{8,})#i', $trackingUrl, $m2)) {
                        $direct = 'https://www.careerjet.co.id/jobad/' . $m2[1];
                        $f2 = $this->client->fetchFinalUrlAndBody($direct, $referer, $userAgent);
                        $finalUrl = $f2['final_url'] ?? $direct;
                        $finalBody = $f2['body'] ?? $f2['body'] ?? null;
                    }

                    $descriptionHtml = '';
                    if (!empty($finalBody)) {
                        $descriptionHtml = CareerjetParser::extractDescription($finalBody);
                    }

                    if (empty($descriptionHtml)) {
                        $descriptionHtml = CareerjetParser::sanitizeHtml('<p>' . htmlentities(trim($excerpt ?? '')) . '</p>');
                    }

                    $careerjetJobId = null;
                    if (!empty($finalUrl) && preg_match('#/jobad/(id[0-9a-f]{8,})#i', $finalUrl, $mi)) {
                        $careerjetJobId = $mi[1];
                    }

                    $datePosted = null;
                    if (!empty($j['date'])) {
                        try {
                            $datePosted = Carbon::parse($j['date'])->toDateString();
                        } catch (\Throwable $e) {
                            $datePosted = null;
                        }
                    }

                    $validThrough = null;
                    if (!empty($j['valid_through'])) {
                        try {
                            $validThrough = Carbon::parse($j['valid_through'])->toDateString();
                        } catch (\Throwable $e) {
                            $validThrough = null;
                        }
                    }
                    if (empty($validThrough) && !empty($datePosted)) {
                        $validThrough = Carbon::parse($datePosted)->addDays(45)->toDateString();
                    } elseif (empty($validThrough)) {
                        $validThrough = Carbon::now()->addDays(45)->toDateString();
                    }

                    $appReq = CareerjetParser::normalizeApplicantReq($j['locations'] ?? null);
                    $salary = CareerjetParser::normalizeSalary($j);

                    $directApply = !empty($j['apply_url']) || !empty($finalUrl);

                    $employmentType = $j['contract_type'] ?? ($j['type'] ?? ($j['employment_type'] ?? 'Full time'));
                    if (is_string($employmentType)) {
                        $lower = strtolower($employmentType);
                        if (in_array($lower, ['p','permanent'])) $employmentType = 'Full time';
                        elseif (in_array($lower, ['t','temporary'])) $employmentType = 'Temporary';
                        elseif (in_array($lower, ['i','internship'])) $employmentType = 'Internship';
                    }

                    $jobLocationType = 'ONSITE';
                    $isRemote = false;
                    $combined = strtolower($title . ' ' . ($j['description'] ?? '') . ' ' . ($j['locations'] ?? ''));
                    if (
                        str_contains($combined, 'remote')
                        || str_contains($combined, 'work from home')
                        || str_contains($combined, 'wfh')
                    ) {
                        $jobLocationType = 'TELECOMMUTE';
                        $isRemote = true;
                    }

                    $job_location = $j['locations'] ?? ($j['location'] ?? $location);

                    $finalUrlToSave = $finalUrl ?: ($j['url'] ?? $j['apply_url'] ?? null);

                    $sourceHost = null;
                    if ($finalUrlToSave) {
                        $p = parse_url($finalUrlToSave);
                        $sourceHost = $p['host'] ?? null;
                    }
                    if (!$sourceHost && !empty($j['site'])) {
                        $sourceHost = $j['site'];
                    }
                    $sourceHost = $sourceHost ? substr($sourceHost, 0, 250) : null;

                    $identifierName = 'careerjet_job_id';
                    $identifierValue = $careerjetJobId ?: ($j['url'] ?? $j['site'] ?? null);

                    $payload = [
                        'title' => trim($title),
                        'description' => strip_tags($descriptionHtml),
                        'description_html' => $descriptionHtml,
                        'company' => $company ?: null,
                        'hiring_organization' => $company ?: null,
                        'job_location' => $job_location,
                        'location' => $job_location,
                        'is_wfh' => $isRemote ? 1 : 0,
                        'search' => $q,
                        'source_url' => $sourceHost,
                        'final_url' => $finalUrlToSave,
                        'url_source' => $j['site'] ?? null,
                        'raw_html' => $finalBody ?? null,
                        'is_imported' => 1,
                        'status' => 'published',
                        'discovered_at' => now(),
                        'posted_at' => !empty($j['date']) ? Carbon::parse($j['date'])->toDateTimeString() : now()->toDateTimeString(),
                        'date_posted' => $datePosted,
                        'applicant_location_requirements' => json_encode($appReq),
                        'base_salary_string' => $salary['base_salary_string'] ?? null,
                        'base_salary_currency' => $salary['base_salary_currency'] ?? null,
                        'base_salary_min' => $salary['base_salary_min'] ?? null,
                        'base_salary_max' => $salary['base_salary_max'] ?? null,
                        'base_salary_unit' => $salary['base_salary_unit'] ?? null,
                        'direct_apply' => $directApply ? 1 : 0,
                        'employment_type' => is_string($employmentType) ? $employmentType : null,
                        'employment_type_raw' => is_string($employmentType) ? $employmentType : null,
                        'identifier_name' => $identifierName,
                        'identifier_value' => $identifierValue,
                        'job_location_type' => $jobLocationType,
                        'valid_through' => $validThrough,
                        'is_remote' => $isRemote ? 1 : 0,
                        'apply_url' => $j['apply_url'] ?? $finalUrlToSave,
                        'easy_apply' => 0,
                        'raw' => json_encode($j),
                        'fingerprint' => $fingerprint,
                        'expires_at' => Carbon::parse($validThrough)->endOfDay()->toDateTimeString(),
                    ];

                    $existing = Job::where('fingerprint', $fingerprint)
                                   ->orWhere('identifier_value', $identifierValue)
                                   ->first();

                    if ($existing) {
                        $existing->fill($payload);
                        if (!$dry) $existing->save();
                        $updated++;
                        $this->info("Updated job id={$existing->id} identifier={$identifierValue}");
                    } else {
                        if ($dry) {
                            $this->line("Dry: would insert job title=\"{$payload['title']}\" identifier={$identifierValue}");
                        } else {
                            if (isset($payload['source_url']) && strlen($payload['source_url']) > 250) {
                                $payload['source_url'] = substr($payload['source_url'], 0, 250);
                            }
                            $job = Job::create($payload);
                            $inserted++;
                            $this->info("Inserted job id={$job->id} identifier={$identifierValue}");
                        }
                    }
                } catch (\Throwable $e) {
                    $skipped++;
                    $this->warn("Error processing job: " . $e->getMessage());
                    \Log::error('Careerjet import job exception', ['exception' => $e->getMessage(), 'job' => $j]);
                }
            } // end foreach jobs
        } // end pages loop

        $this->info("Careerjet import finished. Inserted: {$inserted}, Updated: {$updated}, Skipped/Error: {$skipped}.");
        return 0;
    }
}

