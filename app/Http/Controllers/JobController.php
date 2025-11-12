<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JobController extends Controller
{
    /**
     * Show a job and always generate JobPosting JSON-LD (remote / telecommute).
     *
     * @param  int|string  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $job = Job::where('id', $id)->firstOrFail();

        // datePosted: prefer date_posted, else posted_at, else today's date
        if ($job->date_posted) {
            $datePosted = Carbon::parse($job->date_posted)->toDateString();
        } elseif ($job->posted_at) {
            $datePosted = Carbon::parse($job->posted_at)->toDateString();
        } else {
            $datePosted = Carbon::now()->toDateString();
        }

        // validThrough: prefer field else datePosted + 45
        if ($job->valid_through) {
            $validThrough = Carbon::parse($job->valid_through)->toDateString();
        } else {
            $validThrough = Carbon::parse($datePosted)->addDays(45)->toDateString();
        }

        // employmentType: fallback to Full time
        $employmentType = $job->employment_type ?? 'Full time';

        //
        // applicantLocationRequirements -> normalize & map country codes to full names
        //
        $appReqField = $job->applicant_location_requirements;
        if (is_null($appReqField) || $appReqField === '') {
            $rawAppReq = [];
        } elseif (is_array($appReqField)) {
            $rawAppReq = $appReqField;
        } else {
            $decoded = json_decode($appReqField, true);
            $rawAppReq = is_array($decoded) ? $decoded : [$appReqField];
        }

        // simple mapping for common ISO country codes -> full name (expand as needed)
        $countryMap = [
            'ID' => 'Indonesia',
            'IDN' => 'Indonesia',
            'US' => 'United States',
            'USA' => 'United States',
            'GB' => 'United Kingdom',
            'UK' => 'United Kingdom',
            'IN' => 'India',
            'SG' => 'Singapore',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'PH' => 'Philippines',
            // add more if needed
        ];

        $applicantLocationRequirements = [];
        foreach ($rawAppReq as $r) {
            $rTrim = trim((string)$r);
            if ($rTrim === '') continue;
            // if looks like 2-3 letter code, map; else use raw text
            $upper = strtoupper($rTrim);
            if (strlen($upper) >= 2 && strlen($upper) <= 3 && isset($countryMap[$upper])) {
                $name = $countryMap[$upper];
            } elseif (strlen($upper) === 2 && isset($countryMap[$upper])) {
                $name = $countryMap[$upper];
            } else {
                // try to map common forms like 'id' -> Indonesia
                if (isset($countryMap[$upper])) {
                    $name = $countryMap[$upper];
                } else {
                    $name = $rTrim;
                }
            }
            $applicantLocationRequirements[] = [
                "@type" => "Country",
                "name" => $name
            ];
        }

        // default fallback to Indonesia if empty (optional)
        if (empty($applicantLocationRequirements)) {
            $applicantLocationRequirements[] = [
                "@type" => "Country",
                "name" => "Indonesia"
            ];
        }

        //
        // baseSalary: build MonetaryAmount + QuantitativeValue according to available data
        //
        $baseSalary = null;
        $currency = $job->base_salary_currency ?? 'IDR';
        // normalize unit to accepted values: HOUR|DAY|WEEK|MONTH|YEAR
        $unitText = strtoupper($job->base_salary_unit ?? 'YEAR');
        $allowedUnits = ['HOUR','DAY','WEEK','MONTH','YEAR'];
        if (!in_array($unitText, $allowedUnits)) {
            // try to detect common variants
            if (strpos(strtolower($unitText), 'month') !== false) $unitText = 'MONTH';
            elseif (strpos(strtolower($unitText), 'day') !== false) $unitText = 'DAY';
            elseif (strpos(strtolower($unitText), 'week') !== false) $unitText = 'WEEK';
            elseif (strpos(strtolower($unitText), 'hour') !== false) $unitText = 'HOUR';
            else $unitText = 'YEAR';
        }

        // numeric min/max preferred (they are likely annual from TheirStack)
        $hasMin = !empty($job->base_salary_min) || $job->base_salary_min === 0 || $job->base_salary_min === '0';
        $hasMax = !empty($job->base_salary_max) || $job->base_salary_max === 0 || $job->base_salary_max === '0';

        if ($hasMin || $hasMax) {
            $qv = ["@type" => "QuantitativeValue", "unitText" => $unitText];
            if ($hasMin) $qv["minValue"] = (float)$job->base_salary_min;
            if ($hasMax) $qv["maxValue"] = (float)$job->base_salary_max;

            $baseSalary = [
                "@type" => "MonetaryAmount",
                "currency" => $currency,
                "value" => $qv
            ];
        } elseif (!empty($job->base_salary_string)) {
            // try to parse single numeric from string (e.g. "$40.00", "USD 40.00", "100000")
            $s = $job->base_salary_string;
            // capture first numeric token (allow commas, dots, k)
            if (preg_match('/(\d{1,3}(?:[,\.\d]{0,})\s*[kK]?)/', $s, $m)) {
                $num = $m[1];
                $isK = false;
                if (str_ends_with(strtolower($num), 'k')) { $isK = true; $num = substr($num, 0, -1); }
                $num = preg_replace('/[,\.\s]/','', $num);
                if (is_numeric($num)) {
                    $val = (float)$num;
                    if ($isK) $val *= 1000;
                    $baseSalary = [
                        "@type" => "MonetaryAmount",
                        "currency" => $currency,
                        "value" => [
                            "@type" => "QuantitativeValue",
                            "value" => $val,
                            "unitText" => $unitText
                        ]
                    ];
                }
            }
            // if parsing failed, we keep baseSalary null (we still display string on page)
        }

        //
        // Build JobPosting JSON-LD (telecommute)
        //
        $jobLd = [
            "@context" => "https://schema.org",
            "@type" => "JobPosting",
            "title" => $job->title,
            "description" => $job->description ? strip_tags($job->description) : null,
            "datePosted" => $datePosted,
            "validThrough" => $validThrough,
            "employmentType" => $employmentType,
            "hiringOrganization" => $job->hiring_organization ? [
                "@type" => "Organization",
                "name" => $job->hiring_organization,
                "sameAs" => $job->company_domain ? (strpos($job->company_domain, 'http') === 0 ? $job->company_domain : 'https://'.$job->company_domain) : null,
            ] : null,
            // For remote jobs, provide a Place with addressLocality 'Remote' or job_location if present
            "jobLocation" => [
                "@type" => "Place",
                "address" => [
                    "@type" => "PostalAddress",
                    "streetAddress" => null,
                    "addressLocality" => $job->job_location ? $job->job_location : 'Remote',
                    "addressCountry" => null
                ]
            ],
            // applicantLocationRequirements: single object or array
            "applicantLocationRequirements" => (count($applicantLocationRequirements) === 1) ? $applicantLocationRequirements[0] : $applicantLocationRequirements,
            // include baseSalary only if structured numeric info available
            "baseSalary" => $baseSalary,
            "directApply" => (bool)$job->direct_apply,
            "identifier" => [
                "@type" => "PropertyValue",
                "name" => $job->identifier_name ?? 'job_id',
                "value" => $job->identifier_value ?? (string)$job->id,
            ],
            // Explicitly mark as telecommute for Google
            "jobLocationType" => "TELECOMMUTE",
        ];

        // Remove nulls/top-level empties for cleanliness
        $jobLd = array_filter($jobLd, function($v) {
            return $v !== null && $v !== [];
        });

        // If baseSalary was null we remove the key entirely (array_filter above doesn't remove nested nulls)
        if (empty($jobLd['baseSalary'])) {
            unset($jobLd['baseSalary']);
        }

        $jobPostingJsonLd = json_encode($jobLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        //
        // RELATED JOBS: ambil loker terbaru (kecuali current)
        // urutkan berdasarkan date_posted desc, lalu posted_at desc, fallback ke created_at via latest()
        //
        $relatedJobs = Job::where('id', '<>', $job->id)
            ->orderByDesc('date_posted')
            ->orderByDesc('posted_at')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // Return view with job, generated JSON-LD, and related jobs
        return view('jobs.show', compact('job', 'jobPostingJsonLd', 'relatedJobs'));
    }
}

