<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanyHasActivePackage
{
    /**
     * Handle an incoming request.
     * If company (of authenticated user) has no active package, redirect to purchase page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Attempt to resolve company in several common ways
        $company = null;
        if (property_exists($user, 'company_id') && $user->company_id) {
            $company = \App\Models\Company::find($user->company_id);
        }
        if (!$company && method_exists($user, 'company') && $user->company) {
            $company = $user->company;
        }
        if (!$company && method_exists($user, 'companies')) {
            $company = $user->companies()->first();
        }

        if (!$company) {
            return redirect()->route('purchase.create')->with('error', 'Perusahaan tidak ditemukan. Silakan lengkapi profil perusahaan Anda.');
        }

        if (!method_exists($company, 'hasActivePackage') || !$company->hasActivePackage()) {
            return redirect()->route('purchase.create')->with('error', 'Anda perlu paket aktif untuk mem-publish lowongan. Silakan pilih paket.');
        }

        return $next($request);
    }
}

