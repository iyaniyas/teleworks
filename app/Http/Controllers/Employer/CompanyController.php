<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function edit(Request $request)
    {
        $company = $request->user()->company ?? Company::find($request->user()->company_id);

        if (!$company) {
            return redirect()->route('companies.create')
                ->with('info', 'Silakan lengkapi profil perusahaan terlebih dahulu.');
        }

        // gunakan view yang sesuai dengan lokasi file: resources/views/companies/edit.blade.php
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $company = $user->company ?? Company::find($user->company_id);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'website'     => 'nullable|url',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'logo'        => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($company && $company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $path = $request->file('logo')->store('company-logos', 'public');
            $data['logo'] = $path;
        }

        if (!$company) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);
            $company = Company::create($data);

            // set relation to user if schema supports
            $user->company_id = $company->id;
            $user->save();
        } else {
            $company->update($data);
        }

        return back()->with('success', 'Profil perusahaan diperbarui.');
    }
}

