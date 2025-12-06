<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Job;
// Jika model aplikasi berbeda, nanti kita handle dinamis di bawah
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Kumpulkan semua company yang terkait user:
        // - pivot company_user
        // - owner_id
        // - user->company_id
        $companies = collect();

        if ($user) {
            // 1) pivot company_user (jika relasi ada)
            if (method_exists($user, 'companies')) {
                $pivotCompanies = $user->companies()->get();
                if ($pivotCompanies && $pivotCompanies->count() > 0) {
                    $companies = $companies->merge($pivotCompanies);
                }
            }

            // 2) owner_id
            $ownerCompany = Company::where('owner_id', $user->id)->first();
            if ($ownerCompany && ! $companies->contains('id', $ownerCompany->id)) {
                $companies->push($ownerCompany);
            }

            // 3) company_id langsung di user (legacy)
            if (!empty($user->company_id)) {
                $directCompany = Company::find($user->company_id);
                if ($directCompany && ! $companies->contains('id', $directCompany->id)) {
                    $companies->push($directCompany);
                }
            }
        }

        $companies = $companies->unique('id')->values();

        // Company utama yang dipakai untuk statistik
        $company = $companies->first();

        // Hitung apakah ada paket aktif (dari salah satu company)
        $hasActivePackage = $companies->contains(function ($c) {
            return method_exists($c, 'hasActivePackage') && $c->hasActivePackage();
        });

        // Siapkan statistik default
        $totalJobs = 0;
        $publishedJobs = 0;
        $totalApplications = 0;
        $newApplicants = 0;
        $recentJobs = collect();

        if ($company) {
            $jobsQuery = Job::where('company_id', $company->id);

            $totalJobs = $jobsQuery->count();
            $publishedJobs = (clone $jobsQuery)->where('status', 'published')->count();

            $jobIds = (clone $jobsQuery)->pluck('id');
            if ($jobIds->count() > 0) {

                // Gunakan model aplikasi yang tersedia
                if (class_exists(\App\Models\JobApplication::class)) {
                    $appModel = \App\Models\JobApplication::class;
                } elseif (class_exists(\App\Models\Application::class)) {
                    $appModel = \App\Models\Application::class;
                } else {
                    $appModel = null;
                }

                if ($appModel) {
                    $totalApplications = $appModel::whereIn('job_id', $jobIds)->count();
                    $newApplicants = $appModel::whereIn('job_id', $jobIds)
                        ->where('created_at', '>=', now()->subDay())
                        ->count();
                }
            }

            $recentJobs = (clone $jobsQuery)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        // Paket aktif untuk company utama (jika ada)
        $activePkg = $company && method_exists($company, 'activePackage')
            ? $company->activePackage()
            : null;

        return view('employer.dashboard', [
            'companies'          => $companies,
            'company'            => $company,
            'activePkg'          => $activePkg,
            'hasActivePackage'   => $hasActivePackage,
            'totalJobs'          => $totalJobs,
            'publishedJobs'      => $publishedJobs,
            'totalApplications'  => $totalApplications,
            'newApplicants'      => $newApplicants,
            'recentJobs'         => $recentJobs,
        ]);
    }
}

