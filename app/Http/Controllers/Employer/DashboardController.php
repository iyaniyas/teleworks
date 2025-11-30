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

        $jobIds = $company ? $company->jobs()->pluck('id') : collect();

        $totalJobs = $company ? $company->jobs()->count() : 0;
        $publishedJobs = $company ? $company->jobs()->where('status', 'published')->count() : 0;

        // Count applications using JobApplication or JobApplication model in your app
        $totalApplications = 0;
        try {
            // Try common names: JobApplication, Application, JobApplicationModel
            if (class_exists(\App\Models\JobApplication::class)) {
                $totalApplications = \App\Models\JobApplication::whereIn('job_id', $jobIds)->count();
            } elseif (class_exists(\App\Models\Application::class)) {
                $totalApplications = \App\Models\Application::whereIn('job_id', $jobIds)->count();
            } elseif (class_exists(\App\Models\Job::class)) {
                // fallback: count 0
                $totalApplications = 0;
            }
        } catch (\Throwable $e) {
            $totalApplications = 0;
        }

        $newApplicants = 0;
        try {
            $yesterday = Carbon::now()->subDay();
            if (class_exists(\App\Models\JobApplication::class)) {
                $newApplicants = \App\Models\JobApplication::whereIn('job_id', $jobIds)
                    ->where('created_at', '>=', $yesterday)->count();
            } elseif (class_exists(\App\Models\Application::class)) {
                $newApplicants = \App\Models\Application::whereIn('job_id', $jobIds)
                    ->where('created_at', '>=', $yesterday)->count();
            }
        } catch (\Throwable $e) {
            $newApplicants = 0;
        }

        $recentJobs = $company ? $company->jobs()->latest()->limit(6)->get() : collect();

        return view('employer.dashboard', compact(
            'company','totalJobs','publishedJobs','totalApplications','newApplicants','recentJobs'
        ));
    }

    protected function resolveCompany($user)
    {
        // 1) pivot company_user
        $company = $user->companies()->first();
        if ($company) return $company;

        // 2) owner_id on companies
        $company = Company::where('owner_id', $user->id)->first();
        if ($company) return $company;

        // 3) company_id on user (if exists)
        if (!empty($user->company_id)) {
            return Company::find($user->company_id);
        }

        return null;
    }
}

