<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JobController extends Controller
{
    public function show($id)
    {
        // cari job via id atau fingerprint etc (disesuaikan)
        $job = Job::where('id', $id)->firstOrFail();

        // Build JSON-LD JobPosting (schema.org)
        // Note: take care to only include fields that exist
        $jobLd = [
            "@context" => "https://schema.org",
            "@type" => "JobPosting",
            "title" => $job->title,
            "description" => $job->description ? strip_tags($job->description) : null,
            "datePosted" => $job->date_posted ? Carbon::parse($job->date_posted)->toDateString() : null,
            "validThrough" => $job->valid_through ? Carbon::parse($job->valid_through)->toDateString() : null,
            "employmentType" => $job->employment_type ?? null,
            "hiringOrganization" => $job->hiring_organization ? [
                "@type" => "Organization",
                "name" => $job->hiring_organization,
                "sameAs" => ($job->company_domain ? (strpos($job->company_domain, 'http') === 0 ? $job->company_domain : 'https://'.$job->company_domain) : null),
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
            "applicantLocationRequirements" => null,
            "baseSalary" => null,
            "directApply" => (bool)$job->direct_apply,
            "identifier" => null,
            "jobLocationType" => $job->job_location_type ?? null,
        ];

        // applicantLocationRequirements (parse JSON if perlu)
        if ($job->applicant_location_requirements) {
            $decoded = json_decode($job->applicant_location_requirements, true);
            if (is_array($decoded)) {
                $jobLd['applicantLocationRequirements'] = $decoded;
            } else {
                $jobLd['applicantLocationRequirements'] = [$job->applicant_location_requirements];
            }
        }

        // baseSalary: prefer base_salary_string, else structured
        if (!empty($job->base_salary_string)) {
            $jobLd['baseSalary'] = $job->base_salary_string;
        } elseif (!empty($job->base_salary_min) || !empty($job->base_salary_max)) {
            $salary = [
                "@type" => "MonetaryAmount",
                "currency" => $job->base_salary_currency ?? 'IDR',
                "value" => [
                    "@type" => "QuantitativeValue",
                    "minValue" => $job->base_salary_min ? (float)$job->base_salary_min : null,
                    "maxValue" => $job->base_salary_max ? (float)$job->base_salary_max : null,
                    "unitText" => $job->base_salary_unit ?? 'YEAR',
                ]
            ];
            $jobLd['baseSalary'] = $salary;
        }

        // identifier
        if ($job->identifier_name || $job->identifier_value) {
            $jobLd['identifier'] = [
                "@type" => "PropertyValue",
                "name" => $job->identifier_name ?? null,
                "value" => $job->identifier_value ?? null,
            ];
        }

        // Remove null values from top-level to keep JSON-LD clean
        $jobLd = array_filter($jobLd, function($v) {
            return $v !== null && $v !== [];
        });

        $jobPostingJsonLd = json_encode($jobLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

        return view('jobs.show', compact('job', 'jobPostingJsonLd'));
    }
}

