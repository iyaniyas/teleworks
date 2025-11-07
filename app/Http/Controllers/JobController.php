<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JobController extends Controller
{
    /**
     * (Opsional) Listing sederhana untuk tes.
     * Route contoh:
     *   Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Job::query();

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('company', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $jobs = $query->orderByDesc('date_posted')
                      ->orderByDesc('created_at')
                      ->paginate(20)
                      ->appends(['q' => $q]);

        return view('jobs.index', compact('jobs', 'q'));
    }

    /**
     * DETAIL: Ambil berdasarkan ID murni dari URL.
     * Route contoh:
     *   Route::get('/jobs/{id}', [JobController::class, 'show'])->name('jobs.show');
     */
    public function show(int $id)
    {
        // Ambil job berdasarkan ID, 404 jika tidak ada
        $job = Job::findOrFail($id);

        // --- fallback tanggal agar tidak kosong ---
        $datePosted = $job->date_posted
            ? Carbon::parse($job->date_posted)->toDateString()
            : optional($job->created_at)->toDateString();

        $validThrough = $job->valid_through
            ? Carbon::parse($job->valid_through)->toDateString()
            : Carbon::parse($datePosted ?? now())->addDays(45)->toDateString();

        // --- APPLY URL fallback: cek beberapa kolom + raw JSON ---
        $applyUrl = $job->apply_url
            ?? $job->final_url
            ?? $job->url
            ?? $job->source_url
            ?? $job->redirect_url
            ?? null;

        if (empty($applyUrl) && !empty($job->raw)) {
            $raw = is_array($job->raw) ? $job->raw : json_decode($job->raw, true);
            if (is_array($raw)) {
                $applyUrl = $raw['apply_url'] ?? $raw['final_url'] ?? $raw['url'] ?? $raw['source_url'] ?? $raw['redirect_url'] ?? null;
                if (empty($applyUrl) && !empty($raw['apply_urls']) && is_array($raw['apply_urls'])) {
                    $applyUrl = $raw['apply_urls'][0] ?? null;
                }
            }
        }

        // hiringOrganization
        $hiringOrg = $job->hiring_organization ?: ($job->company ?: null);

        // jobLocation: prefer lokasi yang ada di raw.locations jika tersedia
        $jobLocation = null;
        if (!empty($job->raw)) {
            $raw = is_array($job->raw) ? $job->raw : json_decode($job->raw, true);
            if (is_array($raw) && !empty($raw['locations']) && is_array($raw['locations'])) {
                $loc = $raw['locations'][0];

                // Build display string safely without ambiguous ternaries
                $parts = [];

                if (!empty($loc['display_name'])) {
                    $jobLocation = $loc['display_name'];
                } else {
                    if (!empty($loc['name'])) {
                        $parts[] = $loc['name'];
                    }
                    if (!empty($loc['admin1_name'])) {
                        $parts[] = $loc['admin1_name'];
                    }
                    if (!empty($loc['country_name'])) {
                        $parts[] = $loc['country_name'];
                    }
                    if (!empty($parts)) {
                        $jobLocation = trim(implode(', ', $parts));
                    }
                }
            }
        }

        if (empty($jobLocation)) {
            $jobLocation = $job->job_location ?: $job->location ?: null;
        }

        // applicantLocationRequirements
        $applicantLocationRequirements = [];
        if (!empty($job->applicant_location_requirements)) {
            if (is_array($job->applicant_location_requirements)) {
                $applicantLocationRequirements = $job->applicant_location_requirements;
            } else {
                // Pastikan json_decode dievaluasi dan fallback ke array kosong
                $decoded = json_decode($job->applicant_location_requirements, true);
                $applicantLocationRequirements = $decoded ?: [];
            }
        }

        // baseSalary
        $baseSalary = null;
        if ($job->base_salary_min || $job->base_salary_max) {
            $baseSalary = [
                '@type' => 'MonetaryAmount',
                'currency' => $job->base_salary_currency ?: 'IDR',
                'value' => array_filter([
                    '@type' => 'QuantitativeValue',
                    'minValue' => $job->base_salary_min,
                    'maxValue' => $job->base_salary_max,
                    'unitText' => $job->base_salary_unit ?: 'MONTH',
                ], fn($v) => !is_null($v) && $v !== ''),
            ];
        }

        $directApply = (bool) $job->direct_apply;
        $employmentType = $job->employment_type ?? null;
        $identifier = ($job->identifier_name || $job->identifier_value) ? [
            '@type' => 'PropertyValue',
            'name'  => $job->identifier_name,
            'value' => $job->identifier_value ?: ($job->source ? $job->source.'-'.$job->source_id : null),
        ] : null;

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'JobPosting',
            'title'    => $job->title ?? '',
            'description' => strip_tags((string) ($job->description ?? '')),
            'datePosted'  => $datePosted,
            'validThrough'=> $validThrough,
            'employmentType' => $employmentType ?: null,
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name'  => $hiringOrg ?: '',
            ],
            'jobLocationType' => $job->is_remote ? 'TELECOMMUTE' : 'ONSITE',
            // jobLocation: buat null jika remote
            'jobLocation' => $job->is_remote ? null : ($jobLocation ? [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $jobLocation,
                    'addressCountry'  => 'ID',
                ],
            ] : null),
            'baseSalary' => $baseSalary,
            'directApply' => $directApply,
            'identifier'  => $identifier,
            // tambahkan URL di schema jika tersedia (berguna untuk validasi dan indexing)
            'url' => $applyUrl ?: null,
            // applicantLocationRequirements (jika ada)
            'applicantLocationRequirements' => $applicantLocationRequirements ?: null,
        ];

        // Bersihkan key null agar schema rapi (rekursif sederhana)
        $schema = array_filter($schema, fn($v) => !is_null($v));

        return view('jobs.show', [
            'job' => $job,
            'jobPostingJsonLd' => json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}

