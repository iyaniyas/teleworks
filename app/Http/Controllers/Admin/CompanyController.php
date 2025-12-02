<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $companies = Company::query()
            ->when($q, fn($qbuilder) => $qbuilder->where('name', 'like', '%'.$q.'%'))
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.companies.index', compact('companies','q'));
    }

    public function show(Company $company)
    {
        return view('admin.companies.show', compact('company'));
    }

    public function verify(Request $request, Company $company)
    {
        $data = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $company->is_verified = true;
        $company->verification_note = $data['note'];
        $company->save();

        return redirect()->back()->with('success', 'Company verified.');
    }

    public function unverify(Request $request, Company $company)
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        $company->is_verified = false;
        $company->verification_note = $data['note'];
        $company->save();

        return redirect()->back()->with('success', 'Company unverified.');
    }

    public function suspend(Request $request, Company $company)
    {
        $data = $request->validate([
            'note' => 'required|string|max:2000',
        ]);
        $company->is_suspended = true;
        $company->verification_note = $data['note'];
        $company->save();

        return redirect()->back()->with('success', 'Company suspended.');
    }

    public function unsuspend(Company $company)
    {
        $company->is_suspended = false;
        // keep existing verification_note
        $company->save();

        return redirect()->back()->with('success', 'Company unsuspended.');
    }
}

