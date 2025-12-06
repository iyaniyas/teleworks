<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function __construct()
    {
        // pastikan hanya employer/company atau admin yang bisa akses
        $this->middleware(['auth', 'role:company|admin']);
    }

    /**
     * Resolver perusahaan yang konsisten:
     * - pivot company_user
     * - owner_id
     * - users.company_id
     */
    protected function resolveCompany($user): ?Company
    {
        if (! $user) {
            return null;
        }

        // 1) Pivot company_user
        if (method_exists($user, 'companies')) {
            $pivotCompany = $user->companies()->first();
            if ($pivotCompany) {
                return $pivotCompany;
            }
        }

        // 2) owner_id di tabel companies
        $owned = Company::where('owner_id', $user->id)->first();
        if ($owned) {
            return $owned;
        }

        // 3) kolom company_id di users (jika ada)
        if (! empty($user->company_id)) {
            $byCompanyId = Company::find($user->company_id);
            if ($byCompanyId) {
                return $byCompanyId;
            }
        }

        return null;
    }

    /**
     * Form edit profil perusahaan.
     * Jika belum ada, redirect ke companies.create
     */
    public function edit(Request $request)
    {
        $user = $request->user();
        $company = $this->resolveCompany($user);

        if (! $company) {
            return redirect()
                ->route('companies.create')
                ->with('info', 'Silakan lengkapi profil perusahaan terlebih dahulu.');
        }

        // view: resources/views/companies/edit.blade.php
        return view('companies.edit', compact('company'));
    }

    /**
     * Simpan / update profil perusahaan untuk employer.
     * - Jika belum ada company => buat baru
     * - Jika sudah ada => update
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $company = $this->resolveCompany($user);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'website'     => 'nullable|url',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'logo'        => 'nullable|image|max:2048',
        ]);

        // handle upload logo → simpan ke logo_path (sesuai model Company)
        if ($request->hasFile('logo')) {
            if ($company && $company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $path = $request->file('logo')->store('company-logos', 'public');
            $data['logo_path'] = $path;
        }

        if (! $company) {
            // buat company baru
            $data['owner_id'] = $user->id;
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

            $company = Company::create($data);

            // set relasi ke user jika skema mendukung
            // asumsi ada kolom company_id di users
            try {
                $user->company_id = $company->id;
                $user->save();
            } catch (\Throwable $e) {
                // kalau kolom tidak ada, diam saja, jangan meledak
            }

            // daftarkan ke pivot company_user (jika relasi tersedia)
            if (method_exists($user, 'companies')) {
                $user->companies()->syncWithoutDetaching([
                    $company->id => ['role' => 'owner'],
                ]);
            }
        } else {
            // update company yang sudah ada
            $company->update($data);
        }

        return redirect()
            ->route('employer.dashboard')
            ->with('success', 'Profil perusahaan diperbarui.');
    }
}

