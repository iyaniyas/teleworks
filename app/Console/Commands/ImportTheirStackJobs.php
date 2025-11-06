<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Services\TheirStackClient;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ImportTheirStackJobs extends Command
{
    protected $signature = 'theirstack:import
        {--q=remote : Kata kunci pencarian (contoh: remote, developer)}
        {--country=ID : Kode negara (misal: ID, SG, US)}
        {--pages=1 : Jumlah halaman (loop berbasis 0)}
        {--per-page=20 : Jumlah job per halaman (limit)}';

    protected $description = 'Import job posting dari TheirStack API';

    public function handle(TheirStackClient $api)
    {
        $pages = (int) $this->option('pages');      // contoh: 1
        $limit = (int) $this->option('per-page');   // contoh: 20

        // Filter default: fokus WFH/remote + negara
        $params = [
            'q'            => $this->option('q'),
            'country_code' => $this->option('country'),
            'remote'       => true,
        ];

        $inserted = 0;
        $skipped  = 0;

        // Pagination TheirStack pakai offset/limit (di client kita hitung dari page berbasis 0)
        for ($page = 0; $page < $pages; $page++) {
            $data = $api->searchJobs($params, $page, $limit);

            // TheirStack biasanya kirim array di 'data' (fallback ke 'results' kalau berbeda)
            $jobs = $data['data'] ?? $data['results'] ?? [];

            if (empty($jobs)) {
                $this->warn('Halaman ' . ($page + 1) . ' kosong.');
                continue;
            }

            foreach ($jobs as $j) {
                // --- Mapping field ---
                $sourceId    = $j['id'] ?? null;
                $title       = $j['job_title'] ?? $j['title'] ?? null;
                $company     = $j['company']['name'] ?? $j['company'] ?? null;
                $location    = $j['location'] ?? ($j['long_location'] ?? null);
                $isRemote    = (bool)($j['remote'] ?? false);
                $description = $j['description'] ?? null;
                $applyUrl    = $j['final_url'] ?? $j['url'] ?? null;

                $datePosted = !empty($j['date_posted'])
                    ? Carbon::parse($j['date_posted'])->toDateString()
                    : now()->toDateString();

                // applicantLocationRequirements
                $appLocReq = !empty($j['applicant_location_requirements'])
                    ? $j['applicant_location_requirements']
                    : [($j['country_code'] ?? 'ID')];

                // baseSalary
                $salary   = $j['salary'] ?? $j['base_salary'] ?? [];
                $baseMin  = $salary['min'] ?? null;
                $baseMax  = $salary['max'] ?? null;
                $baseCur  = $salary['currency'] ?? 'IDR';
                $baseUnit = $salary['unit'] ?? 'MONTH';

                // lainnya
                $employmentType  = $j['employment_type'] ?? 'FULL_TIME';
                $directApply     = (bool)($j['direct_apply'] ?? false);
                $jobLocationType = $isRemote ? 'TELECOMMUTE' : ((isset($j['hybrid']) && $j['hybrid']) ? 'HYBRID' : 'ONSITE');

                // validThrough default: 45 hari dari datePosted
                $validThrough = Carbon::parse($datePosted)->addDays(45)->toDateString();

                Job::updateOrCreate(
                    ['source' => 'theirstack', 'source_id' => $sourceId],
                    [
                        'title'                              => $title,
                        'company'                            => $company,
                        'location'                           => $location,
                        'is_remote'                          => $isRemote,
                        'description'                        => $description,
                        'apply_url'                          => $applyUrl,
                        'date_posted'                        => $datePosted,
                        'hiring_organization'                => $company,
                        'job_location'                       => $location,
                        'applicant_location_requirements'    => json_encode($appLocReq),
                        'base_salary_min'                    => $baseMin,
                        'base_salary_max'                    => $baseMax,
                        'base_salary_currency'               => $baseCur,
                        'base_salary_unit'                   => $baseUnit,
                        'direct_apply'                       => $directApply,
                        'employment_type'                    => $employmentType,
                        'identifier_name'                    => $company,
                        'identifier_value'                   => $sourceId ? ('theirstack-' . $sourceId) : null,
                        'job_location_type'                  => $jobLocationType,
                        'valid_through'                      => $validThrough,
                        'raw'                                => json_encode($j),
                    ]
                );

                $inserted++;
            }

            $this->info('Halaman ' . ($page + 1) . ': inserted=' . $inserted . ', skipped=' . $skipped);
        }

        $this->info('✅ Import selesai! Total: ' . $inserted . ' job baru');
        return self::SUCCESS;
    }
}

