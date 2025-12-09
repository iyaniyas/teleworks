<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        $companiesPending = Company::where('is_verified', false)->count();
        $suspended = Company::where('is_suspended', true)->count();
        $jobsPending = Job::where('status', 'pending')->count();
	$openReports = Report::where('status', 'open')->count();

	        // USER STATS
        $totalUsers = User::count();
        $admins = User::role('admin')->count();
        $companies = User::role('company')->count();
        $seekers = User::role('job_seeker')->count();

        return view('admin.dashboard', compact(
            'companiesPending',
            'suspended',
            'jobsPending',
            'openReports',
            'totalUsers',
            'admins',
            'companies',
            'seekers'
        ));

        return view('admin.dashboard', compact('companiesPending', 'suspended', 'jobsPending', 'openReports'));
    }
}

