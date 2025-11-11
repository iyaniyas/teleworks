<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JobController extends Controller
{
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

        // applicantLocationRequirements: try decode or fallback to Indonesia
        $appReq = $job->applicant_location_requirements;
        if (is_null($appReq)) {
            $appReq = ['Indonesia'];
        } elseif (!is_array($appReq)) {
            $decoded = json_decode($appReq, true);
            $appReq = is_array($decoded) ? $decoded : [$appReq];
        }

        // baseSalary: prefer string else structured object if min/max exists
        $baseSalary = null;
        if (!empty($job->base_salary_string)) {
            $baseSalary = $job->base_salary_string;
        } elseif (!empty($job->base_salary_min) || !empty($job->base_salary_max)) {
            $baseSalary = [
                "@type" => "MonetaryAmount",
                "currency" => $job->base_salary_currency ?? 'IDR',
                "value" => [
                    "@type" => "QuantitativeValue",
                    "minValue" => $job->base_salary_min ? (float)$job->base_salary_min : null,
                    "maxValue" => $job->base_salary_max ? (float)$job->base_salary_max : null,
                    "unitText" => $job->base_salary_unit ?? 'YEAR'
                ]
            ];
        }

        // identifier: prefer identifier_name/value else fallback to job id
        $identifierName = $job->identifier_name ?? 'job_id';
        $identifierValue = $job->identifier_value ?? (string)$job->id;

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
            "jobLocation" => $job->job_location ? [
                "@type" => "Place",
                "address" => [
                    "@type" => "PostalAddress",
                    "streetAddress" => null,
                    "addressLocality" => $job->job_location,
                    "addressCountry" => null
                ]
            ] : null,
            "applicantLocationRequirements" => $appReq,
            "baseSalary" => $baseSalary,
            "directApply" => (bool)$job->direct_apply,
            "identifier" => [
                "@type" => "PropertyValue",
                "name" => $identifierName,
                "value" => $identifierValue,
            ],
            "jobLocationType" => $job->job_location_type ?? ($job->is_remote ? 'Remote' : 'Onsite'),
        ];

        // Remove nulls/top-level empties for cleanliness
        $jobLd = array_filter($jobLd, function($v) {
            return $v !== null && $v !== [];
        });

        $jobPostingJsonLd = json_encode($jobLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return view('jobs.show', compact('job', 'jobPostingJsonLd'));
    }
}

