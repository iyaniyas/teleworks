<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobApplication as Application;

class ApplicantController extends Controller
{
    public function index($jobId)
    {
        $job = Job::findOrFail($jobId);
        $this->authorize('viewApplicants', $job);

        // Employer lihat ranking AI (ai_score tertinggi di atas)
        $apps = $job->applications()
            ->with('user')
            ->orderByDesc('ai_score')
            ->paginate(25);

        return view('employer.applicants.index', compact('job', 'apps'));
    }

    public function updateStatus(Request $request, Application $application)
    {
        // authorize: only company owning the job can update
        $job = $application->job;
        $this->authorize('manage', $job);

        $request->validate([
            'status' => 'required|in:new,reviewed,shortlisted,rejected,hired',
        ]);

        $application->status = $request->status;
        $application->save();

        return back()->with('success', 'Status pelamar diperbarui.');
    }

    public function addNote(Request $request, Application $application)
    {
        $job = $application->job;
        $this->authorize('manage', $job);

        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $meta = $application->meta ?? [];
        $metaNotes = $meta['notes'] ?? [];

        $metaNotes[] = [
            'user_id'    => auth()->id(),
            'note'       => $request->note,
            'created_at' => now()->toDateTimeString(),
        ];

        $meta['notes'] = $metaNotes;
        $application->meta = $meta;
        $application->save();

        return back()->with('success', 'Catatan ditambahkan pada pelamar.');
    }

    public function aiSummary(Job $job)
	{
    $this->authorize('viewApplicants', $job);

    $summary = $job->buildApplicantsAiSummary();

    return view('employer.applicants.ai_summary', [
        'job' => $job,
        'summary' => $summary,
    ]);
	}

}

