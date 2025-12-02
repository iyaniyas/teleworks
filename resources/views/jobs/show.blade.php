{{-- resources/views/jobs/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Loker '.($job->title ?? 'Lowongan Kerja').' — Teleworks')
@php
  // Gunakan lokasi dari kolom job_location jika ada, kalau kosong tampilkan 'Indonesia'
  $lokasi = !empty($job->job_location) ? $job->job_location : 'Indonesia';
  $metaDescriptionText = 'Loker '.e($job->title).' di '.e($lokasi).' remote, WFH, dan freelance terbaru di Teleworks.';
@endphp

@section('meta_description', $metaDescriptionText)



@section('content')
<div class="container my-4">
<style>
/* ----- Basic page & theme ----- */
body { background-color: #0f1115; color: #e6eef8; }
a { color: #9ecbff; text-decoration: none; }
a:hover { color: #cfe6ff; text-decoration: underline; }

/* ----- Card / sections ----- */
.tw-card { background: #181a20; border: 1px solid #2a2d35; border-radius: .75rem; padding: 1.5rem; box-shadow: 0 0 15px rgba(0,0,0,0.4); }
.tw-section { background: #1f2229; border: 1px solid #2a2d35; border-radius: .5rem; padding: 1rem; }
.tw-muted { color: #9aa0ac; }
.tw-badge { background: linear-gradient(90deg,#10b981,#059669); color: white; font-weight: 600; border-radius: .3rem; padding: .25rem .5rem; font-size: .8rem; }
.tw-salary { color: #cde7ff; font-weight: 600; }
.tw-alert { background: #22252c; border: 1px solid #2e323b; color: #b0b6c3; border-radius: .5rem; padding: .75rem 1rem; }

/* ----- text / lists ----- */
.job-desc { line-height: 1.6; color: #dde6f5; }
.small-muted { color: #bbb !important; font-size: 0.875rem; }
.muted { color: #ccc; }
.highlight { color: #fff; font-weight: 500; }
.related-list a { display:block; padding:.4rem 0; border-bottom:1px dashed rgba(255,255,255,0.03); }
.city-links a { display:inline-block; margin-right:.5rem; margin-bottom:.25rem; padding:.35rem .6rem; background:#0e1720; border-radius:.4rem; text-decoration:none; color:#9ecbff; font-size:.9rem; }

/* Make sure the main container has bottom padding so content isn't hidden by modal/cta */
body .container { padding-bottom: 6rem; } /* room for centered CTA */
</style>

<div class="mb-3">
  <a href="{{ url('/') }}">Beranda</a>
  <span class="mx-2 tw-muted">›</span>
  <a href="{{ url('/cari') }}">Cari Lowongan</a>
</div>

<div class="tw-card">
  <h1 class="h4 fw-bold mb-2" style="color:#f3f7ff;">{{ $job->title ?? 'Tanpa Judul' }}</h1>

  <div class="tw-section mb-4">
    <h2 class="h5 fw-bold mb-3" style="color:#cfe6ff;">Rincian Lowongan</h2>
    <ul class="list-unstyled small" style="line-height:1.8;">
      @if($job->date_posted)
        <li><strong>Dipublikasikan:</strong> {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}</li>
      @endif

      @if($job->valid_through)
        <li><strong>Berlaku Hingga:</strong> {{ \Carbon\Carbon::parse($job->valid_through)->translatedFormat('d M Y') }}</li>
      @endif

      @if($job->hiring_organization)
        <li><strong>Perusahaan:</strong> {{ $job->hiring_organization }}</li>
      @endif

      @if($job->job_location)
        <li><strong>Lokasi Kerja:</strong> {{ $job->job_location }}</li>
      @endif

      @if($job->job_location_type)
        <li><strong>Tipe Lokasi:</strong> {{ ucfirst($job->job_location_type) }}</li>
      @endif

      @if($job->applicant_location_requirements)
        @php
          $raw = is_array($job->applicant_location_requirements) ? $job->applicant_location_requirements : (json_decode($job->applicant_location_requirements, true) ?: [$job->applicant_location_requirements]);
          $map = ['ID' => 'Indonesia','IDN' => 'Indonesia','id'=>'Indonesia','Id'=>'Indonesia'];
          $appReqDisplay = [];
          foreach($raw as $r) {
              $rStr = trim((string)$r);
              if ($rStr === '') continue;
              $u = strtoupper($rStr);
              if (isset($map[$u])) {
                  $appReqDisplay[] = $map[$u];
              } elseif (isset($map[$rStr])) {
                  $appReqDisplay[] = $map[$rStr];
              } else {
                  $appReqDisplay[] = $rStr;
              }
          }
          if (empty($appReqDisplay)) { $appReqDisplay = ['Indonesia']; }
        @endphp
        <li><strong>Syarat Lokasi Pelamar:</strong> {{ implode(', ', $appReqDisplay) }}</li>
      @endif

      @if($job->employment_type)
        <li><strong>Jenis Pekerjaan:</strong> {{ ucfirst($job->employment_type) }}</li>
      @endif

      {{-- Base Salary --}}
      @if($job->base_salary_string || $job->base_salary_min || $job->base_salary_max)
        <li><strong>Gaji:</strong>
          @if(!empty($job->base_salary_string))
            {{ $job->base_salary_string }}
          @elseif($job->base_salary_min && $job->base_salary_max)
            {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_min) }} – {{ number_format($job->base_salary_max) }} / {{ $job->base_salary_unit ?? 'MONTH' }}
          @elseif($job->base_salary_min)
            mulai {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_min) }} / {{ $job->base_salary_unit ?? 'MONTH' }}
          @elseif($job->base_salary_max)
            s.d. {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_max) }} / {{ $job->base_salary_unit ?? 'MONTH' }}
          @endif
        </li>
      @endif

      @if($job->identifier_value)
        <li><strong>ID Lowongan:</strong> {{ $job->identifier_name ?? 'job_id' }}: {{ $job->identifier_value }}</li>
      @endif

      <li><strong>Kirim Langsung:</strong> {{ !empty($job->direct_apply) ? 'Ya' : 'Tidak' }}</li>
    </ul>
  </div>

  <div class="tw-section mb-4 job-desc">
    {{-- Tambahkan judul posisi ke dalam deskripsi sesuai permintaan --}}
    <p><strong>Posisi:</strong> {{ $job->title }}</p>
    {!! $job->description_html ?? ($job->description ? nl2br(e($job->description)) : '<em class="tw-muted">Deskripsi belum tersedia.</em>') !!}
  </div>

{{-- REPORT JOB BUTTON (letakkan dekat CTA atau di bawah title) --}}
<div class="mt-3">
  @auth
    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reportJobModal">
      Laporkan Lowongan
    </button>
  @else
    <a href="{{ route('login') }}" class="btn btn-outline-danger">Login untuk Laporkan</a>
  @endauth
</div>

{{-- REPORT JOB MODAL --}}
<div class="modal fade" id="reportJobModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-secondary">
      <div class="modal-header border-0">
        <h5 class="modal-title">Laporkan Lowongan: {{ $job->title }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <form action="{{ route('reports.store') }}" method="POST">
        @csrf
        <input type="hidden" name="reportable_type" value="{{ addslashes(\App\Models\Job::class) }}">
        <input type="hidden" name="reportable_id" value="{{ $job->id }}">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Alasan pelaporan <small class="text-muted">(wajib)</small></label>
            <textarea name="reason" class="form-control bg-transparent text-light border-secondary" rows="4" required>{{ old('reason') }}</textarea>
          </div>

          <div class="mb-2 small-muted">
            Contoh alasan: lowongan palsu, penipuan, konten tidak pantas, email/phone contact mencurigakan, dsb.
          </div>

          @if ($errors->has('reason'))
            <div class="alert alert-danger">{{ $errors->first('reason') }}</div>
          @endif
        </div>

        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Kirim Laporan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Jika server-side validasi gagal, auto-open modal supaya user lihat error --}}
@if($errors->has('reason') && old('reportable_id') == $job->id)
  <script>
    document.addEventListener("DOMContentLoaded", function(){
      var el = document.getElementById('reportJobModal');
      if(el) new bootstrap.Modal(el).show();
    });
  </script>
@endif


  {{-- If apply_url existed previously, we intentionally removed external CTA; internal apply UI below --}}
  @if(empty($job->apply_url))
  <div class="tw-alert mb-4">Link lamaran eksternal tidak disertakan — gunakan tombol "Lamar Sekarang" di bawah jika tersedia.</div>
  @endif

  {{-- Related jobs (loker terbaru) --}}
  <div class="tw-section mb-4">
    <h3 class="h6 fw-bold mb-2" style="color:#cfe6ff;">Loker terbaru</h3>
    @if(!empty($relatedJobs) && $relatedJobs->count() > 0)
      <div class="related-list small">
        @foreach($relatedJobs as $r)
          <a href="{{ url('/loker/'.$r->id) }}" title="{{ $r->title ?? 'Lowongan' }}" class="muted">
            • {{ $r->title ?? 'Tanpa Judul' }}
            @if($r->date_posted)
              <span class="small-muted"> — {{ \Carbon\Carbon::parse($r->date_posted)->translatedFormat('d M Y') }}</span>
            @endif
          </a>
        @endforeach
      </div>
    @else
      <div class="small-muted">Tidak ada loker terkait.</div>
    @endif
  </div>

  {{-- 5 Kota Teratas di Indonesia (huruf kecil) --}}
  <div class="tw-section mb-4">
    <h3 class="h6 fw-bold mb-2" style="color:#cfe6ff;">5 Kota Teratas — Jelajahi berdasarkan kota</h3>
    <div class="city-links">
      <a href="https://www.teleworks.id/cari/lokasi/jakarta">jakarta</a>
      <a href="https://www.teleworks.id/cari/lokasi/bandung">bandung</a>
      <a href="https://www.teleworks.id/cari/lokasi/surabaya">surabaya</a>
      <a href="https://www.teleworks.id/cari/lokasi/medan">medan</a>
      <a href="https://www.teleworks.id/cari/lokasi/semarang">semarang</a>
    </div>
  </div>

</div>
</div>

{{-- ===============================
   INTERNAL APPLY — Bootstrap 5 (Dark, centered CTA + modal)
   =============================== --}}
@php
    $directApply = !empty($job->direct_apply);
    $existingApp = null;
    if (auth()->check()) {
        try {
            $existingApp = auth()->user()->applications()->where('job_id', $job->id)->first();
        } catch (\Throwable $e) {
            $existingApp = \App\Models\JobApplication::where('job_id', $job->id)
                                ->where('user_id', auth()->id())->first();
        }
    }
@endphp

@if($directApply)
  {{-- Centered small CTA fixed at bottom (dark) --}}
  <div class="position-fixed w-100" style="left:0;right:0;bottom:18px;z-index:1500;pointer-events:none;">
    <div class="d-flex justify-content-center">
      @guest
        <a href="{{ route('login') }}" class="btn btn-primary btn-lg shadow" style="pointer-events:auto;">
          Login untuk Lamar
        </a>
      @else
        @if($existingApp)
          <button class="btn btn-secondary btn-lg shadow" disabled style="pointer-events:auto;">
            Sudah melamar — {{ ucfirst($existingApp->status ?? 'submitted') }}
          </button>
        @else
          <button class="btn btn-primary btn-lg shadow" data-bs-toggle="modal" data-bs-target="#applyModal" style="pointer-events:auto;">
            Lamar Sekarang
          </button>
        @endif
      @endguest
    </div>
  </div>

  {{-- Apply Modal (centered, dark) --}}
  <div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-light border-secondary">
        <div class="modal-header border-0">
          <h5 class="modal-title">Lamar: {{ $job->title }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <form action="{{ url('/loker/'.$job->id.'/apply') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
          @csrf
          <div class="modal-body">
            {{-- Validation errors --}}
            @if($errors->any() && session()->hasOldInput())
              <div class="alert alert-danger">
                <ul class="mb-0 small">
                  @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div class="mb-3">
              <label for="cover_letter" class="form-label small-muted">Surat Lamaran (opsional)</label>
              <textarea id="cover_letter" name="cover_letter" rows="4"
                        class="form-control bg-transparent text-light border-secondary">{{ old('cover_letter') }}</textarea>
            </div>

            <div class="mb-3">
              <label for="resume" class="form-label small-muted">Unggah CV (PDF / DOC / DOCX) — maks 5MB (opsional)</label>
              <input id="resume" name="resume" type="file" accept=".pdf,.doc,.docx"
                     class="form-control bg-transparent text-light border-secondary">
              <div class="form-text small-muted">Jika sudah menyimpan CV di profil, upload tidak wajib.</div>
            </div>
          </div>

          <div class="modal-footer border-0">
            <div class="w-100 d-flex gap-2">
              <button type="button" class="btn btn-outline-light w-50" data-bs-dismiss="modal">Batal</button>
              <button id="applySubmitBtn" type="submit" class="btn btn-success w-50">Kirim Lamaran</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Auto-open modal when server returned validation errors --}}
  @if($errors->any() && session()->hasOldInput())
    <script>
      document.addEventListener("DOMContentLoaded", function(){
        var modalEl = document.getElementById('applyModal');
        if (modalEl) {
          var m = new bootstrap.Modal(modalEl);
          m.show();
        }
      });
    </script>
  @endif

  {{-- Prevent double-submit visual feedback --}}
  <script>
    document.addEventListener("DOMContentLoaded", function(){
      var form = document.querySelector('#applyModal form');
      var btn = document.getElementById('applySubmitBtn');
      if(form && btn){
        form.addEventListener('submit', function(){
          btn.disabled = true;
          btn.textContent = 'Mengirim...';
        });
      }
    });
  </script>
@endif

@endsection

@push('schema')
@if(!empty($jobPostingJsonLd))
<script type="application/ld+json">
{!! $jobPostingJsonLd !!}
</script>
@endif
@endpush

