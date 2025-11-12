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
body { background-color: #0f1115; color: #e6eef8; }
a { color: #9ecbff; text-decoration: none; }
a:hover { color: #cfe6ff; text-decoration: underline; }
.tw-card { background: #181a20; border: 1px solid #2a2d35; border-radius: .75rem; padding: 1.5rem; box-shadow: 0 0 15px rgba(0,0,0,0.4); }
.tw-section { background: #1f2229; border: 1px solid #2a2d35; border-radius: .5rem; padding: 1rem; }
.tw-muted { color: #9aa0ac; }
.tw-badge { background: linear-gradient(90deg,#10b981,#059669); color: white; font-weight: 600; border-radius: .3rem; padding: .25rem .5rem; font-size: .8rem; }
.tw-salary { color: #cde7ff; font-weight: 600; }
.tw-btn { background: linear-gradient(90deg,#2563eb,#1e40af); color: #fff; border: none; border-radius: .5rem; padding: .6rem 1.5rem; font-weight: 600; text-decoration: none; transition: .2s; }
.tw-btn:hover { background: linear-gradient(90deg,#1d4ed8,#1e3a8a); color: #fff; }
.tw-alert { background: #22252c; border: 1px solid #2e323b; color: #b0b6c3; border-radius: .5rem; padding: .75rem 1rem; }
.job-desc { line-height: 1.6; color: #dde6f5; }
.small-muted { color: #bbb !important; font-size: 0.875rem; }
.muted { color: #ccc; }
.highlight { color: #fff; font-weight: 500; }
.related-list a { display:block; padding:.4rem 0; border-bottom:1px dashed rgba(255,255,255,0.03); }
.city-links a { display:inline-block; margin-right:.5rem; margin-bottom:.25rem; padding:.35rem .6rem; background:#0e1720; border-radius:.4rem; text-decoration:none; color:#9ecbff; font-size:.9rem; }
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
          // decode & normalize applicant location requirements,
          // map common country codes like ID/IDN -> Indonesia
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

      {{-- Base Salary: show string or structured numbers --}}
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

  @if(!empty($job->apply_url))
  <div class="mb-4">
    <a href="{{ $job->apply_url }}" target="_blank" rel="nofollow noopener" class="tw-btn">Lamar Sekarang →</a>
  </div>
  @else
  <div class="tw-alert mb-4">Link lamaran belum tersedia.</div>
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
      <a href="https://remotewfh.id/cari?q=&lokasi=jakarta">jakarta</a>
      <a href="https://remotewfh.id/cari?q=&lokasi=bandung">bandung</a>
      <a href="https://remotewfh.id/cari?q=&lokasi=surabaya">surabaya</a>
      <a href="https://remotewfh.id/cari?q=&lokasi=medan">medan</a>
      <a href="https://remotewfh.id/cari?q=&lokasi=semarang">semarang</a>
    </div>
  </div>

</div>
</div>
@endsection

@push('schema')
@if(!empty($jobPostingJsonLd))
<script type="application/ld+json">
{!! $jobPostingJsonLd !!}
</script>
@endif
@endpush

