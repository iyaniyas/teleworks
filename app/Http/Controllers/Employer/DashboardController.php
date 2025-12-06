<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication; // if your application model differs, adapt
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $company = $this->resolveCompany($user);

        // default values
        $totalJobs = 0;
        $publishedJobs = 0;
        $totalApplications = 0;
        $newApplicants = 0;
        $recentJobs = collect();

        if ($company) {
            // Base query untuk semua job perusahaan ini
            $baseJobsQuery = Job::where('company_id', $company->id);

            // Hitung total job
            $totalJobs = (clone $baseJobsQuery)->count();

            // Hitung published job (boleh tambahkan filter expires_at kalau mau cuma yang aktif)
            $publishedJobs = (clone $baseJobsQuery)
                ->where('status', 'published')
                ->count();

            // Hitung aplikasi kalau model JobApplication ada
            if (class_exists(JobApplication::class)) {
                $jobIds = (clone $baseJobsQuery)->pluck('id');

                if ($jobIds->isNotEmpty()) {
                    $totalApplications = JobApplication::whereIn('job_id', $jobIds)->count();

                    $newApplicants = JobApplication::whereIn('job_id', $jobIds)
                        ->where('created_at', '>=', Carbon::now()->subDay())
                        ->count();
                }
            }

            // Lowongan terbaru untuk panel "Lowongan Terbaru"
            $recentJobs = (clone $baseJobsQuery)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        // hasActivePackage masih boleh dipakai kalau suatu saat mau,
        // tapi view sekarang fokus ke KPI & recentJobs.
        $hasActivePackage = false;
        if ($company) {
            $now = Carbon::now();
            $hasActivePackage = Job::where('company_id', $company->id)
                ->where('is_paid', 1)
                ->where(function ($q) use ($now) {
                    $q->whereNotNull('paid_until')->where('paid_until', '>', $now)
                      ->orWhereNotNull('expires_at')->where('expires_at', '>', $now);
                })->exists();
        }

        return view('employer.dashboard', compact(
            'company',
            'hasActivePackage',
            'totalJobs',
            'publishedJobs',
            'totalApplications',
            'newApplicants',
            'recentJobs'
        ));
    }

    /**
     * Resolve company yang berkaitan dengan user (pivot / owner / legacy)
     */
    protected function resolveCompany($user)
    {
        if (!$user) {
            return null;
        }

        // 1) pivot company_user
        if (method_exists($user, 'companies') && $user->companies()->exists()) {
            $company = $user->companies()->first();
            if ($company) {
                return $company;
            }
        }

        // 2) owner_id on companies
        $company = Company::where('owner_id', $user->id)->first();
        if ($company) {
            return $company;
        }

        // 3) company_id on user (if exists)
        if (!empty($user->company_id)) {
            return Company::find($user->company_id);
        }

        return null;
    }
}

