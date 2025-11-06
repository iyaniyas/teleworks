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

        // Fallback tanggal agar tidak kosong
        $datePosted = $job->date_posted
            ? Carbon::parse($job->date_posted)->toDateString()
            : optional($job->created_at)->toDateString();

        $validThrough = $job->valid_through
            ? Carbon::parse($job->valid_through)->toDateString()
            : Carbon::parse($datePosted ?? now())->addDays(45)->toDateString();

        // Siapkan schema JobPosting (WFH-aware)
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'JobPosting',
            'title'    => $job->title ?? '',
            'description' => strip_tags((string) ($job->description ?? '')),
            'datePosted'  => $datePosted,
            'validThrough'=> $validThrough,
            'employmentType' => $job->employment_type ?: null,
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name'  => $job->hiring_organization ?: ($job->company ?: ''),
            ],
            'jobLocationType' => $job->is_remote ? 'TELECOMMUTE' : 'ONSITE',
            'jobLocation' => $job->is_remote ? null : [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job->job_location ?: ($job->location ?: ''),
                    'addressCountry'  => 'ID',
                ],
            ],
            'baseSalary' => ($job->base_salary_min || $job->base_salary_max) ? [
                '@type'    => 'MonetaryAmount',
                'currency' => $job->base_salary_currency ?: 'IDR',
                'value' => array_filter([
                    '@type'    => 'QuantitativeValue',
                    'minValue' => $job->base_salary_min,
                    'maxValue' => $job->base_salary_max,
                    'unitText' => $job->base_salary_unit ?: 'MONTH',
                ]),
            ] : null,
            'directApply' => (bool) $job->direct_apply,
            'identifier' => ($job->identifier_name || $job->identifier_value) ? [
                '@type' => 'PropertyValue',
                'name'  => $job->identifier_name,
                'value' => $job->identifier_value,
            ] : null,
        ];

        // Bersihkan key null agar schema rapi
        $schema = array_filter($schema, fn($v) => !is_null($v));

        return view('jobs.show', [
            'job' => $job,
            'jobPostingJsonLd' => json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}

