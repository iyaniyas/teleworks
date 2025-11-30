@extends('layouts.app')

@section('title', 'Edit Perusahaan')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:100vh;">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Edit Profil Perusahaan</h3>
        <div style="color:#9da3b4;">Perbarui profil perusahaan agar terlihat profesional di Teleworks.</div>
      </div>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="card border-0 shadow-sm" style="background:#1c1f2a; color:#d1d6e3;">
        <div class="card-body">

          <form method="POST" action="{{ route('companies.update', $company->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            {{-- Name --}}
            <div class="mb-3">
              <label class="form-label" style="color:#cbd1e6;">Nama Perusahaan</label>
              <input type="text" name="name" value="{{ old('name', $company->name) }}"
                     class="form-control"
                     style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
            </div>

            {{-- Domain --}}
            <div class="mb-3">
              <label class="form-label" style="color:#cbd1e6;">Website / Domain</label>
              <input type="text" name="domain" value="{{ old('domain', $company->domain) }}"
                     class="form-control"
                     placeholder="contoh: perusahaan.com"
                     style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
            </div>

            {{-- Slug --}}
            <div class="mb-3">
              <label class="form-label" style="color:#cbd1e6;">Slug (opsional)</label>
              <input type="text" name="slug" value="{{ old('slug', $company->slug) }}"
                     class="form-control"
                     placeholder="contoh: nama-perusahaan"
                     style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
            </div>

            {{-- Description --}}
            <div class="mb-3">
              <label class="form-label" style="color:#cbd1e6;">Deskripsi</label>
              <textarea name="description" rows="5"
                        class="form-control"
                        style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">{{ old('description', $company->description) }}</textarea>
            </div>

            {{-- Logo --}}
            <div class="mb-3">
              <label class="form-label" style="color:#cbd1e6;">Logo Perusahaan</label>

              @if($company->logo_path)
                <div class="mb-2">
                  <img src="{{ asset('storage/'.$company->logo_path) }}"
                       style="height:60px;border-radius:6px;">
                </div>
              @endif

              <input type="file" name="logo" class="form-control"
                     style="background:#181b25;border:1px solid #33374a;color:#e4e7f5;">
            </div>

            <div class="d-grid mt-4">
              <button class="btn btn-primary" style="font-weight:500;">Simpan Perubahan</button>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection

