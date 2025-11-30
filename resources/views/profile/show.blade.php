@extends('layouts.app')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:100vh;">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Profil Akun</h3>
        <div style="color:#9da3b4;">
          Informasi dasar akun Teleworks.id.
        </div>
      </div>

      <div class="card border-0 shadow-sm" style="background:#1c1f2a;color:#d1d6e3;">
        <div class="card-body">

          {{-- HEADER USER --}}
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width:72px;height:72px;background:#2a2f45;
                        font-weight:700;font-size:1.6rem;color:#cbd1ff;">
              {{ strtoupper(substr($user->name,0,1)) }}
            </div>
            <div>
              <div style="font-weight:600;color:#ffffff;">
                {{ $user->name }}
              </div>
              <div class="small" style="color:#9da3b4;">
                {{ $user->email }}
              </div>
            </div>
          </div>

          <hr style="border-color:#2c3148;">

          <div class="mb-3">
            <div class="small mb-1" style="color:#9da3b4;">Nama Lengkap</div>
            <div style="color:#f1f3ff;">{{ $user->name }}</div>
          </div>

          <div class="mb-3">
            <div class="small mb-1" style="color:#9da3b4;">Email</div>
            <div style="color:#f1f3ff;">{{ $user->email }}</div>
          </div>

          @role('job_seeker')
            <hr style="border-color:#2c3148;">
            <div class="mb-3">
              <div class="small mb-1" style="color:#9da3b4;">Profil Pencari Kerja</div>
              <div class="small" style="color:#b2b7ce;">
                Kelola profil detail di dashboard pencari kerja.
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
              <a href="{{ route('seeker.dashboard') }}" class="btn btn-primary btn-sm">
                Buka Dashboard Pencari Kerja
              </a>
              <a href="{{ route('seeker.profile.edit') }}"
                 class="btn btn-outline-light btn-sm"
                 style="border-color:#3a3f58;color:#dce1f1;">
                Edit Profil Pencari Kerja
              </a>
            </div>
          @endrole

          <hr style="border-color:#2c3148;">

          <div class="mt-3">
            <a href="{{ route('profile.edit') }}" class="btn btn-outline-light btn-sm"
               style="border-color:#3a3f58;color:#dce1f1;">
              Edit Pengaturan Akun
            </a>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection

