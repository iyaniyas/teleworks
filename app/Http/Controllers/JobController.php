<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JobController extends Controller
{
    /**
     * Tampilkan detail job + JSON-LD JobPosting.
     *
     * @param  int|string  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $job = Job::with('company')
            ->publicVisible() // published + expired
            ->where('id', $id)
            ->firstOrFail();

        // datePosted: prefer date_posted, lalu posted_at, lalu hari ini
        if (!empty($job->date_posted)) {
            $datePosted = Carbon::parse($job->date_posted)->toDateString();
            $datePostedForDisplay = Carbon::parse($job->date_posted);
        } elseif (!empty($job->posted_at)) {
            $datePosted = Carbon::parse($job->posted_at)->toDateString();
            $datePostedForDisplay = Carbon::parse($job->posted_at);
        } else {
            $datePosted = Carbon::now()->toDateString();
            $datePostedForDisplay = Carbon::now();
        }

        // validThrough: pakai field jika ada, kalau tidak datePosted + 45 hari
        if (!empty($job->valid_through)) {
            $validThrough = Carbon::parse($job->valid_through)->toDateString();
        } else {
            $validThrough = Carbon::parse($datePosted)->addDays(45)->toDateString();
        }

        // Jika status expired tapi validThrough masih masa depan, paksa ke kemarin
        if ($job->status === 'expired' && Carbon::parse($validThrough)->isFuture()) {
            $validThrough = Carbon::yesterday()->toDateString();
        }

        // employmentType: fallback ke Full time
        $employmentType = $job->employment_type ?? 'Full time';

        /*
         * applicantLocationRequirements: normalisasi & mapping kode negara -> nama
         */
        $appReqField = $job->applicant_location_requirements ?? null;
        if (is_null($appReqField) || $appReqField === '') {
            $rawAppReq = [];
        } elseif (is_array($appReqField)) {
            $rawAppReq = $appReqField;
        } else {
            $decoded = json_decode($appReqField, true);
            $rawAppReq = is_array($decoded) ? $decoded : [$appReqField];
        }

        $countryMap = [
            'ID'  => 'Indonesia',
            'IDN' => 'Indonesia',
            'US'  => 'United States',
            'USA' => 'United States',
            'GB'  => 'United Kingdom',
            'UK'  => 'United Kingdom',
            'IN'  => 'India',
            'SG'  => 'Singapore',
            'AU'  => 'Australia',
            'CA'  => 'Canada',
            'PH'  => 'Philippines',
        ];

        $applicantLocationRequirements = [];
        foreach ($rawAppReq as $r) {
            $rTrim = trim((string) $r);
            if ($rTrim === '') {
                continue;
            }
            $upper = strtoupper($rTrim);
            if ((strlen($upper) >= 2 && strlen($upper) <= 3) && isset($countryMap[$upper])) {
                $name = $countryMap[$upper];
            } else {
                if (isset($countryMap[$upper])) {
                    $name = $countryMap[$upper];
                } else {
                    $name = $rTrim;
                }
            }
            $applicantLocationRequirements[] = [
                '@type' => 'Country',
                'name'  => $name,
            ];
        }

        if (empty($applicantLocationRequirements)) {
            $applicantLocationRequirements[] = [
                '@type' => 'Country',
                'name'  => 'Indonesia',
            ];
        }

        /*
         * baseSalary: bangun MonetaryAmount + QuantitativeValue
         */
        $baseSalary = null;
        $currency = $job->base_salary_currency ?? 'IDR';

        // normalisasi unit: HOUR|DAY|WEEK|MONTH|YEAR
        $unitText = strtoupper($job->base_salary_unit ?? 'YEAR');
        $allowedUnits = ['HOUR', 'DAY', 'WEEK', 'MONTH', 'YEAR'];
        if (!in_array($unitText, $allowedUnits, true)) {
            if (stripos($unitText, 'month') !== false) {
                $unitText = 'MONTH';
            } elseif (stripos($unitText, 'day') !== false) {
                $unitText = 'DAY';
            } elseif (stripos($unitText, 'week') !== false) {
                $unitText = 'WEEK';
            } elseif (stripos($unitText, 'hour') !== false) {
                $unitText = 'HOUR';
            } else {
                $unitText = 'YEAR';
            }
        }

        $hasMin = isset($job->base_salary_min) && $job->base_salary_min !== '' && $job->base_salary_min !== null;
        $hasMax = isset($job->base_salary_max) && $job->base_salary_max !== '' && $job->base_salary_max !== null;

        if ($hasMin || $hasMax) {
            $qv = [
                '@type'    => 'QuantitativeValue',
                'unitText' => $unitText,
            ];
            if ($hasMin) {
                $qv['minValue'] = (float) $job->base_salary_min;
            }
            if ($hasMax) {
                $qv['maxValue'] = (float) $job->base_salary_max;
            }

            $baseSalary = [
                '@type'    => 'MonetaryAmount',
                'currency' => $currency,
                'value'    => $qv,
            ];
        } elseif (!empty($job->base_salary_string)) {
            $s = $job->base_salary_string;
            if (preg_match('/(\d{1,3}(?:[,\.\d]{0,})\s*[kK]?)/', $s, $m)) {
                $num = $m[1];
                $isK = false;
                if (str_ends_with(strtolower($num), 'k')) {
                    $isK = true;
                    $num = substr($num, 0, -1);
                }
                $num = preg_replace('/[,\.\s]/', '', $num);
                if (is_numeric($num)) {
                    $val = (float) $num;
                    if ($isK) {
                        $val *= 1000;
                    }
                    $baseSalary = [
                        '@type'    => 'MonetaryAmount',
                        'currency' => $currency,
                        'value'    => [
                            '@type'    => 'QuantitativeValue',
                            'value'    => $val,
                            'unitText' => $unitText,
                        ],
                    ];
                }
            }
        }

        // sinkronisasi nama & domain perusahaan dari relasi companies
        $companyModel = null;
        try {
            $companyModel = $job->company()->first();
        } catch (\Throwable $e) {
            $companyModel = null;
        }

        $companyName = $companyModel && !empty($companyModel->name)
            ? $companyModel->name
            : ($job->hiring_organization ?? null);

        $companyDomainFromModel = null;
        if ($companyModel) {
            if (!empty($companyModel->website)) {
                $companyDomainFromModel = $companyModel->website;
            } elseif (!empty($companyModel->domain)) {
                $companyDomainFromModel = $companyModel->domain;
            }
        }

        $companySameAs = null;
        if ($companyDomainFromModel) {
            $companySameAs = str_starts_with($companyDomainFromModel, 'http')
                ? $companyDomainFromModel
                : 'https://'.ltrim($companyDomainFromModel, '/');
        } elseif (!empty($job->company_domain)) {
            $companySameAs = str_starts_with($job->company_domain, 'http')
                ? $job->company_domain
                : 'https://'.ltrim($job->company_domain, '/');
        }

        /*
         * Build JobPosting JSON-LD (telecommute)
         */
        $jobLd = [
            '@context'       => 'https://schema.org',
            '@type'          => 'JobPosting',
            'title'          => $job->title,
            'description'    => $job->description ? strip_tags($job->description) : null,
            'datePosted'     => $datePosted,
            'validThrough'   => $validThrough,
            'employmentType' => $employmentType,
            'hiringOrganization' => $companyName ? [
                '@type'  => 'Organization',
                'name'   => $companyName,
                'sameAs' => $companySameAs,
            ] : null,
            'jobLocation' => [
                '@type'   => 'Place',
                'address' => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => null,
                    'addressLocality' => $job->job_location ? $job->job_location : 'Remote',
                    'addressCountry'  => null,
                ],
            ],
            'applicantLocationRequirements' => (count($applicantLocationRequirements) === 1)
                ? $applicantLocationRequirements[0]
                : $applicantLocationRequirements,
            'baseSalary'  => $baseSalary,
            'directApply' => (bool) $job->direct_apply,
            'identifier'  => [
                '@type' => 'PropertyValue',
                'name'  => $job->identifier_name ?? 'job_id',
                'value' => $job->identifier_value ?? (string) $job->id,
            ],
            'jobLocationType' => 'TELECOMMUTE',
        ];

        // hapus null / array kosong di level atas
        $jobLd = array_filter($jobLd, function ($v) {
            return $v !== null && $v !== [];
        });

        if (empty($jobLd['baseSalary'])) {
            unset($jobLd['baseSalary']);
        }

        $jobPostingJsonLd = json_encode(
            $jobLd,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        /*
         * RELATED JOBS: ambil loker aktif terbaru (kecuali current)
         */
        $relatedJobs = Job::active()
            ->where('id', '<>', $job->id)
            ->orderByDesc('date_posted')
            ->orderByDesc('posted_at')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // timestamp untuk layout
        $timestamp = $datePostedForDisplay
            ? $datePostedForDisplay
                ->timezone(config('app.timezone', 'Asia/Jakarta'))
                ->format('d M Y, H:i').' WIB'
            : null;

        return view('jobs.show', compact('job', 'jobPostingJsonLd', 'relatedJobs', 'timestamp'));
    }

    /**
     * Form edit job (public route /loker/{id}/edit).
     */
    public function edit($id)
    {
        $job = Job::where('id', $id)->firstOrFail();

        $this->authorize('update', $job);

        return view('jobs.edit', compact('job'));
    }

    /**
     * Update job dari form publik /loker/{id}.
     */
    public function update(Request $request, $id)
    {
        $job = Job::where('id', $id)->firstOrFail();

        $this->authorize('update', $job);

        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'location'        => 'nullable|string|max:255',
            'base_salary_min' => 'nullable|numeric',
            'base_salary_max' => 'nullable|numeric',
        ]);

        $job->update($validated);

        return redirect()
            ->route('jobs.show', $job->id)
            ->with('success', 'Job berhasil diperbarui.');
    }
}

