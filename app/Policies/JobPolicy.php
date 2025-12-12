<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create jobs for a company.
     * Allowed if the user belongs to at least one company (pivot)
     * OR is owner of a company.
     */
    public function create(User $user)
    {
        // user belongs to any company via pivot
        if ($user->companies()->exists()) {
            return true;
        }

        // or user is owner of any company
        if (Company::where('owner_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update/manage a job.
     * Allowed if job->company is owned by user (owner_id)
     * OR user belongs to company's pivot.
     */
    public function update(User $user, Job $job)
    {
        // Admin handled in before()

        // If job has no company -> deny
        if (!$job->company_id) {
            return false;
        }

        // Owner check
        $company = Company::find($job->company_id);
        if ($company && $company->owner_id == $user->id) {
            return true;
        }

        // Pivot membership check
        return $user->companies()
            ->where('companies.id', $job->company_id)
            ->exists();
    }

    /**
     * Determine whether the user can view applicants of a job.
     * Read-only access.
     */
    public function viewApplicants(User $user, Job $job)
    {
        // Admin handled in before()

        if (!$job->company_id) {
            return false;
        }

        // Owner company
        $company = Company::find($job->company_id);
        if ($company && $company->owner_id == $user->id) {
            return true;
        }

        // Member company via pivot
        return $user->companies()
            ->where('companies.id', $job->company_id)
            ->exists();
    }

    /**
     * Prevent editing imported jobs by non-admins.
     * Allow admin via before().
     */
    public function safeManage(User $user, Job $job)
    {
        if ($job->is_imported) {
            return false;
        }

        return $this->update($user, $job);
    }
}

