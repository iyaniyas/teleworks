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
        {--q=remote : Kata kunci pencarian (contoh: remote, developer)}
        {--country=ID : Kode negara (misal: ID, SG, US)}
        {--pages=1 : Jumlah halaman (loop berbasis 0)}
        {--per-page=20 : Jumlah job per halaman (limit)}';

    protected $description = 'Import job posting dari TheirStack API dengan auto-fingerprint dan URL lengkap';

    public function handle(TheirStackClient $api)
    {
        $pages = (int) $this->option('pages');
        $limit = (int) $this->option('per-page');

        $params = [
            'q'            => $this->option('q'),
            'country_code' => $this->option('country'),
            'remote'       => true,
        ];

        $inserted = 0;
        $skipped  = 0;

        for ($page = 0; $page < $pages; $page++) {
            $data = $api->searchJobs($params, $page, $limit);
            $jobs = $data['data'] ?? $data['results'] ?? [];

            if (empty($jobs)) {
                $this->warn('Halaman ' . ($page + 1) . ' kosong.');
                continue;
            }

            foreach ($jobs as $j) {
                // --- Mapping utama dari TheirStack ---
                $sourceId = $j['id'] ?? null;
                $title = $j['job_title'] ?? $j['title'] ?? null;
                $company = $j['company_object']['name'] ?? $j['company']['name'] ?? $j['company'] ?? null;
                $companyDomain = $j['company_domain'] ?? ($j['company_object']['domain'] ?? null);
                $location = $j['locations'][0]['display_name'] ?? $j['location'] ?? ($j['long_location'] ?? null);
                $isRemote = (bool)($j['remote'] ?? false);
                $description = $j['description'] ?? null;

                // --- Ambil apply URL prioritas ---
                $applyUrl = $this->pickApplyUrl($j);

                $datePosted = !empty($j['date_posted'])
                    ? Carbon::parse($j['date_posted'])->toDateString()
                    : now()->toDateString();

                $appLocReq = !empty($j['applicant_location_requirements'])
                    ? $j['applicant_location_requirements']
                    : [($j['country_code'] ?? 'ID')];

                // --- Salary ---
                $salary = $j['salary'] ?? $j['base_salary'] ?? [];
                $baseMin  = $salary['min'] ?? null;
                $baseMax  = $salary['max'] ?? null;
                $baseCur  = $salary['currency'] ?? 'IDR';
                $baseUnit = $salary['unit'] ?? 'MONTH';

                // --- Lainnya ---
                $employmentType  = $j['employment_type'] ?? 'FULL_TIME';
                $directApply     = (bool)($j['direct_apply'] ?? false);
                $jobLocationType = $isRemote ? 'TELECOMMUTE' : ((isset($j['hybrid']) && $j['hybrid']) ? 'HYBRID' : 'ONSITE');
                $validThrough    = Carbon::parse($datePosted)->addDays(45)->toDateString();

                // --- Buat fingerprint unik ---
                $fp = $this->makeFingerprint($title, $company, $location, $datePosted);

                // --- Simpan / update ---
                Job::updateOrCreate(
                    ['fingerprint' => $fp],
                    [
                        'title'                           => $title,
                        'company'                         => $company,
                        'company_domain'                  => $companyDomain,
                        'location'                        => $location,
                        'is_remote'                       => $isRemote,
                        'description'                     => $description,
                        'apply_url'                       => $applyUrl,
                        'final_url'                       => $j['final_url'] ?? null,
                        'source_url'                      => $j['source_url'] ?? null,
                        'url_source'                      => $j['url_source'] ?? ($j['company_object']['url_source'] ?? null),
                        'date_posted'                     => $datePosted,
                        'hiring_organization'             => $company,
                        'job_location'                    => $location,
                        'applicant_location_requirements' => json_encode($appLocReq),
                        'base_salary_min'                 => $baseMin,
                        'base_salary_max'                 => $baseMax,
                        'base_salary_currency'            => $baseCur,
                        'base_salary_unit'                => $baseUnit,
                        'direct_apply'                    => $directApply,
                        'employment_type'                 => $employmentType,
                        'identifier_name'                 => $company,
                        'identifier_value'                => $sourceId ? ('theirstack-' . $sourceId) : null,
                        'job_location_type'               => $jobLocationType,
                        'valid_through'                   => $validThrough,
                        'source'                          => 'theirstack',
                        'discovered_at'                   => now(),
                        'raw'                             => json_encode($j),
                    ]
                );

                $inserted++;
            }

            $this->info('Halaman ' . ($page + 1) . ": inserted=$inserted, skipped=$skipped");
        }

        $this->info('✅ Import selesai! Total: ' . $inserted . ' job baru');
        return self::SUCCESS;
    }

    // ================================
    // 🔹 Helper Functions
    // ================================

    private function makeFingerprint($title, $company, $location, $date)
    {
        $plain = $this->normalize($title) . '|' .
                 $this->normalize($company) . '|' .
                 $this->normalize($location) . '|' .
                 $this->normalize($date);
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
        return $url;
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
}

