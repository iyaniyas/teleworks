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
        $companyFromController = $company ?? null;

        if (!$companyFromController && $user) {
            $tmpCompany = null;

            if (property_exists($user, 'company_id') && $user->company_id) {
                $tmpCompany = \App\Models\Company::find($user->company_id);
            }
            if (!$tmpCompany && method_exists($user, 'company') && $user->company) {
                $tmpCompany = $user->company;
            }
            if (!$tmpCompany && method_exists($user, 'companies')) {
                $tmpCompany = $user->companies()->first();
            }

            $companyFromController = $tmpCompany;
        }

        $company = $companyFromController;
        $activePkg = $activePkg ?? ($company && method_exists($company,'activePackage') ? $company->activePackage() : null);
        $companiesList = collect($companies ?? []);
      @endphp

      {{-- KARTU PAKET --}}
      <div class="mb-3">
        @if($activePkg)
          <div class="card p-3 mb-3" style="background:#0b3d91;color:#fff;">
            <div class="d-flex justify-content-between">
              <div>
                <div style="font-weight:600;">
                  Paket Aktif:
                  {{ $activePkg->package_id ? (\App\Models\JobPackage::find($activePkg->package_id)->name ?? 'Paket') : 'Paket' }}
                </div>
                <div style="font-size:.9rem;">
                  Berlaku sampai:
                  {{ \Carbon\Carbon::parse($activePkg->expires_at)->format('d M Y') }}
                  (sisa {{ \Carbon\Carbon::now()->diffInDays($activePkg->expires_at) }} hari)
                </div>
              </div>
              <div class="text-end">
                @if(Route::has('employer.company.edit'))
                  <a href="{{ route('employer.company.edit') }}" class="btn btn-outline-light">Edit Profil</a>
                @endif
                <a href="{{ route('purchase.create') }}" class="btn btn-outline-light ms-2">
                  Tambah Loker
                </a>
              </div>
            </div>
          </div>
        @else
          <div class="card p-3 mb-3" style="background:#1c1f2a;color:#cbd1e6;">
            <div class="d-flex justify-content-between">
              <div>
                <div style="font-weight:600;">Belum ada paket aktif</div>
                <div style="font-size:.9rem;">Beli paket untuk publish lowongan.</div>
              </div>
              <div class="text-end">
                <a href="{{ route('pricing') }}" class="btn btn-primary">Beli Paket</a>
              </div>
            </div>
          </div>
        @endif
      </div>

      {{-- LIST PERUSAHAAN --}}
      <div class="mb-4">
        <div class="card" style="background:#1c1f2a;color:#cbd1e6;">
          <div class="card-header" style="background:#181b25;color:#e8eaf1;">
            Perusahaan Saya
          </div>

          @if($companiesList->count())
            <ul class="list-group list-group-flush">
              @foreach($companiesList as $c)
                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center" style="border-color:#25293a;">
                  <div>
                    <div class="fw-semibold text-light">{{ $c->name ?? 'Tanpa Nama' }}</div>
                    <div class="small text-muted">{{ $c->location ?? 'Lokasi tidak diisi' }}</div>
                  </div>
                  <div>
                    @if(Route::has('employer.company.edit') && ($company && $company->id === $c->id))
                      <a href="{{ route('employer.company.edit') }}" class="btn btn-sm btn-outline-light">
                        Edit Profil Aktif
                      </a>
                    @endif
                  </div>
                </li>
              @endforeach
            </ul>
          @else
            <div class="card-body text-muted">
              Belum ada perusahaan terhubung.
            </div>
          @endif
        </div>
      </div>

      <div class="row">
        {{-- LEFT --}}
        <div class="col-md-8">
          <div class="card h-100" style="background:#1c1f2a;color:#cbd1e6;">
            <div class="card-body">

              {{-- KPI --}}
              <div class="row mb-3">
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;">
                    <div class="small">Semua Loker</div>
                    <div class="fw-semibold">{{ $totalJobs ?? 0 }}</div>
                  </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;">
                    <div class="small">Loker Tampil</div>
                    <div class="fw-semibold">{{ $publishedJobs ?? 0 }}</div>
                  </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;">
                    <div class="small">Semua Pelamar</div>
                    <div class="fw-semibold">{{ $totalApplications ?? 0 }}</div>
                  </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                  <div class="p-3 rounded" style="background:#151726;">
                    <div class="small">Pelamar Baru (24h)</div>
                    <div class="fw-semibold">{{ $newApplicants ?? 0 }}</div>
                  </div>
                </div>
              </div>

              {{-- RECENT JOBS --}}
              @if(isset($recentJobs) && $recentJobs->count())
                <h5 class="mb-2 text-light">Lowongan Terbaru</h5>
                <ul class="list-group list-group-flush">
                  @foreach($recentJobs as $rj)
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center" style="border-color:#25293a;">
                      <a href="{{ route('jobs.show', $rj->id) }}" class="text-light text-decoration-none">
                        {{ $rj->title }}
                      </a>

                      <a href="/employer/jobs/{{ $rj->id }}/applicants"
                         class="btn btn-sm btn-outline-light">
                        Lihat Pelamar
                      </a>
                    </li>
                  @endforeach
                </ul>
              @else
                <div class="text-muted small">
                  Belum ada lowongan.
                </div>
              @endif

            </div>
          </div>
        </div>

        {{-- RIGHT --}}
        <div class="col-md-4">
          <div class="card h-100" style="background:#1c1f2a;color:#cbd1e6;">
            <div class="card-body">
              <div class="list-group">
                <a href="{{ route('employer.jobs.index') }}" class="list-group-item bg-transparent text-light border-secondary">
                  Kelola Lowongan
                </a>
                <a href="{{ route('employer.applications') }}" class="list-group-item bg-transparent text-light border-secondary">
                  Semua Pelamar
                </a>
                @if(Route::has('employer.company.edit'))
                  <a href="{{ route('employer.company.edit') }}" class="list-group-item bg-transparent text-light border-secondary">
                    Profil Perusahaan
                  </a>
                @endif
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>
@endsection

