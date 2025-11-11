@extends('layouts.app')

@section('title', ($job->title ?? 'Lowongan Kerja').' — Teleworks')

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
.job-desc { white-space: pre-line; line-height: 1.6; color: #dde6f5; }
.small-muted { color: #bbb !important; font-size: 0.875rem; }
.muted { color: #ccc; }
.highlight { color: #fff; font-weight: 500; }
</style>

<div class="mb-3">
  <a href="{{ url('/') }}">Beranda</a>
  <span class="mx-2 tw-muted">›</span>
  <a href="{{ url('/cari') }}">Cari Lowongan</a>
</div>

<div class="tw-card">
  <h1 class="h4 fw-bold mb-2" style="color:#f3f7ff;">{{ $job->title ?? 'Tanpa Judul' }}</h1>
  <div class="tw-muted mb-3">
    <strong style="color:#dbeeff;">{{ $job->hiring_organization ?? $job->company ?? 'Perusahaan Tidak Diketahui' }}</strong>
    @if($job->is_remote) • <span class="tw-badge">Remote</span>@endif
    @if($job->job_location) • {{ $job->job_location }}@endif
    @if($job->date_posted) • Diposting {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}@endif
  </div>

  @if($job->base_salary_min || $job->base_salary_max || $job->base_salary_string)
  <div class="tw-section mb-3">
    <strong>Gaji:</strong>
    @php $cur = $job->base_salary_currency ?? 'IDR'; $unit = $job->base_salary_unit ?? 'MONTH'; @endphp
    <div class="tw-salary mt-1">
      @if(!empty($job->base_salary_string))
        {{ $job->base_salary_string }}
      @elseif($job->base_salary_min && $job->base_salary_max)
        {{ $cur }} {{ number_format($job->base_salary_min) }} – {{ number_format($job->base_salary_max) }} / {{ $unit }}
      @elseif($job->base_salary_min)
        mulai {{ $cur }} {{ number_format($job->base_salary_min) }} / {{ $unit }}
      @elseif($job->base_salary_max)
        s.d. {{ $cur }} {{ number_format($job->base_salary_max) }} / {{ $unit }}
      @endif
    </div>
  </div>
  @endif

  <div class="tw-section mb-4">
    <h2 class="h5 fw-bold mb-3" style="color:#cfe6ff;">Job Details</h2>
    <ul class="list-unstyled small" style="line-height:1.8;">
      @if($job->date_posted)
        <li><strong>Date Posted:</strong> {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}</li>
      @endif

      @if($job->valid_through)
        <li><strong>Valid Through:</strong> {{ \Carbon\Carbon::parse($job->valid_through)->translatedFormat('d M Y') }}</li>
      @endif

      @if($job->hiring_organization)
        <li><strong>Hiring Organization:</strong> {{ $job->hiring_organization }}</li>
      @endif

      @if($job->job_location)
        <li><strong>Job Location:</strong> {{ $job->job_location }}</li>
      @endif

      @if($job->job_location_type)
        <li><strong>Job Location Type:</strong> {{ ucfirst($job->job_location_type) }}</li>
      @endif

      @if($job->applicant_location_requirements)
        @php
          $appReq = is_array($job->applicant_location_requirements) ? $job->applicant_location_requirements : (json_decode($job->applicant_location_requirements, true) ?: [$job->applicant_location_requirements]);
        @endphp
        <li><strong>Applicant Location Requirements:</strong> {{ implode(', ', $appReq) }}</li>
      @endif

      @if($job->employment_type)
        <li><strong>Employment Type:</strong> {{ ucfirst($job->employment_type) }}</li>
      @endif

      @if($job->base_salary_string)
        <li><strong>Base Salary:</strong> {{ $job->base_salary_string }}</li>
      @endif

      @if($job->identifier_value)
        <li><strong>Identifier:</strong> {{ $job->identifier_name ?? 'job_id' }}: {{ $job->identifier_value }}</li>
      @endif

      <li><strong>Direct Apply:</strong> {{ !empty($job->direct_apply) ? 'Yes' : 'No' }}</li>
    </ul>
  </div>

  <div class="tw-section mb-4 job-desc">
    {!! $job->description ? nl2br(e($job->description)) : '<em class="tw-muted">Deskripsi belum tersedia.</em>' !!}
  </div>

  @if(!empty($job->apply_url))
  <div class="mb-4">
    <a href="{{ $job->apply_url }}" target="_blank" rel="nofollow noopener" class="tw-btn">Lamar Sekarang →</a>
  </div>
  @else
  <div class="tw-alert mb-4">Link lamaran belum tersedia.</div>
  @endif

  <div class="tw-muted small">
    @if($job->valid_through)
      Berlaku sampai {{ \Carbon\Carbon::parse($job->valid_through)->translatedFormat('d M Y') }}
    @endif
  </div>
</div>
</div>
@endsection

@push('schema')
<script type="application/ld+json">
{!! $jobPostingJsonLd !!}
</script>
@endpush

