@extends('layouts.app')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:100vh;">
  <div class="row justify-content-center">

    <div class="col-lg-10">

      {{-- TITLE --}}
      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Dashboard Pencari Kerja</h3>
        <div style="color:#9da3b4;">
          Kelola profil dan pantau peluang kerja yang sesuai denganmu.
        </div>
      </div>

      <div class="row g-4">

        {{-- PROFIL --}}
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100"
               style="background:#1c1f2a; color:#d1d6e3;">

            <div class="card-body">

              @php
                  $profile = auth()->user()->profile ?? null;
                  $skills  = $profile ? (array) ($profile->skills ?? []) : [];
              @endphp

              {{-- AVATAR --}}
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:64px;height:64px;background:#2a2f45;
                            font-weight:700;font-size:1.3rem;color:#cbd1ff;">
                  {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                </div>

                <div>
                  <div style="font-weight:600;color:#ffffff;">
                    {{ auth()->user()->name }}
                  </div>
                  <div class="small" style="color:#a4aac3;">
                    {{ $profile->headline ?? 'Belum mengisi headline' }}
                  </div>
                  <div class="small" style="color:#8b90a8;">
                    📍 {{ $profile->location ?? 'Lokasi belum diisi' }}
                  </div>
                </div>
              </div>

              {{-- SUMMARY --}}
              <div class="mb-3">
                <div class="small mb-1" style="color:#9da3b4;">Ringkasan Profil</div>
                <div class="rounded p-3"
                     style="background:#181b25; color:#cfd3e7;">
                  {{ $profile->summary ?? 'Belum ada ringkasan profil.' }}
                </div>
              </div>

              {{-- SKILLS --}}
              <div class="mb-3">
                <div class="small mb-1" style="color:#9da3b4;">Skills</div>

                @if(count($skills))
                  @foreach($skills as $skill)
                    <span class="badge rounded-pill me-1 mb-1"
                          style="background:#2a2f45;color:#d5d9ff;font-weight:500;">
                      {{ $skill }}
                    </span>
                  @endforeach
                @else
                  <div class="small text-muted">
                    Belum ada skills. Lengkapi profil.
                  </div>
                @endif
              </div>

              {{-- ACTION --}}
              <div class="d-grid gap-2 mt-3">
                <a href="{{ route('seeker.profile.edit') }}"
                   class="btn btn-primary"
                   style="font-weight:500;">
                  Edit Profil
                </a>

                <a href="{{ route('seeker.applications.index') }}"
                   class="btn btn-outline-light"
                   style="border-color:#3a3f58;color:#dce1f1;">
                  Lamaran Saya
                </a>

                <a href="{{ route('seeker.saved.index') }}"
                   class="btn btn-outline-light"
                   style="border-color:#3a3f58;color:#dce1f1;">
                  Lowongan Tersimpan
                </a>
              </div>

            </div>
          </div>
        </div>

        {{-- REKOMENDASI --}}
        <div class="col-md-8">
          <div class="card border-0 shadow-sm h-100"
               style="background:#1c1f2a;color:#cbd1e6;">

            <div class="card-body">

              <div class="mb-3">
                <div style="font-weight:600;color:#ffffff;">Rekomendasi Lowongan</div>
                <div class="small" style="color:#9da3b4;">
                  Berdasarkan profil dan preferensimu.
                </div>
              </div>

              @if(isset($recommendedJobs) && $recommendedJobs->count())

                <div class="list-group list-group-flush">

                  @foreach($recommendedJobs as $job)
                    <a href="{{ url('/loker/'.$job->id) }}"
                       class="list-group-item list-group-item-action"
                       style="background:#181b25;
                              border:1px solid #252943;
                              border-radius:10px;
                              margin-bottom:8px;
                              color:#e4e8ff;">

                      <div class="d-flex flex-column">

                        <span style="font-weight:600;">
                          {{ $job->title }}
                        </span>

                        <span class="small" style="color:#b2b7ce;">
                          {{ $job->company_name ?? 'Perusahaan' }}
                          · {{ $job->location ?? 'Remote / Indonesia' }}
                        </span>

                        @if(!empty($job->employment_type))
                          <span class="small" style="color:#8f95ab;">
                            {{ $job->employment_type }}
                          </span>
                        @endif

                      </div>

                    </a>
                  @endforeach

                </div>

              @else
                <div class="text-center py-5">

                  <div class="mb-2" style="font-weight:500;color:#e2e5f4;">
                    Belum ada rekomendasi tersedia
                  </div>

                  <div class="small mb-3" style="color:#9da3b4;">
                    Lengkapi skill dan lokasi untuk mendapatkan rekomendasi.
                  </div>

                  <a href="{{ route('seeker.profile.edit') }}"
                     class="btn btn-outline-primary btn-sm">
                    Lengkapi Profil Sekarang
                  </a>

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

