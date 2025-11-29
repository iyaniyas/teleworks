<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JobPolicy
{

use HandlesAuthorization;

    /**
     * Super-admin override: jika user admin, berikan semua ability.
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true; // admin boleh semua
        }
    }

    /**
     * Determine whether the user can update the job.
     */
    public function update(User $user, Job $job)
    {
        // 1) jika user punya role company, cek ownership:
        if ($user->hasRole('company')) {
            // Jika job memiliki company_id
            if ($job->company_id) {
                // Cara 1: cek kalau user adalah owner (owner_id on companies)
                if ($job->company->owner_id === $user->id) {
                    return true;
                }

                // Cara 2: cek kalau user ada pada company->users pivot (recruiter)
                if ($job->company->users->contains('id', $user->id)) {
                    return true;
                }
            }
        }

        // default deny
        return false;
    }

    /**
     * Determine whether the user can delete the job.
     */
    public function delete(User $user, Job $job)
    {
        // Reuse update logic
        return $this->update($user, $job);
    }	
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Job $job): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Job $job): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Job $job): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Job $job): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Job $job): bool
    {
        //
    }
}
