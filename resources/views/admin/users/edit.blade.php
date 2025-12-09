@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Edit User #{{ $user->id }}</h3>
        <div style="color:#9da3b4;">
          {{ $user->name }} &lt;{{ $user->email }}&gt;
        </div>
      </div>

      {{-- Flash --}}
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0 small">
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Form utama --}}
      <div class="card mb-4" style="background:#101320;border-color:#25293a;color:#cbd1e6;">
        <div class="card-body">
          <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
              <label class="form-label small">Nama</label>
              <input type="text" name="name" class="form-control form-control-sm"
                     value="{{ old('name', $user->name) }}"
                     style="background:#070a13;color:#e6eef8;border-color:#25293a;">
            </div>

            <div class="mb-3">
              <label class="form-label small">Email</label>
              <input type="email" name="email" class="form-control form-control-sm"
                     value="{{ old('email', $user->email) }}"
                     style="background:#070a13;color:#e6eef8;border-color:#25293a;">
            </div>

            <div class="mb-3">
              <label class="form-label small d-flex justify-content-between">
                <span>Password baru (opsional)</span>
                <span class="text-muted">Biarkan kosong jika tidak ingin mengubah.</span>
              </label>
              <input type="password" name="password" class="form-control form-control-sm mb-2"
                     style="background:#070a13;color:#e6eef8;border-color:#25293a;">
              <input type="password" name="password_confirmation" class="form-control form-control-sm"
                     placeholder="Ulangi password baru"
                     style="background:#070a13;color:#e6eef8;border-color:#25293a;">
            </div>

            <div class="mb-3">
              <label class="form-label small">Role</label>
              <select name="role" class="form-select form-select-sm"
                      style="background:#070a13;color:#e6eef8;border-color:#25293a;">
                @foreach($availableRoles as $r)
                  <option value="{{ $r }}" @selected(old('role', $currentRole) === $r)>
                    {{ ucfirst(str_replace('_',' ',$r)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small">Perusahaan (company_id)</label>
              <select name="company_id" class="form-select form-select-sm"
                      style="background:#070a13;color:#e6eef8;border-color:#25293a;">
                <option value="">- Tidak ada -</option>
                @foreach($companies as $company)
                  <option value="{{ $company->id }}" @selected(old('company_id', $user->company_id) == $company->id)>
                    [{{ $company->id }}] {{ $company->name }}
                  </option>
                @endforeach
              </select>
              <div class="form-text text-muted small">
                Digunakan untuk relasi utama employer &amp; perusahaan.
              </div>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary">
                Simpan Perubahan
              </button>
              <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary ms-2">
                Kembali
              </a>
            </div>
          </form>
        </div>
      </div>

      {{-- Info profil & CV (read-only) --}}
      <div class="row">
        <div class="col-md-6 mb-3">
          <div class="card h-100" style="background:#101320;border-color:#25293a;color:#cbd1e6;">
            <div class="card-header small" style="background:#181b25;color:#e8eaf1;">
              Profil Pencari Kerja
            </div>
            <div class="card-body small">
              @if($user->profile)
                <div class="mb-2">
                  <strong>Headline:</strong><br>
                  {{ $user->profile->headline ?? '-' }}
                </div>
                <div class="mb-2">
                  <strong>Lokasi:</strong><br>
                  {{ $user->profile->location ?? '-' }}
                </div>
                <div class="mb-2">
                  <strong>Ringkasan:</strong><br>
                  {{ $user->profile->summary ?? '-' }}
                </div>
                @if(is_array($user->profile->skills))
                  <div class="mb-2">
                    <strong>Skill:</strong><br>
                    @foreach($user->profile->skills as $skill)
                      <span class="badge bg-secondary me-1 mb-1">{{ $skill }}</span>
                    @endforeach
                  </div>
                @endif
              @else
                <div class="text-muted">Belum ada profil.</div>
              @endif
            </div>
          </div>
        </div>

        <div class="col-md-6 mb-3">
          <div class="card h-100" style="background:#101320;border-color:#25293a;color:#cbd1e6;">
            <div class="card-header small" style="background:#181b25;color:#e8eaf1;">
              CV / Resume
            </div>
            <div class="card-body small">
              @if($user->resumes && $user->resumes->count() > 0)
                <ul class="list-unstyled mb-0">
                  @foreach($user->resumes as $resume)
                    <li class="mb-1">
                      {{-- Sesuaikan field resume sesuai schema aslimu --}}
                      @php
                        $name = $resume->original_name ?? $resume->filename ?? ('Resume #'.$resume->id);
                        $path = $resume->file_path ?? $resume->path ?? null;
                      @endphp

                      @if($path)
                        <a href="{{ asset('storage/'.$path) }}" target="_blank" class="link-light text-decoration-underline">
                          {{ $name }}
                        </a>
                      @else
                        {{ $name }}
                      @endif
                    </li>
                  @endforeach
                </ul>
              @else
                <div class="text-muted">Belum ada CV terunggah.</div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Info perusahaan terkait --}}
      <div class="card mb-3" style="background:#101320;border-color:#25293a;color:#cbd1e6;">
        <div class="card-header small" style="background:#181b25;color:#e8eaf1;">
          Perusahaan Terkait User
        </div>
        <div class="card-body small">
          @php
            $allCompanies = collect();
            if ($user->company) {
              $allCompanies->push($user->company);
            }
            if ($user->companies && $user->companies->count() > 0) {
              $allCompanies = $allCompanies->merge($user->companies);
            }
            $allCompanies = $allCompanies->unique('id');
          @endphp

          @if($allCompanies->count() > 0)
            <ul class="mb-0">
              @foreach($allCompanies as $c)
                <li>
                  [#{{ $c->id }}] {{ $c->name }}
                  @if($c->location)
                    <span class="text-muted">({{ $c->location }})</span>
                  @endif
                </li>
              @endforeach
            </ul>
          @else
            <div class="text-muted">Tidak ada perusahaan yang terhubung.</div>
          @endif
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

