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

      <div class="row g-4">

        {{-- COMPANY PROFILE --}}
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100" style="background:#1c1f2a; color:#d1d6e3;">
            <div class="card-body">

              @php
                $company = $company ?? null;
              @endphp

              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:64px;height:64px;background:#2a2f45;
                            font-weight:700;font-size:1.3rem;color:#cbd1ff;">
                  @if($company && $company->logo_path)
                    <img src="{{ asset('storage/'.$company->logo_path) }}" alt="logo" style="width:64px;height:64px;object-fit:cover;border-radius:50%;">
                  @else
                    {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                  @endif
                </div>

                <div>
                  <div style="font-weight:600;color:#ffffff;">
                    {{ $company->name ?? 'Belum ada nama perusahaan' }}
                  </div>
                  <div class="small" style="color:#a4aac3;">
                    {{ $company->domain ?? '-' }}
                  </div>
                  <div class="small" style="color:#8b90a8;">
                    {{ $company->is_verified ? 'Terverifikasi' : 'Belum terverifikasi' }}
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <div class="small mb-1" style="color:#9da3b4;">Deskripsi</div>
                <div class="rounded p-3" style="background:#181b25; color:#cfd3e7;">
                  {!! nl2br(e($company->description ?? 'Belum ada deskripsi perusahaan.')) !!}
                </div>
              </div>

              <div class="d-grid gap-2 mt-3">
                {{-- View public profile --}}
                <a href="{{ route('companies.show', $company->slug ?? '') }}" class="btn btn-primary" style="font-weight:500;">
                  Lihat Profil Publik
                </a>

		<a href="{{ route('companies.edit', $company->id) }}"
		   class="btn btn-outline-light"
		   style="border-color:#3a3f58;color:#dce1f1;">
			  Edit Perusahaan
		</a>

                <a href="{{ route('employer.jobs.create') }}" class="btn btn-outline-light" style="border-color:#3a3f58;color:#dce1f1;">
                  Buat Lowongan
                </a>

                <a href="{{ route('employer.applications') }}" class="btn btn-outline-light" style="border-color:#3a3f58;color:#dce1f1;">
                  Lihat Pelamar
                </a>
              </div>

            </div>
          </div>
        </div>

        {{-- JOBS & KPI --}}
        <div class="col-md-8">
          <div class="card border-0 shadow-sm h-100" style="background:#1c1f2a;color:#cbd1e6;">
            <div class="card-body">

              {{-- KPI blok atas --}}
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

              {{-- Lowongan terbaru --}}
              <div class="mb-3">
                <div style="font-weight:600;color:#ffffff;">Lowongan Terbaru</div>
                <div class="small" style="color:#9da3b4;">Daftar lowongan yang terakhir dibuat oleh perusahaan ini.</div>
              </div>

              @if(isset($recentJobs) && $recentJobs->count())
                <div class="list-group list-group-flush">
                  @foreach($recentJobs as $job)
                    <div class="list-group-item" style="background:#181b25;border:1px solid #252943;border-radius:10px;margin-bottom:8px;color:#e4e8ff;">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div style="font-weight:600;">{{ $job->title }}</div>
                          <div class="small" style="color:#b2b7ce;">{{ $job->company ?? ($company->name ?? 'Perusahaan') }} · {{ $job->location ?? 'Remote / Indonesia' }}</div>
                          <div class="small mt-1" style="color:#8f95ab;">{{ $job->employment_type ?? '' }}</div>
                        </div>
                        <div class="text-end">
                          <div><span class="badge bg-{{ $job->status === 'published' ? 'success' : 'secondary' }}">{{ $job->status }}</span></div>
                          <div class="mt-2">
                            @if($job->is_imported)
                              <span class="badge bg-warning text-dark">Imported</span>
                              <a href="{{ route('jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                            @else
                              <a href="{{ route('employer.jobs.edit', $job->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endif
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="text-center py-4">
                  <div style="font-weight:500;color:#e2e5f4;">Belum ada lowongan</div>
                  <div class="small mb-3" style="color:#9da3b4;">Buat lowongan pertama untuk mulai menerima pelamar.</div>
                  <a href="{{ route('employer.jobs.create') }}" class="btn btn-outline-primary btn-sm">Buat Lowongan</a>
                </div>
              @endif

            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>
@endsection

