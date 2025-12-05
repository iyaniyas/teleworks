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
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        $hasActivePackage = false;

        if ($companyId) {
            $now = Carbon::now();
            $hasActivePackage = Job::where('company_id', $companyId)
                ->where('is_paid', 1)
                ->where(function($q) use ($now) {
                    $q->whereNotNull('paid_until')->where('paid_until', '>', $now)
                      ->orWhereNotNull('expires_at')->where('expires_at', '>', $now);
                })->exists();
        }

        return view('employer.dashboard', compact('hasActivePackage'));
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

