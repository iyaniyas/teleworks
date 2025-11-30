@extends('layouts.app')

@section('content')
<div class="container py-5" style="background:#14161c; min-height:100vh;">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="mb-4">
        <h3 style="font-weight:600;color:#e8eaf1;">Edit Profil Pencari Kerja</h3>
        <div style="color:#9da3b4;">
          Lengkapi profil agar lowongan yang tampil semakin relevan.
        </div>
      </div>

      <div class="card border-0 shadow-sm" style="background:#1c1f2a;color:#d1d6e3;">
        <div class="card-body">

          @php
              $profile = auth()->user()->profile ?? null;
              $skillsArray = $profile ? (array) ($profile->skills ?? []) : [];
          @endphp

          <form method="POST" action="{{ route('seeker.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            {{-- HEADLINE --}}
            <div class="mb-3">
              <label class="form-label" style="color:#e2e5f4;">Headline</label>
              <input name="headline"
                     class="form-control"
                     style="background:#181b25;border-color:#31364e;color:#f2f4ff;"
                     value="{{ old('headline', $profile->headline ?? '') }}">
              <div class="form-text" style="color:#7f859d;">
                Contoh: “SEO Specialist & Web Loker Builder”
              </div>
            </div>

            {{-- LOKASI --}}
            <div class="mb-3">
              <label class="form-label" style="color:#e2e5f4;">Lokasi</label>
              <input name="location"
                     class="form-control"
                     style="background:#181b25;border-color:#31364e;color:#f2f4ff;"
                     value="{{ old('location', $profile->location ?? '') }}">
              <div class="form-text" style="color:#7f859d;">
                Contoh: “Solo, Jawa Tengah” atau “Remote dari Yogyakarta”.
              </div>
            </div>

            {{-- SUMMARY --}}
            <div class="mb-3">
              <label class="form-label" style="color:#e2e5f4;">Ringkasan</label>
              <textarea name="summary"
                        rows="5"
                        class="form-control"
                        style="background:#181b25;border-color:#31364e;color:#f2f4ff;">{{ old('summary', $profile->summary ?? '') }}</textarea>
              <div class="form-text" style="color:#7f859d;">
                Ceritakan singkat pengalaman, keahlian utama, dan jenis kerja jarak jauh yang kamu cari.
              </div>
            </div>

            {{-- SKILLS --}}
            <div class="mb-3">
              <label class="form-label" style="color:#e2e5f4;">Skills (pisahkan dengan koma)</label>
              <input name="skills"
                     class="form-control"
                     style="background:#181b25;border-color:#31364e;color:#f2f4ff;"
                     value="{{ old('skills', implode(',', $skillsArray)) }}">
              <div class="form-text" style="color:#7f859d;">
                Contoh: <em>SEO, Copywriting, Laravel, Customer Support</em>
              </div>
            </div>

            {{-- RESUME --}}
            <div class="mb-4">
              <label class="form-label" style="color:#e2e5f4;">Unggah CV</label>
              <input type="file"
                     name="resume"
                     accept=".pdf,.doc,.docx"
                     class="form-control"
                     style="background:#181b25;border-color:#31364e;color:#f2f4ff;">
              <div class="form-text" style="color:#7f859d;">
                Format: PDF, DOC, atau DOCX.
              </div>
            </div>

            {{-- ACTION --}}
            <div class="d-flex gap-2">
              <button class="btn btn-primary" style="font-weight:500;">Simpan Profil</button>
              <a href="{{ route('seeker.dashboard') }}"
                 class="btn btn-outline-light"
                 style="border-color:#3a3f58;color:#dce1f1;">
                Batal
              </a>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection

