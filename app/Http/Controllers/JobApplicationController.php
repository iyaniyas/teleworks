<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AiClient;

class JobApplicationController extends Controller
{
    /**
     * Apply to a job (store application)
     */
    public function apply(Request $request, $jobId, AiClient $ai)
    {
        $request->validate([
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB
            'cover_letter' => 'nullable|string|max:5000',
        ]);

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan login untuk melamar.');
        }

        $job = Job::findOrFail($jobId);

        // prevent duplicate apply
        $exists = JobApplication::where('job_id', $job->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($exists) {
            return back()->with('error', 'Anda sudah pernah melamar pekerjaan ini.');
        }

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $disk = config('filesystems.resumes_disk', 'public');
            $resumePath = $request->file('resume')->store("resumes/{$job->id}/{$user->id}", $disk);
        }

        $application = JobApplication::create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'resume_path' => $resumePath,
            'cover_letter' => $request->input('cover_letter'),
            'status' => 'applied',
            // kolom applied_at belum ada di tabel, jadi tidak dipakai di sini
        ]);

        // AI scoring: nilai pelamar berdasarkan job & CV/Profile
        try {
            $ai->scoreApplication($application);
        } catch (\Throwable $e) {
            Log::error('JobApplicationController@apply AI scoring error', [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
            ]);
        }

        return back()->with('success', 'Lamaran terkirim. Terima kasih!');
    }

    /**
     * Employer view: list applications for jobs the current user owns (company owner)
     */
    public function indexForEmployer(Request $request)
    {
        $user = Auth::user();
        if (! $user) abort(403);

        // admin sees all
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $apps = JobApplication::with(['job','user'])
                ->orderByDesc('ai_score')     // urut berdasarkan skor AI dulu
                ->orderByDesc('created_at')   // lalu terbaru
                ->paginate(20);

            return view('employer.applications.index', compact('apps'));
        }

        $companyIds = [];

        if (isset($user->company_id) && $user->company_id) {
            $companyIds[] = $user->company_id;
        }

        if (method_exists($user, 'companies')) {
            try {
                $fromRel = $user->companies()->pluck('companies.id')->toArray();
                if (! empty($fromRel)) $companyIds = array_merge($companyIds, $fromRel);
            } catch (\Throwable $e) {
                Log::debug("indexForEmployer: unable to read user->companies relation", ['err' => $e->getMessage()]);
            }
        }

        if (empty($companyIds)) {
            abort(403);
        }

        $apps = JobApplication::with(['job','user'])
            ->whereHas('job', function($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            })
            ->orderByDesc('ai_score')     // prioritas: skor AI tertinggi
            ->orderByDesc('created_at')   // kalau sama, yang terbaru dulu
            ->paginate(20);

        return view('employer.applications.index', compact('apps'));
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:applied,viewed,shortlisted,interview,rejected,hired',
        ]);

        $application = JobApplication::with('job')->findOrFail($id);

        $user = Auth::user();
        if (! $user) abort(403);

        $allowed = false;

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $allowed = true;
        }

        if (! $allowed && method_exists($user, 'companies')) {
            try {
                if ($user->companies()->where('companies.id', $application->job->company_id)->exists()) {
                    $allowed = true;
                }
            } catch (\Throwable $e) {
                Log::debug('changeStatus: user->companies check failed', ['err' => $e->getMessage()]);
            }
        }

        if (! $allowed) {
            try {
                $company = $application->job->company;
                if ($company && method_exists($company, 'users') && $company->users()->where('user_id', $user->id)->exists()) {
                    $allowed = true;
                }
            } catch (\Throwable $e) {
                Log::debug('changeStatus: company->users check failed', ['err' => $e->getMessage()]);
            }
        }

        if (! $allowed) {
            Log::warning('Unauthorized changeStatus attempt', [
                'actor_id' => $user->id ?? null,
                'application_id' => $application->id,
                'job_id' => $application->job->id ?? null,
            ]);
            abort(403, 'Tidak diizinkan untuk mengubah status lamaran ini.');
        }

        $application->status = $request->input('status');
        $application->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'status' => $application->status]);
        }

        return back()->with('success', 'Status aplikasi diperbarui.');
    }

    public function downloadResume(Request $request, $id)
    {
        $app = JobApplication::with('job.company','user')->findOrFail($id);
        $user = Auth::user();
        if (! $user) abort(403);

        if ($user->id === $app->user_id) {
            if (! $app->resume_path) abort(404);
            $disk = config('filesystems.resumes_disk', 'public');
            return Storage::disk($disk)->download($app->resume_path);
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            if (! $app->resume_path) abort(404);
            $disk = config('filesystems.resumes_disk', 'public');
            return Storage::disk($disk)->download($app->resume_path);
        }

        $allowed = false;

        if (method_exists($user, 'companies')) {
            try {
                if ($user->companies()->where('companies.id', $app->job->company_id)->exists()) {
                    $allowed = true;
                }
            } catch (\Throwable $e) {
                Log::debug('downloadResume: user->companies check failed', ['err' => $e->getMessage()]);
            }
        }

        if (! $allowed) {
            try {
                $company = $app->job->company;
                if ($company && method_exists($company, 'users') && $company->users()->where('user_id', $user->id)->exists()) {
                    $allowed = true;
                }
            } catch (\Throwable $e) {
                Log::debug('downloadResume: company->users check failed', ['err' => $e->getMessage()]);
            }
        }

        if (! $allowed) {
            Log::warning('Unauthorized resume download', [
                'actor_id' => $user->id ?? null,
                'application_id' => $app->id,
                'job_id' => $app->job->id ?? null,
            ]);
            abort(403, 'Tidak diizinkan untuk mengunduh file ini.');
        }

        if (! $app->resume_path) abort(404);

        $disk = config('filesystems.resumes_disk', 'public');

        Log::info('Resume download check', [
            'disk' => $disk,
            'path' => $app->resume_path,
            'exists' => Storage::disk($disk)->exists($app->resume_path),
            'actor_id' => $user->id ?? null,
        ]);

        if (! Storage::disk($disk)->exists($app->resume_path)) {
            abort(404, 'File resume tidak ditemukan di storage.');
        }

        $orig = basename($app->resume_path);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $cleanUser = isset($app->user->name) ? preg_replace('/\s+/', '_', strtolower($app->user->name)) : 'applicant';
        $downloadName = "{$cleanUser}_cv." . ($ext ?: 'pdf');

        return Storage::disk($disk)->download($app->resume_path, $downloadName);
    }
}

