<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    public function __construct()
    {
        // keep original middleware (auth + role guard)
        $this->middleware(['auth','role:company|admin']);
    }

    /**
     * Resolve company yang berkaitan dengan user (pivot / owner / legacy)
     */
    protected function resolveCompany($user)
    {
        try {
            if (!$user) return null;

            if (method_exists($user, 'companies') && $user->companies()->exists()) {
                $c = $user->companies()->first();
                if ($c) return $c;
            }

            $c = Company::where('owner_id', $user->id)->first();
            if ($c) return $c;

            if (!empty($user->company_id)) {
                $c = Company::find($user->company_id);
                if ($c) return $c;
            }
        } catch (\Throwable $e) {
            Log::warning('JobController::resolveCompany failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * List jobs for employer
     */
    public function index(Request $request)
    {
        $company = $this->resolveCompany($request->user());
        if (!$company) {
            return redirect()->route('companies.create')->with('error','Profil perusahaan tidak ditemukan.');
        }

        $jobs = Job::where('company_id', $company->id)
                    ->orderByDesc('created_at')
                    ->paginate(15);

        return view('employer.jobs.index', compact('jobs','company'));
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $company = $this->resolveCompany($request->user());
        if (!$company) {
            return redirect()->route('companies.create')->with('error','Silakan buat profil perusahaan terlebih dahulu.');
        }

        return view('employer.jobs.create', compact('company'));
    }

    /**
     * Show single job in employer area
     */
    public function show(Job $job)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            $company = $this->resolveCompany($user);

            if (!$company || $company->id !== $job->company_id) {
                abort(404);
            }
        }

        return view('employer.jobs.show', compact('job'));
    }

    /**
     * Store new job (no slug). Note: location is required and used as job_location.
     *
     * Behavior:
     * - Job SELALU disimpan sebagai draft.
     * - Semua field penting (lokasi, remote, gaji, dsb) langsung ikut tersimpan.
     * - Setelah pembayaran sukses, webhook akan mengubah status => published.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $company = $this->resolveCompany($user);

        if (!$company) {
            return redirect()->route('companies.create')
                ->with('error','Silakan buat profil perusahaan terlebih dahulu.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'description_html' => 'nullable|string',

            'location' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'employment_type' => 'required|string|max:255',

            'applicant_location_requirements' => 'required',
            'is_remote' => 'required|in:0,1',

            'base_salary_min' => 'required|numeric',
            'base_salary_max' => 'required|numeric',

            'date_posted' => 'nullable|date',
            'expires_at' => 'required|date',

            'apply_via' => 'required|in:teleworks,external',
            'apply_contact' => 'nullable|string|max:1000',
        ]);

        // Normalisasi applicant_location_requirements (sama pola dengan update())
        $appr = $request->input('applicant_location_requirements');
        if (is_array($appr)) {
            $arr = array_values(array_filter(array_map('trim', $appr)));
        } else {
            $arr = preg_split('/[\r\n,]+/', (string)$appr);
            $arr = array_values(array_filter(array_map('trim', $arr)));
        }

        // Lokasi kerja & tipe lokasi
        $jobLocation = $validated['location'];
        $jobLocationType = ((int)$validated['is_remote'] === 1) ? 'Remote' : 'On-site';

        $datePosted = $validated['date_posted'] ?: now()->toDateString();
        $expiresAt  = $validated['expires_at'];

        $data = [
            'company_id' => $company->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'description_html' => $validated['description_html'] ?? null,

            'location' => $validated['location'],
            'job_location' => $jobLocation,
            'job_location_type' => $jobLocationType,
            'type' => $validated['type'] ?? null,
            'employment_type' => $validated['employment_type'],

            'applicant_location_requirements' => json_encode($arr),
            'is_remote' => (int)$validated['is_remote'],
            'is_wfh' => ((int)$validated['is_remote'] === 1)
                ? 1
                : ((stripos($validated['type'] ?? '', 'wfh') !== false) ? 1 : 0),

            'base_salary_min' => $validated['base_salary_min'],
            'base_salary_max' => $validated['base_salary_max'],

            'date_posted' => $datePosted,
            'valid_through' => $expiresAt,
            'expires_at' => $expiresAt,

            'status' => 'draft',
            'hiring_organization' => $company->name ?? $company->domain ?? null,
        ];

        $job = Job::create($data);

        // Apply handling (samakan dengan update())
        $applyVia = $validated['apply_via'] ?? 'external';
        $applyContact = trim($validated['apply_contact'] ?? '');

        if ($applyVia === 'teleworks') {
            $job->direct_apply = 1;
            $job->apply_url = url('/loker/' . $job->id);
        } else {
            $job->direct_apply = 0;
            $job->apply_url = $applyContact ?: $job->apply_url;
        }
        $job->save();

        return redirect()->route('employer.jobs.show', $job->id)
            ->with('success','Lowongan tersimpan sebagai draft. Silakan pilih paket untuk publish.');
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        if (method_exists($this, 'authorize')) {
            try { $this->authorize('update', $job); } catch (\Throwable $e) {}
        }

        if ($job->is_imported) {
            return back()->with('error','Lowongan ini berasal dari importer dan tidak dapat diedit melalui dashboard employer.');
        }

        // decode applicant_location_requirements for form
        $appr = $job->applicant_location_requirements;
        if (is_string($appr)) {
            $apprArr = json_decode($appr, true);
            if (!is_array($apprArr)) {
                $apprArr = preg_split('/[\r\n,]+/', $appr);
                $apprArr = array_values(array_filter(array_map('trim', $apprArr)));
            }
        } elseif (is_array($appr)) {
            $apprArr = $appr;
        } else {
            $apprArr = [];
        }

        return view('employer.jobs.edit', compact('job','apprArr'));
    }

    /**
     * Update job
     */
    public function update(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        if (method_exists($this, 'authorize')) {
            try { $this->authorize('update', $job); } catch (\Throwable $e) {}
        }

        if ($job->is_imported) {
            return back()->with('error','Lowongan ini berasal dari importer dan tidak dapat diedit melalui dashboard employer.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'description_html' => 'nullable|string',
            'location' => 'required|string|max:255', // required now
            'type' => 'nullable|string|max:255',
            'employment_type' => 'required|string|max:255',
            'applicant_location_requirements' => 'required',
            'is_remote' => 'required|in:0,1',
            'base_salary_min' => 'required|numeric',
            'base_salary_max' => 'required|numeric',
            'date_posted' => 'nullable|date',
            'expires_at' => 'required|date',
            'apply_via' => 'required|in:teleworks,external',
            'apply_contact' => 'nullable|string|max:1000',
            'status' => ['nullable', Rule::in(['draft','published','expired','archived'])],
        ]);

        // normalize applicant_location_requirements
        $appr = $request->input('applicant_location_requirements');
        if (is_array($appr)) {
            $arr = array_values(array_filter(array_map('trim', $appr)));
        } else {
            $arr = preg_split('/[\r\n,]+/', (string)$appr);
            $arr = array_values(array_filter(array_map('trim', $arr)));
        }

        // infer job_location and type
        $jobLocation = $validated['location'];
        $jobLocationType = ((int)$validated['is_remote'] === 1) ? 'Remote' : 'On-site';

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'description_html' => $validated['description_html'] ?? $job->description_html,
            'location' => $validated['location'],
            'job_location' => $jobLocation,
            'job_location_type' => $jobLocationType,
            'type' => $validated['type'] ?? $job->type,
            'employment_type' => $validated['employment_type'],
            'applicant_location_requirements' => json_encode($arr),
            'is_remote' => (int)$validated['is_remote'],
            'is_wfh' => ((int)$validated['is_remote'] === 1) ? 1 : ((stripos($validated['type'] ?? '', 'wfh') !== false) ? 1 : 0),
            'base_salary_min' => $validated['base_salary_min'],
            'base_salary_max' => $validated['base_salary_max'],
            'date_posted' => $validated['date_posted'] ? $validated['date_posted'] : ($job->date_posted ?? now()->toDateString()),
            'valid_through' => $validated['expires_at'],
            'expires_at' => $validated['expires_at'],
            'status' => $validated['status'] ?? $job->status,
            'hiring_organization' => $job->hiring_organization ?? $job->company ?? null,
        ];

        $job->update($data);

        // apply handling
        $applyVia = $validated['apply_via'] ?? 'external';
        $applyContact = trim($validated['apply_contact'] ?? '');
        if ($applyVia === 'teleworks') {
            $job->direct_apply = 1;
            $job->apply_url = url('/loker/' . $job->id);
        } else {
            $job->direct_apply = 0;
            $job->apply_url = $applyContact ?: $job->apply_url;
        }
        $job->save();

        return redirect()->route('employer.jobs.index')->with('success', 'Lowongan diperbarui.');
    }

    /**
     * Destroy job
     */
    public function destroy(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        if (method_exists($this, 'authorize')) {
            try { $this->authorize('update', $job); } catch (\Throwable $e) {}
        }

        if ($job->is_imported) {
            return back()->with('error','Tidak dapat menghapus job yang diimpor.');
        }

        $job->delete();
        return back()->with('success','Lowongan dihapus.');
    }

    /**
     * Publish a draft job (action).
     */
    public function publish(Request $request, Job $job)
    {
        $user = $request->user();
        $company = $this->resolveCompany($user);

        if (!$company || $job->company_id !== $company->id) {
            return back()->with('error', 'Anda tidak berwenang untuk mem-publish lowongan ini.');
        }

        if (!$company->hasActivePackage()) {
            return redirect()->route('purchase.create')->with('error', 'Anda perlu paket aktif untuk mem-publish lowongan.');
        }

        $job->status = 'published';

        $payment = $company->activePackage();
        if ($payment) {
            $job->is_paid = 1;
            if (isset($payment->expires_at)) {
                $job->paid_until = $payment->expires_at;
            }
            if (isset($payment->package_id)) {
                $job->package_id = $payment->package_id;
            }
        }

        $job->save();

        return redirect()->route('employer.jobs.index')->with('success', 'Lowongan berhasil dipublikasikan.');
    }
}

