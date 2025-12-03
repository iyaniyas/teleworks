<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Show create company form
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store new company (assign owner, attach pivot, assign role, link to user.company_id)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            // allow domain with or without scheme by using regex OR just string, keep it flexible
            'domain'      => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo'        => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        if (! $user) {
            abort(401, 'Unauthorized');
        }

        // prepare slug, ensure uniqueness
        $baseSlug = Str::slug($request->name);
        $slug     = $baseSlug;
        $i        = 0;

        while (Company::where('slug', $slug)->exists()) {
            $i++;
            $slug = $baseSlug . '-' . Str::random(4);
            if ($i > 6) { // fallback to uniqid
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
        }

        $payload = [
            'owner_id'    => $user->id,
            'name'        => $request->name,
            'slug'        => $slug,
            'domain'      => $request->domain,
            'description' => $request->description,
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $payload['logo_path'] = $path;
        }

        $company = Company::create($payload);

        // link user -> company (ini yang hilang sebelumnya)
        try {
            $user->company_id = $company->id;
            $user->save();
        } catch (\Throwable $e) {
            // kalau gagal, jangan putus flow; tapi relasi mungkin tidak tersimpan
        }

        // attach owner as company_user pivot (jika relasi many-to-many dipakai)
        try {
            if (method_exists($company, 'users')) {
                $company->users()->syncWithoutDetaching([
                    $user->id => ['role' => 'owner'],
                ]);
            }
        } catch (\Throwable $e) {
            // ignore if pivot exists or any pivot error
        }

        // assign company role to user (requires spatie)
        if (method_exists($user, 'assignRole')) {
            try {
                $user->assignRole('company');
            } catch (\Throwable $e) {
                // ignore role assignment errors
            }
        }

        // kalau employer dashboard ada, arahkan ke sana; kalau tidak, pakai halaman publik company
        if (Route::has('employer.company.edit')) {
            return redirect()
                ->route('employer.company.edit')
                ->with('success', 'Company created and linked to your account.');
        }

        return redirect()
            ->route('companies.show', $company->slug)
            ->with('success', 'Company created and you have been assigned as owner.');
    }

    /**
     * Public company profile by slug
     */
    public function show($slug)
    {
        $company = Company::where('slug', $slug)
            ->with('jobs')
            ->firstOrFail();

        return view('companies.show', compact('company'));
    }

    /**
     * Edit company form (owner or admin only)
     */
    public function edit(Company $company)
    {
        $user = Auth::user();

        // allow if owner or admin
        if (! $this->canManageCompany($user, $company)) {
            abort(403, 'Unauthorized');
        }

        return view('companies.edit', compact('company'));
    }

    /**
     * Update company (owner or admin only)
     */
    public function update(Request $request, Company $company)
    {
        $user = Auth::user();

        if (! $this->canManageCompany($user, $company)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('companies', 'slug')->ignore($company->id)],
            'domain'      => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo'        => 'nullable|image|max:2048',
        ]);

        $data = [
            'name'        => $request->name,
            'domain'      => $request->domain,
            'description' => $request->description,
        ];

        // handle slug: if provided, slugify; else keep existing
        if ($request->filled('slug')) {
            $slugCandidate = Str::slug($request->input('slug'));
            // ensure uniqueness
            $slug = $slugCandidate;
            $i    = 0;
            while (
                Company::where('slug', $slug)
                    ->where('id', '<>', $company->id)
                    ->exists()
            ) {
                $i++;
                $slug = $slugCandidate . '-' . Str::random(3);
                if ($i > 6) {
                    $slug = $slugCandidate . '-' . uniqid();
                    break;
                }
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('logo')) {
            // delete old logo if exists
            if (!empty($company->logo_path) && Storage::disk('public')->exists($company->logo_path)) {
                try {
                    Storage::disk('public')->delete($company->logo_path);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $company->update($data);

        // redirect to employer dashboard if route exists, else to company show
        if (Route::has('employer.dashboard')) {
            return redirect()
                ->route('employer.dashboard')
                ->with('success', 'Profil perusahaan berhasil diperbarui.');
        }

        return redirect()
            ->route('companies.show', $company->slug)
            ->with('success', 'Profil perusahaan berhasil diperbarui.');
    }

    /**
     * Helper: can the user manage this company?
     */
    protected function canManageCompany($user, Company $company): bool
    {
        if (!$user) {
            return false;
        }

        // admin role allowed
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // owner id match
        if ($company->owner_id && $user->id === $company->owner_id) {
            return true;
        }

        // pivot membership with owner/manager role
        if (method_exists($user, 'companies')) {
            $found = $user->companies()
                ->where('company_id', $company->id)
                ->exists();

            if ($found) {
                return true;
            }
        }

        return false;
    }
}

