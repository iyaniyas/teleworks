<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    /**
     * Apply to a job (store application)
     */
    public function apply(Request $request, $jobId)
    {
        $request->validate([
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB limit
            'cover_letter' => 'nullable|string|max:5000',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error','Silakan login untuk melamar.');
        }

        $job = Job::findOrFail($jobId);

        // prevent duplicate apply
        $exists = JobApplication::where('job_id', $job->id)->where('user_id', $user->id)->exists();
        if ($exists) {
            return back()->with('error','Anda sudah pernah melamar pekerjaan ini.');
        }

        $resumePath = null;
        if ($request->hasFile('resume')) {
            // store in storage/app/public/resumes/{jobId}/{userId}/
            $resumePath = $request->file('resume')->store("resumes/{$job->id}/{$user->id}", 'public');
        }

        $application = JobApplication::create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'resume_path' => $resumePath,
            'cover_letter' => $request->input('cover_letter'),
            'status' => 'applied',
        ]);

        // TODO: send notification/email to employer (later)
        return back()->with('success', 'Lamaran terkirim. Terima kasih!');
    }

    /**
     * Employer view: list applications for jobs the current user owns (company owner)
     */
    public function indexForEmployer(Request $request)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        // allow admin
        if ($user->hasRole('admin')) {
            $apps = JobApplication::with(['job','user'])->latest()->paginate(20);
            return view('employer.applications.index', compact('apps'));
        }

        // company user: find company ids this user belongs to (owner/recruiter)
        $companyIds = $user->companies()->pluck('companies.id')->toArray(); // requires User::companies relation

        $apps = JobApplication::with(['job','user'])
            ->whereHas('job', function($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            })
            ->latest()
            ->paginate(20);

        return view('employer.applications.index', compact('apps'));
    }

    /**
     * Employer change status of an application
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:applied,viewed,shortlisted,interview,rejected,hired']);

        $application = JobApplication::with('job.company')->findOrFail($id);

        $user = Auth::user();
        if (!$user) abort(403);

        // authorize: admin OR company owner/recruiter for application's job
        if (!$user->hasRole('admin')) {
            // user must belong to company owning the job
            $company = $application->job->company;
            if (!$company) abort(403);
            $belongs = $company->users()->where('user_id', $user->id)->exists();
            if (!$belongs) abort(403);
        }

        $application->status = $request->status;
        $application->save();

        return back()->with('success','Status aplikasi diperbarui.');
    }

    /**
     * Download resume (only employer or admin or applicant)
     */
    public function downloadResume($id)
    {
        $app = JobApplication::with('job.company','user')->findOrFail($id);
        $user = Auth::user();
        if (!$user) abort(403);

        // applicant can download their own resume
        if ($user->id === $app->user_id) {
            if (!$app->resume_path) abort(404);
            return Storage::disk('public')->download($app->resume_path);
        }

        // admin can
        if ($user->hasRole('admin')) {
            if (!$app->resume_path) abort(404);
            return Storage::disk('public')->download($app->resume_path);
        }

        // employer: check company membership
        $company = $app->job->company;
        if (!$company) abort(403);
        $belongs = $company->users()->where('user_id', $user->id)->exists();
        if (!$belongs) abort(403);

        if (!$app->resume_path) abort(404);
        return Storage::disk('public')->download($app->resume_path);
    }
}

