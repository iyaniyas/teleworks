{{-- resources/views/jobs/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Loker '.($job->title ?? 'Lowongan Kerja').' — Teleworks')

@php
  $lokasi = !empty($job->job_location) ? $job->job_location : 'Indonesia';
  $metaDescriptionText = 'Loker '.e($job->title).' di '.e($lokasi).' remote, WFH, dan freelance terbaru di Teleworks.';
@endphp

@section('meta_description', $metaDescriptionText)

{{-- Inject JSON-LD into <head> via stack (layouts.app sudah punya @stack('head')) --}}
@push('head')
  @if(!empty($jobPostingJsonLd))
    <script type="application/ld+json">
{!! $jobPostingJsonLd !!}
    </script>
  @endif
@endpush

@section('content')
<div class="container my-4">
  {{-- CARD UTAMA (perubahan #3: border diperjelas) --}}
  <div class="card mb-4" style="background:#0b1722;color:#e6eef8;border:1px solid #1f3347;">
    <div class="card-body">

      {{-- Breadcrumb --}}
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-transparent px-0 mb-0 small">
          <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-decoration-none" style="color:#cfe6ff;">Beranda</a></li>
          <li class="breadcrumb-item"><a href="{{ url('/cari') }}" class="text-decoration-none" style="color:#cfe6ff;">Cari Lowongan</a></li>
          <li class="breadcrumb-item active" aria-current="page" style="color:#e6eef8;">{{ $job->title ?? 'Lowongan' }}</li>
        </ol>
      </nav>

      {{-- Judul --}}
      <h1 class="h4 fw-bold mb-2" style="color:#ffffff;">{{ $job->title ?? 'Tanpa Judul' }}</h1>

      {{-- Rincian --}}
      <section class="mb-4">
        <h2 class="h6 fw-semibold mb-2" style="color:#9fd3ff;">Rincian Lowongan</h2>
        <ul class="list-unstyled small mb-0" style="color:#dbeaf6;">

          @if($job->date_posted)
            <li><strong style="color:#ffffff;">Dipublikasikan:</strong> {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}</li>
          @endif

          @if($job->valid_through)
            <li><strong style="color:#ffffff;">Berlaku Hingga:</strong> {{ \Carbon\Carbon::parse($job->valid_through)->translatedFormat('d M Y') }}</li>
          @endif

          @if($job->hiring_organization)
            <li><strong style="color:#ffffff;">Perusahaan:</strong> {{ $job->hiring_organization }}</li>
          @endif

          @if($job->job_location)
            <li><strong style="color:#ffffff;">Lokasi Kerja:</strong> {{ $job->job_location }}</li>
          @endif

          @if($job->job_location_type)
            <li><strong style="color:#ffffff;">Tipe Lokasi:</strong> {{ ucfirst($job->job_location_type) }}</li>
          @endif

          @if($job->applicant_location_requirements)
            @php
              $raw = is_array($job->applicant_location_requirements) ? $job->applicant_location_requirements : (json_decode($job->applicant_location_requirements, true) ?: [$job->applicant_location_requirements]);
              $map = ['ID'=>'Indonesia','IDN'=>'Indonesia','id'=>'Indonesia','Id'=>'Indonesia'];
              $appReqDisplay = [];
              foreach($raw as $r){
                $rStr = trim((string)$r);
                if($rStr==='') continue;
                $u=strtoupper($rStr);
                $appReqDisplay[]=$map[$u] ?? $map[$rStr] ?? $rStr;
              }
              if(empty($appReqDisplay)) $appReqDisplay=['Indonesia'];
            @endphp
            <li><strong style="color:#ffffff;">Syarat Lokasi Pelamar:</strong> {{ implode(', ', $appReqDisplay) }}</li>
          @endif

          @if($job->employment_type)
            <li><strong style="color:#ffffff;">Jenis Pekerjaan:</strong> {{ ucfirst($job->employment_type) }}</li>
          @endif

          {{-- Gaji --}}
          @if($job->base_salary_string || $job->base_salary_min || $job->base_salary_max)
            <li>
              <strong style="color:#ffffff;">Gaji:</strong>
              <span style="color:#dbeaf6;">
                @if($job->base_salary_string)
                  {{ $job->base_salary_string }}
                @elseif($job->base_salary_min && $job->base_salary_max)
                  {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_min) }} –
                  {{ number_format($job->base_salary_max) }} / {{ $job->base_salary_unit ?? 'MONTH' }}
                @elseif($job->base_salary_min)
                  mulai {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_min) }}
                @elseif($job->base_salary_max)
                  s.d. {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_max) }}
                @endif
              </span>
            </li>
          @endif

          @if($job->identifier_value)
            <li><strong style="color:#ffffff;">ID Lowongan:</strong> {{ $job->identifier_name ?? 'job_id' }}: {{ $job->identifier_value }}</li>
          @endif

          <li><strong style="color:#ffffff;">Kirim Langsung:</strong> {{ $job->direct_apply ? 'Ya' : 'Tidak' }}</li>
        </ul>
      </section>

      {{-- Deskripsi --}}
      <section class="mb-3">
        <p class="mb-1">
          <strong style="color:#ffffff;">Posisi:</strong>
          <span style="color:#dbeaf6;">{{ $job->title }}</span>
        </p>
        <div class="small" style="color:#dbeaf6;">
          {!! $job->description_html ?? ($job->description ? nl2br(e($job->description)) : '<em style="color:#9fb0c8;">Deskripsi belum tersedia.</em>') !!}
        </div>
      </section>

      {{-- Laporkan (perubahan #1 & #2: outline → solid danger) --}}
      <div class="mb-3">
        @auth
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reportJobModal">Laporkan Lowongan</button>
        @else
          <a href="{{ route('login') }}" class="btn btn-danger btn-sm">Login untuk Laporkan</a>
        @endauth
      </div>

      {{-- RELATED --}}
      <div class="mt-4">
        <h3 class="h6 fw-semibold mb-2" style="color:#9fd3ff;">Loker terbaru</h3>
        @if(!empty($relatedJobs) && $relatedJobs->count() > 0)
          @foreach($relatedJobs as $r)
            <div>
              <a href="{{ url('/loker/'.$r->id) }}" style="color:#cfe6ff;text-decoration:none;">
                • {{ $r->title ?? 'Tanpa Judul' }}
              </a>
              @if($r->date_posted)
                <span class="small" style="color:#9fb0c8;"> — {{ \Carbon\Carbon::parse($r->date_posted)->translatedFormat('d M Y') }}</span>
              @endif
            </div>
          @endforeach
        @else
          <div class="text-muted small" style="color:#9fb0c8;">Tidak ada loker terkait.</div>
        @endif
      </div>

      {{-- 5 Kota Teratas --}}
      <div class="mt-4">
        <h3 class="h6 fw-semibold mb-2" style="color:#9fd3ff;">5 Kota Teratas — Jelajahi berdasarkan kota</h3>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/jakarta">jakarta</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/bandung">bandung</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/surabaya">surabaya</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/medan">medan</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/semarang">semarang</a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- REPORT MODAL --}}
<div class="modal fade" id="reportJobModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#071524;color:#e6eef8;">
      <div class="modal-header">
        <h5 class="modal-title">Laporkan Lowongan: {{ $job->title }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="{{ route('reports.store') }}" method="POST">
        @csrf
        <input type="hidden" name="reportable_type" value="{{ addslashes(\App\Models\Job::class) }}">
        <input type="hidden" name="reportable_id" value="{{ $job->id }}">

        <div class="modal-body">
          <label class="form-label">Alasan pelaporan (wajib)</label>
          <textarea name="reason" class="form-control bg-transparent text-light border-secondary" rows="4" required>{{ old('reason') }}</textarea>

          @if($errors->has('reason'))
            <div class="alert alert-danger mt-2">{{ $errors->first('reason') }}</div>
          @endif
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Kirim Laporan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- APPLY FLAGS --}}
@php
  $directApply = !empty($job->direct_apply);
  $externalApplyUrl = null;
  $externalHost = null;

  if(!empty($job->apply_url)){
    if(\Illuminate\Support\Str::startsWith($job->apply_url,['http://','https://'])){
      $externalApplyUrl=$job->apply_url;
    } else {
      $externalApplyUrl='https://'.ltrim($job->apply_url,'/');
    }

    $p=parse_url($externalApplyUrl);
    if(!empty($p['host'])) $externalHost=preg_replace('/^www\./','',$p['host']);
  }

  $existingApp = auth()->check()
      ? auth()->user()->applications()->where('job_id',$job->id)->first()
      : null;
@endphp

{{-- FIXED CTA (tidak diubah, tetap sesuai logic lama) --}}
@if($directApply || $externalApplyUrl)
  <div class="position-fixed bottom-0 start-0 end-0" style="z-index:1500;">
    <div class="container py-3 d-flex justify-content-center">
      @if($directApply)
        @guest
          <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Login untuk Lamar</a>
        @else
          @if($existingApp)
            <button class="btn btn-secondary btn-lg" disabled>Sudah melamar — {{ ucfirst($existingApp->status ?? 'submitted') }}</button>
          @else
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#applyModal">Lamar via Teleworks</button>
          @endif
        @endguest

      @elseif($externalApplyUrl)
        <a href="{{ e($externalApplyUrl) }}" target="_blank" rel="noopener noreferrer nofollow"
           class="btn btn-success btn-lg">
          Lamar via Perusahaan
          @if($externalHost)
            <span class="badge bg-dark text-white ms-2">{{ $externalHost }}</span>
          @endif
        </a>
      @endif
    </div>
  </div>
@endif

{{-- INTERNAL APPLY MODAL --}}
@if($directApply)
<div class="modal fade" id="applyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="background:#071524;color:#e6eef8;">
      <div class="modal-header">
        <h5 class="modal-title">Lamar: {{ $job->title }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="{{ url('/loker/'.$job->id.'/apply') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <label class="form-label">Surat Lamaran (opsional)</label>
          <textarea name="cover_letter" rows="4" class="form-control bg-transparent text-light border-secondary">{{ old('cover_letter') }}</textarea>

          <label class="form-label mt-3">Unggah CV (opsional)</label>
          <input type="file" name="resume" accept=".pdf,.doc,.docx"
                 class="form-control bg-transparent text-light border-secondary">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Kirim Lamaran</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@endsection

