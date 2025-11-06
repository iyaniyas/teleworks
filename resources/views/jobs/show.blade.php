@extends('layouts.app')

@section('title', ($job->title ? $job->title.' — ' : '').'Teleworks')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="{{ url('/') }}" class="text-decoration-none">Beranda</a>
      <span class="mx-2">/</span>
      <a href="{{ url('/cari') }}" class="text-decoration-none">Cari Lowongan</a>
    </div>
  </div>

  <h1 class="h4 mb-2">{{ $job->title ?? 'Tanpa Judul' }}</h1>

  <div class="text-muted mb-3">
    <span><strong>Perusahaan:</strong> {{ $job->hiring_organization ?? $job->company ?? '-' }}</span>
    @if(!empty($job->is_remote) && $job->is_remote) <span> • Remote</span>@endif
    @if(!empty($job->job_location) || !empty($job->location)) <span> • {{ $job->job_location ?? $job->location }}</span>@endif
    @if(!empty($job->date_posted)) <span> • Diposting {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}</span>@endif
  </div>

  @if(($job->base_salary_min || $job->base_salary_max))
    <div class="mb-3">
      <strong>Gaji:</strong>
      @php
        $cur = $job->base_salary_currency ?? 'IDR';
        $unit = $job->base_salary_unit ?? 'MONTH';
      @endphp
      @if($job->base_salary_min && $job->base_salary_max)
        {{ $cur }} {{ number_format($job->base_salary_min) }} – {{ number_format($job->base_salary_max) }} / {{ $unit }}
      @elseif($job->base_salary_min)
        mulai {{ $cur }} {{ number_format($job->base_salary_min) }} / {{ $unit }}
      @elseif($job->base_salary_max)
        s.d. {{ $cur }} {{ number_format($job->base_salary_max) }} / {{ $unit }}
      @endif
    </div>
  @endif

  <div class="mb-4">
    @if(!empty($job->description))
      {!! nl2br(e($job->description)) !!}
    @else
      <em>Deskripsi belum tersedia.</em>
    @endif
  </div>

  @if(!empty($job->apply_url))
    <a class="btn btn-primary" href="{{ $job->apply_url }}" rel="nofollow noopener" target="_blank">
      Lamar Sekarang
    </a>
  @endif
@endsection

@push('schema')
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'JobPosting',
  'title' => $job->title,
  'description' => strip_tags((string) $job->description),
  'datePosted' => $job->date_posted,
  'validThrough' => $job->valid_through ?? optional(\Carbon\Carbon::parse($job->date_posted ?? now()))->addDays(45)->toDateString(),
  'employmentType' => $job->employment_type,
  'hiringOrganization' => [
      '@type' => 'Organization',
      'name' => $job->hiring_organization ?? $job->company,
  ],
  'jobLocationType' => !empty($job->is_remote) && $job->is_remote ? 'TELECOMMUTE' : 'ONSITE',
  'jobLocation' => (!empty($job->is_remote) && $job->is_remote) ? null : [
      '@type' => 'Place',
      'address' => [
          '@type' => 'PostalAddress',
          'addressLocality' => $job->job_location ?? $job->location,
          'addressCountry' => 'ID',
      ],
  ],
  'baseSalary' => ($job->base_salary_min || $job->base_salary_max) ? [
      '@type' => 'MonetaryAmount',
      'currency' => $job->base_salary_currency ?? 'IDR',
      'value' => array_filter([
          '@type' => 'QuantitativeValue',
          'minValue' => $job->base_salary_min,
          'maxValue' => $job->base_salary_max,
          'unitText' => $job->base_salary_unit ?? 'MONTH',
      ]),
  ] : null,
  'directApply' => (bool) $job->direct_apply,
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

