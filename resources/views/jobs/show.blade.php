{{-- resources/views/jobs/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Loker '.($job->title ?? 'Lowongan Kerja').' — Teleworks')

@php
  $lokasi = !empty($job->job_location) ? $job->job_location : 'Indonesia';
  $metaDescriptionText = 'Loker '.e($job->title).' di '.e($lokasi).' remote, WFH, dan freelance terbaru di Teleworks.';
@endphp

@section('meta_description', $metaDescriptionText)

@push('head')
  @if(!empty($jobPostingJsonLd))
    <script type="application/ld+json">
{!! $jobPostingJsonLd !!}
    </script>
  @endif
@endpush

@section('content')
@php
  $now = \Carbon\Carbon::now();

  $expiresAt = $job->expires_at instanceof \Carbon\Carbon
      ? $job->expires_at
      : ($job->expires_at ? \Carbon\Carbon::parse($job->expires_at) : null);

  $isExpired = $job->status === 'published'
      && !is_null($expiresAt)
      && $expiresAt->lt($now);

  $isSaved = auth()->check()
      ? \App\Models\Bookmark::where('user_id', auth()->id())
            ->where('job_id', $job->id)
            ->exists()
      : false;
@endphp

<div class="container my-4">
  <div class="card mb-4 bg-dark text-light border-secondary">
    <div class="card-body">

      {{-- Breadcrumb --}}
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
          <li class="breadcrumb-item">
            <a href="{{ url('/') }}" class="text-decoration-none link-light">Beranda</a>
          </li>
          <li class="breadcrumb-item">
            <a href="{{ url('/cari') }}" class="text-decoration-none link-light">Cari Lowongan</a>
          </li>
          <li class="breadcrumb-item active" aria-current="page">
            {{ $job->title ?? 'Lowongan' }}
          </li>
        </ol>
      </nav>

      {{-- Judul + tombol simpan --}}
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0">
          {{ $job->title ?? 'Tanpa Judul' }}
        </h1>

        @auth
          <form action="{{ route('jobs.bookmark', $job->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit"
                    class="btn btn-sm {{ $isSaved ? 'btn-outline-warning' : 'btn-outline-light' }}">
              @if($isSaved)
                ★ Tersimpan
              @else
                ☆ Simpan Loker
              @endif
            </button>
          </form>
        @else
          <a href="{{ route('login', ['intended' => url()->current()]) }}"
             class="btn btn-sm btn-outline-light">
            ☆ Simpan Loker
          </a>
        @endauth
      </div>

      {{-- Warning kadaluarsa --}}
      @if($isExpired)
        <div class="alert alert-warning small">
          ⚠️ Lowongan ini sudah <strong>kadaluarsa</strong>. Informasi masih dapat dibaca,
          namun kemungkinan besar tidak lagi menerima lamaran.
          @php
            $tanggalAkhir = $job->valid_through ?? $job->expires_at;
          @endphp
          @if($tanggalAkhir)
            <div class="mt-1">
              Berlaku hingga:
              {{ \Carbon\Carbon::parse($tanggalAkhir)->translatedFormat('d M Y') }}
            </div>
          @endif
        </div>
      @endif

      {{-- Rincian --}}
      <section class="mb-4">
        <h2 class="h6 fw-semibold mb-2 text-info">Rincian Lowongan</h2>
        <ul class="list-unstyled small mb-0">

          @if($job->date_posted)
            <li>
              <strong>Dipublikasikan:</strong>
              {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}
            </li>
          @endif

          @if($job->valid_through)
            <li>
              <strong>Berlaku Hingga:</strong>
              {{ \Carbon\Carbon::parse($job->valid_through)->translatedFormat('d M Y') }}
            </li>
          @endif

          @php
            try {
                $companyModel = $job->company()->first();
            } catch (\Throwable $e) {
                $companyModel = null;
            }
            $companyName = $companyModel && !empty($companyModel->name)
                ? $companyModel->name
                : ($job->hiring_organization ?? null);
          @endphp

          @if($companyName)
            <li>
              <strong>Perusahaan:</strong>
              {{ $companyName }}
            </li>
          @endif

          @if($job->job_location)
            <li>
              <strong>Lokasi Kerja:</strong>
              {{ $job->job_location }}
            </li>
          @endif

          @if($job->job_location_type)
            <li>
              <strong>Tipe Lokasi:</strong>
              {{ ucfirst($job->job_location_type) }}
            </li>
          @endif

          @if($job->applicant_location_requirements)
            @php
              $raw = is_array($job->applicant_location_requirements)
                  ? $job->applicant_location_requirements
                  : (json_decode($job->applicant_location_requirements, true)
                        ?: [$job->applicant_location_requirements]);

              $map = ['ID'=>'Indonesia','IDN'=>'Indonesia','id'=>'Indonesia','Id'=>'Indonesia'];
              $appReqDisplay = [];
              foreach($raw as $r){
                $rStr = trim((string)$r);
                if($rStr==='') continue;
                $u = strtoupper($rStr);
                $appReqDisplay[] = $map[$u] ?? $map[$rStr] ?? $rStr;
              }
              if(empty($appReqDisplay)) $appReqDisplay = ['Indonesia'];
            @endphp
            <li>
              <strong>Syarat Lokasi Pelamar:</strong>
              {{ implode(', ', $appReqDisplay) }}
            </li>
          @endif

          @if($job->employment_type)
            <li>
              <strong>Jenis Pekerjaan:</strong>
              {{ ucfirst($job->employment_type) }}
            </li>
          @endif

          {{-- Gaji --}}
          @if($job->base_salary_string || $job->base_salary_min || $job->base_salary_max)
            <li>
              <strong>Gaji:</strong>
              <span>
                @if($job->base_salary_string)
                  {{ $job->base_salary_string }}
                @elseif($job->base_salary_min && $job->base_salary_max)
                  {{ $job->base_salary_currency ?? 'IDR' }}
                  {{ number_format($job->base_salary_min) }} –
                  {{ number_format($job->base_salary_max) }}
                  / {{ $job->base_salary_unit ?? 'MONTH' }}
                @elseif($job->base_salary_min)
                  mulai {{ $job->base_salary_currency ?? 'IDR' }}
                  {{ number_format($job->base_salary_min) }}
                @elseif($job->base_salary_max)
                  s.d. {{ $job->base_salary_currency ?? 'IDR' }}
                  {{ number_format($job->base_salary_max) }}
                @endif
              </span>
            </li>
          @endif

          @if($job->identifier_value)
            <li>
              <strong>ID Lowongan:</strong>
              {{ $job->identifier_name ?? 'job_id' }}: {{ $job->identifier_value }}
            </li>
          @endif

          <li>
            <strong>Kirim Langsung:</strong>
            {{ $job->direct_apply ? 'Ya' : 'Tidak' }}
          </li>
        </ul>
      </section>

      {{-- Deskripsi --}}
      <section class="mb-4">
        <p class="mb-1">
          <strong>Posisi:</strong>
          <span>{{ $job->title }}</span>
        </p>
        <div class="small">
          {!! $job->description_html
              ?? ($job->description
                    ? nl2br(e($job->description))
                    : '<em>Deskripsi belum tersedia.</em>') !!}
        </div>
      </section>

      {{-- RELATED --}}
      <section class="mt-4">
        <h3 class="h6 fw-semibold mb-2 text-info">Loker terbaru</h3>
        @if(!empty($relatedJobs) && $relatedJobs->count() > 0)
          @foreach($relatedJobs as $r)
            <div>
              <a href="{{ url('/loker/'.$r->id) }}" class="link-light text-decoration-none">
                • {{ $r->title ?? 'Tanpa Judul' }}
              </a>
              {{-- tanggal DIHAPUS untuk menghindari teks abu-abu kontras rendah --}}
            </div>
          @endforeach
        @else
          <div class="text-secondary small">Tidak ada loker terkait.</div>
        @endif
      </section>

      {{-- 5 Kota Teratas --}}
      <section class="mt-4">
        <h3 class="h6 fw-semibold mb-2 text-info">5 Kota Teratas — Jelajahi berdasarkan kota</h3>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/jakarta">jakarta</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/bandung">bandung</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/surabaya">surabaya</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/medan">medan</a>
          <a class="btn btn-sm btn-outline-light" href="/cari/lokasi/semarang">semarang</a>
        </div>
      </section>

    </div>
  </div>
</div>

@php
  $directApply = !empty($job->direct_apply);
  $externalApplyUrl = null;
  $externalHost = null;

  if (!empty($job->apply_url)) {
    if (\Illuminate\Support\Str::startsWith($job->apply_url, ['http://','https://'])) {
      $externalApplyUrl = $job->apply_url;
    } else {
      $externalApplyUrl = 'https://'.ltrim($job->apply_url,'/');
    }

    $p = parse_url($externalApplyUrl);
    if (!empty($p['host'])) {
      $externalHost = preg_replace('/^www\./','',$p['host']);
    }
  }

  $existingApp = auth()->check()
      ? auth()->user()->applications()->where('job_id',$job->id)->first()
      : null;
@endphp

{{-- FIXED CTA (hanya apply) --}}
@if($directApply || $externalApplyUrl)
  <div class="fixed-bottom bg-dark border-top border-secondary">
    <div class="container py-3 d-flex justify-content-center">
      @if($directApply)
        @guest
          <a href="{{ route('login', ['intended' => url()->current()]) }}" class="btn btn-primary btn-lg">
            Login untuk Lamar
          </a>
        @else
          @if($existingApp)
            <button class="btn btn-secondary btn-lg" disabled>
              Sudah melamar — {{ ucfirst($existingApp->status ?? 'submitted') }}
            </button>
          @else
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#applyModal">
              Lamar Langsung!
            </button>
          @endif
        @endguest
      @elseif($externalApplyUrl)
        <a href="{{ e($externalApplyUrl) }}" target="_blank" rel="noopener noreferrer nofollow"
           class="btn btn-success btn-lg">
          Lamar:
          @if($externalHost)
            <span class="badge bg-dark text-light ms-2">{{ $externalHost }}</span>
          @endif
        </a>
      @endif
    </div>
  </div>
@endif

{{-- INTERNAL APPLY MODAL --}}
@if($directApply)
<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Lamar: {{ $job->title }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ url('/loker/'.$job->id.'/apply') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Surat Lamaran (opsional)</label>
            <textarea name="cover_letter" rows="4"
                      class="form-control bg-transparent text-light border-secondary">{{ old('cover_letter') }}</textarea>
          </div>

          <div>
            <label class="form-label">Unggah CV (opsional)</label>
            <input type="file" name="resume" accept=".pdf,.doc,.docx"
                   class="form-control bg-transparent text-light border-secondary">
          </div>
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

