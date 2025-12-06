@extends('layouts.app')

@section('title', 'Detail Lowongan')

@section('content')
<div class="container py-4">
  <h1 class="mb-3">{{ $job->title }}</h1>

  <div class="mb-4">
    {!! nl2br(e($job->description)) !!}
  </div>

  @php
    $now = \Carbon\Carbon::now();
    $paidUntil = null;

    if (!empty($job->paid_until)) {
        try { $paidUntil = \Carbon\Carbon::parse($job->paid_until); } catch (\Exception $e) { $paidUntil = null; }
    } elseif (!empty($job->expires_at)) {
        $paidUntil = $job->expires_at instanceof \Carbon\Carbon
            ? $job->expires_at
            : \Carbon\Carbon::parse($job->expires_at);
    }

    $isPaidActive = $paidUntil
        && $paidUntil->gt($now)
        && (int)($job->is_paid ?? 0) === 1;
  @endphp

  <div class="mb-3">
    <span class="badge bg-{{ $job->status === 'published' ? 'success' : 'secondary' }}">
      {{ $job->status }}
    </span>
    @if($isPaidActive)
      <span class="badge bg-info text-dark">
        Paket aktif s/d {{ $paidUntil->format('d M Y') }}
      </span>
    @elseif((int)($job->is_paid ?? 0) === 1)
      <span class="badge bg-warning text-dark">
        Paket sudah habis masa aktifnya
      </span>
    @endif
  </div>

  <div class="d-flex gap-2">
    @if(!$isPaidActive && $job->status !== 'published')
      {{-- Belum ada paket aktif untuk job ini --}}
      <a class="btn btn-primary"
         href="{{ route('purchase.create', ['job_id' => $job->id]) }}">
        Beli Paket untuk Lowongan Ini
      </a>
    @elseif($isPaidActive && $job->status !== 'published')
      {{-- Sudah dibayar, masih draft -> publish --}}
      <form action="{{ route('employer.jobs.publish', $job) }}"
            method="POST">
        @csrf
        <button class="btn btn-success">
          Publish Lowongan
        </button>
      </form>
    @elseif($job->status === 'published')
      {{-- Sudah published --}}
      <a class="btn btn-success"
         href="{{ route('jobs.show', $job->id) }}">
        Lihat Halaman Publik
      </a>
    @endif

    <a href="{{ route('employer.jobs.index') }}" class="btn btn-outline-secondary">
      Kembali ke daftar
    </a>
  </div>
</div>
@endsection

