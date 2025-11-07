@extends('layouts.app')

@section('title', ($job->title ?? 'Lowongan Kerja').' — Teleworks')

@section('content')
<div class="container my-4">

  {{-- Navigasi kecil --}}
  <div class="mb-3">
    <a href="{{ url('/') }}" class="text-decoration-none text-secondary">Beranda</a>
    <span class="mx-2 text-muted">›</span>
    <a href="{{ url('/cari') }}" class="text-decoration-none text-secondary">Cari Lowongan</a>
  </div>

  {{-- Judul lowongan --}}
  <h1 class="h4 fw-bold mb-2">{{ $job->title ?? 'Tanpa Judul' }}</h1>

  {{-- Info ringkas perusahaan, lokasi, tanggal --}}
  <div class="text-muted mb-3">
    <strong>{{ $job->hiring_organization ?? $job->company ?? 'Perusahaan Tidak Diketahui' }}</strong>
    @if($job->is_remote)
      • <span class="badge bg-success">Remote</span>
    @endif
    @if($job->job_location)
      • {{ $job->job_location }}
    @endif
    @if($job->date_posted)
      • Diposting {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}
    @endif
  </div>

  {{-- Gaji --}}
  @if($job->base_salary_min || $job->base_salary_max)
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

  {{-- Deskripsi pekerjaan --}}
  <div class="border rounded p-3 bg-light mb-4" style="white-space: pre-line;">
    {!! $job->description ? nl2br(e($job->description)) : '<em>Deskripsi belum tersedia.</em>' !!}
  </div>

  {{-- Tombol Lamar --}}
  @if(!empty($job->apply_url))
    <div class="mb-4">
      <a href="{{ $job->apply_url }}"
         target="_blank"
         rel="nofollow noopener"
         class="btn btn-primary px-4 py-2 fw-semibold">
         Lamar Sekarang →
      </a>
    </div>
  @else
    <div class="alert alert-secondary">
      Link lamaran belum tersedia.
    </div>
  @endif

  {{-- Info tambahan --}}
  <div class="text-muted small">
    @if($job->valid_through)
      Berlaku sampai {{ \Carbon\Carbon::parse($job->valid_through)->translatedFormat('d M Y') }}
    @endif
  </div>
</div>
@endsection

@push('schema')
<script type="application/ld+json">
{!! $jobPostingJsonLd !!}
</script>
@endpush

