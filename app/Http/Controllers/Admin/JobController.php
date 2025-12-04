<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $status = $request->query('status');

        // Build list of relations to eager load only if they exist on the model
        $with = ['company']; // company is expected to exist
        // If Job model defines an owner() relationship method, eager-load it.
        if (method_exists(Job::class, 'owner')) {
            $with[] = 'owner';
        }
        // if company has owner relation and you want it: eager load nested relation safely
        if (method_exists(\App\Models\Company::class, 'owner') && ! in_array('company.owner', $with)) {
            // laravel supports nested eager loads like 'company.owner'
            $with[] = 'company.owner';
        }

        $jobsQuery = Job::query()
            ->when($q, fn($b) => $b->where('title', 'like', '%'.$q.'%'))
            ->when($status, fn($b) => $b->where('status', $status))
            ->with($with)
            ->orderBy('created_at','desc');

        $jobs = $jobsQuery->paginate(25);

        return view('admin.jobs.index', compact('jobs','q','status'));
    }

    public function edit(Job $job)
    {
        return view('admin.jobs.edit', compact('job'));
    }

    public function update(Request $request, Job $job)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:draft,pending,approved,rejected,paused',
        ]);

        $job->update($data);
	
        \Log::info('Admin job update request', ['request' => $request->all(), 'job_id' => $job->id]);

	return redirect()->route('admin.jobs.index')->with('success', 'Job updated.');

    }

    public function destroy(Job $job)
    {
        // prefer soft delete if model uses SoftDeletes trait
        $usesSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($job));

        if ($usesSoftDeletes) {
            $job->delete();
        } else {
            // permanent delete if soft deletes not available
            $job->delete();
        }

        return redirect()->route('admin.jobs.index')->with('success', 'Job removed.');
    }

}

