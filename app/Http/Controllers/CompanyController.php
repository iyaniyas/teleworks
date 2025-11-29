<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|url|max:255',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();

        $company = Company::create([
            'owner_id' => $user->id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . uniqid(),
            'domain' => $request->domain,
            'description' => $request->description,
        ]);

        // attach owner as company_user pivot
        $company->users()->attach($user->id, ['role' => 'owner']);

        // assign company role to user (requires spatie installed)
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('company');
        }

        return redirect()->route('companies.show', $company->slug)->with('success', 'Company created and you have been assigned as owner.');
    }

    public function show($slug)
    {
        $company = Company::where('slug', $slug)->with('jobs')->firstOrFail();
        return view('companies.show', compact('company'));
    }
}

