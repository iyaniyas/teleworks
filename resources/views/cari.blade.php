@extends('layouts.app')

@php
  use Illuminate\Support\Str;

  // timestamp untuk halaman pencarian: tampilkan bulan & tahun
  $timestamp = now()->timezone(config('app.timezone','Asia/Jakarta'))->format('M Y');

  // Controller provides: 'jobs', 'q', 'lokasi', 'qRaw', 'lokasiRaw', 'wfh'
  // Fallbacks if direct access
  $qRaw = $qRaw ?? request('q') ?? '';
  $lokasiRaw = $lokasiRaw ?? request('lokasi') ?? '';

  // For display in UI we use lowercased strings (consistent with controller)
  $qDisplay = $q ?? ($qRaw ? mb_strtolower($qRaw) : '');
  $lokasiDisplay = $lokasi ?? ($lokasiRaw ? mb_strtolower($lokasiRaw) : '');

  // Build canonical:
  if (!empty($qRaw) || !empty($lokasiRaw)) {
      $kataSlug = $qRaw ? Str::slug($qRaw, '-') : null;
      $lokasiSlug = $lokasiRaw ? Str::slug($lokasiRaw, '-') : null;

      if (!$kataSlug && $lokasiSlug) {
          $canonicalUrl = url('/cari/lokasi/' . $lokasiSlug);
      } elseif ($kataSlug) {
          $canonicalUrl = url('/cari/' . $kataSlug . ($lokasiSlug ? '/' . $lokasiSlug : ''));
      } else {
          $canonicalUrl = url('/cari');
      }
  } else {
      $canonicalUrl = url('/cari');
  }
@endphp

@section('title', trim('Lowongan ' . ($qDisplay ?: 'kerja') . ($lokasiDisplay ? ' di ' . $lokasiDisplay : '')))

@push('head')
  <link rel="canonical" href="{{ $canonicalUrl }}" />
@endpush

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
      Lowongan {{ $qDisplay ?: 'kerja' }}{{ $lokasiDisplay ? ' di ' . $lokasiDisplay : '' }}
    </h1>
  </div>

  {{-- Form pencarian --}}
  <div class="card mb-3 p-3">
    <form method="GET" action="{{ route('search.index') }}" class="row g-2 align-items-center">
      <div class="col-md-5">
        <input id="search-q" type="search" name="q" value="{{ old('q', $qRaw ?? $q ?? request('q')) }}" class="form-control form-control-dark"
               placeholder="Posisi, perusahaan, kata kunci..." autocomplete="off" />
      </div>

      <div class="col-md-5">
        <input id="search-lokasi" type="text" name="lokasi" value="{{ old('lokasi', $lokasiRaw ?? $lokasi ?? request('lokasi')) }}" class="form-control form-control-dark"
               placeholder="Lokasi (kota/kabupaten)" />
      </div>

      <div class="col-md-2 text-end">
        <button class="btn btn-outline-light btn-sm w-100">Cari</button>
      </div>
    </form>
  </div>

  {{-- Daftar hasil --}}
  @if(isset($jobs) && $jobs->count())
    <div class="row g-3">
      @foreach($jobs as $job)
        @php
          $href = url('/loker/'.$job->id);
          $sourceLower = !empty($job->source) ? strtolower($job->source) : '';
          // Tampilkan date_posted kalau ada, fallback ke created_at
          $postedModel = $job->date_posted ?? $job->created_at ?? null;
          $posted = $postedModel ? optional($postedModel)->timezone(config('app.timezone','Asia/Jakarta')) : null;
          $postedDisplay = $posted ? $posted->format('d M Y H:i') . ' WIB' : null;
        @endphp

        <div class="col-12 col-md-6 col-lg-4">
          <article class="card h-100 shadow-sm hover-card p-3 border-0 bg-dark text-light">
            <div class="d-flex flex-column h-100">
              <div class="mb-2">
                <a href="{{ $href }}" class="h6 result-title text-decoration-none text-light">
                  {{ $job->title }}
                </a>
              </div>

              <div class="small-muted mb-2">
                {{ $job->company ?? 'Perusahaan tidak disebut' }} — {{ $job->location ?? $job->job_location ?? 'Lokasi tidak diketahui' }}
                @if(!empty($job->source) && $sourceLower !== 'theirstack')
                  <span class="badge bg-info text-dark ms-1">{{ ucfirst($job->source) }}</span>
                @endif
              </div>

              <div class="flex-grow-1 small-muted mb-2"></div>

              <div class="d-flex justify-content-between align-items-center mt-auto small-muted">
                <div>{{ $posted ? $posted->format('d M Y') : '' }}</div>
                @if(!empty($job->type))
                  <div class="text-end">{{ $job->type }}</div>
                @endif
              </div>
            </div>
          </article>
        </div>
      @endforeach
    </div>

    <div class="mt-4">
      {{ $jobs->links('pagination::bootstrap-5') }}
    </div>
  @else
    <div class="card p-3">
      <p class="mb-0 muted">Tidak ditemukan lowongan. Coba variasikan kata kunci atau hilangkan filter lokasi.</p>
    </div>
  @endif
@endsection

@push('styles')
<style>
  .hover-card {
    transition: all 0.2s ease-in-out;
  }
  .hover-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(255,255,255,0.1);
  }
  .small-muted {
    color: #bbb !important;
    font-size: 0.875rem;
  }
  .muted {
    color: #ccc;
  }
  .highlight {
    color: #fff;
    font-weight: 500;
  }
</style>
@endpush

@push('scripts')
<script>
  (function() {
    function isMobile() {
      return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    function safeFocus() {
      try {
        var el = document.getElementById('search-q');
        if (!el) return;
        if (!isMobile()) {
          setTimeout(function() {
            el.focus();
            var len = el.value.length;
            if (typeof el.selectionStart === 'number') {
              el.setSelectionRange(len, len);
            }
          }, 50);
        }
      } catch (e) {
        console.warn('safeFocus error', e);
      }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', safeFocus);
    } else {
      safeFocus();
    }

    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'visible') safeFocus();
    });
  })();
</script>
@endpush

