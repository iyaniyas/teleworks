@extends('layouts.app')

@section('title', 'Dashboard Perusahaan')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:100vh;">
  <div class="row justify-content-center">
    <div class="col-lg-10">

      {{-- TITLE --}}
      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Dashboard Perusahaan</h3>
        <div style="color:#9da3b4;">
          Kelola profil perusahaan, lowongan, dan pantau pelamar.
        </div>
      </div>

      @php
          $user = auth()->user();
          $company = null;
          if ($user) {
              if (property_exists($user, 'company_id') && $user->company_id) {
                  $company = \App\Models\Company::find($user->company_id);
              }
              if (!$company && method_exists($user, 'company') && $user->company) {
                  $company = $user->company;
              }
              if (!$company && method_exists($user, 'companies')) {
                  $company = $user->companies()->first();
              }
          }
          $activePkg = $company ? $company->activePackage() : null;
      @endphp

      <div class="mb-3">
        @if($activePkg)
          <div class="card p-3 mb-3" style="background:#0b3d91;color:#fff;">
            <div class="d-flex justify-content-between">
              <div>
                <div style="font-weight:600;">
                  Paket Aktif:
                  {{ $activePkg->package_id ? (\App\Models\JobPackage::find($activePkg->package_id)->name ?? 'Paket') : 'Paket' }}
                </div>
                <div style="font-size:.9rem;opacity:.95;">
                  Berlaku sampai: {{ \Carbon\Carbon::parse($activePkg->expires_at)->format('d M Y') }}
                  (sisa {{ \Carbon\Carbon::now()->diffInDays($activePkg->expires_at) }} hari)
                </div>
              </div>
              <div class="text-end">
                <a href="{{ route('employer.company.edit') }}" class="btn btn-outline-light">Edit Profil</a>
                <a href="{{ route('purchase.create') }}" class="btn btn-outline-light ms-2">Pasang loker baru.</a>
              </div>
            </div>
          </div>
        @else
          <div class="card p-3 mb-3" style="background:#1c1f2a;color:#cbd1e6;">
            <div class="d-flex justify-content-between">
              <div>
                <div style="font-weight:600;">Belum ada paket aktif</div>
                <div style="font-size:.9rem;opacity:.9;">Beli paket untuk dapat mem-publish lowongan.</div>
              </div>
              <div class="text-end">
                <a href="{{ route('pricing') }}" class="btn btn-primary">Beli Paket</a>
              </div>
            </div>
          </div>
        @endif
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="card border-0 shadow-sm h-100" style="background:#1c1f2a;color:#cbd1e6;">
            <div class="card-body">

              {{-- KPI block (sample) --}}
              <div class="row mb-3">
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;color:#e6eef8;">
                    <div class="small">Lowongan</div>
                    <div style="font-weight:600;font-size:1.25rem;">{{ $totalJobs ?? 0 }}</div>
                  </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;color:#e6eef8;">
                    <div class="small">Publikasi</div>
                    <div style="font-weight:600;font-size:1.25rem;">{{ $publishedJobs ?? 0 }}</div>
                  </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;color:#e6eef8;">
                    <div class="small">Pelamar</div>
                    <div style="font-weight:600;font-size:1.25rem;">{{ $totalApplications ?? 0 }}</div>
                  </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;color:#e6eef8;">
                    <div class="small">Baru (24h)</div>
                    <div style="font-weight:600;font-size:1.25rem;">{{ $newApplicants ?? 0 }}</div>
                  </div>
                </div>
              </div>

              {{-- recent jobs --}}
              @if(isset($recentJobs) && $recentJobs->count() > 0)
                <div class="mb-3">
                  <h5 class="mb-2" style="color:#e8eaf1;">Lowongan Terbaru</h5>
                  <ul class="list-group list-group-flush">
                    @foreach($recentJobs as $rj)
                      <li class="list-group-item bg-transparent" style="color:#d1d6e3;">
                        <a href="{{ route('jobs.show', $rj->id) }}" class="text-decoration-none text-light">{{ $rj->title }}</a>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif

            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100" style="background:#1c1f2a;color:#cbd1e6;">
            <div class="card-body">
              <div class="list-group">
                <a href="{{ route('employer.jobs.index') }}" class="list-group-item list-group-item-action bg-transparent text-light">Kelola Lowongan</a>
                <a href="{{ route('employer.applications') }}" class="list-group-item list-group-item-action bg-transparent text-light">Pelamar</a>
                <a href="{{ route('employer.company.edit') }}" class="list-group-item list-group-item-action bg-transparent text-light">Profil Perusahaan</a>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>
@endsection

