@extends('layouts.app')

@php
  use Illuminate\Support\Str;
  $timestamp = now()->timezone(config('app.timezone','Asia/Jakarta'))->format('M Y');

  $qRaw = $qRaw ?? request('q') ?? '';
  $lokasiRaw = $lokasiRaw ?? request('lokasi') ?? '';

  $qDisplay = $q ?? ($qRaw ? mb_strtolower($qRaw) : '');
  $lokasiDisplay = $lokasi ?? ($lokasiRaw ? mb_strtolower($lokasiRaw) : '');

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

  $currentPage = isset($jobs) && method_exists($jobs, 'currentPage') ? $jobs->currentPage() : (int) (request('page', 1));
  $lastPage = isset($jobs) && method_exists($jobs, 'lastPage') ? $jobs->lastPage() : null;

  $majorCities = [
    'jakarta','surabaya','bandung','medan','semarang',
    'makassar','palembang','tangerang','bekasi','yogyakarta'
  ];
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

  {{-- generic fallback note (non-branded) --}}
  @if(!empty($fallback_note))
    <div class="card mb-3 p-3 bg-dark text-light border-secondary">
      <div class="small-muted mb-2">{{ $fallback_note }}</div>
    </div>
  @endif

  {{-- Results --}}
  @if(isset($jobs) && $jobs->count())
    <div class="row g-3">
      @foreach($jobs as $job)
        @php
          $isExternal = isset($job->is_external) && $job->is_external;
          $href = $isExternal ? ($job->apply_url ?? $job->url ?? '#') : url('/loker/'.$job->id);

          $companyRaw = trim($job->company ?? '');
          $locationRaw = trim($job->location ?? ($job->job_location ?? ''));

          $companyDisplay = $companyRaw !== '' ? $companyRaw : null;
          $locationDisplayJob = $locationRaw !== '' ? $locationRaw : null;

          if (!$companyDisplay && !$locationDisplayJob) {
            $companyPlaceholder = 'Perusahaan tidak disebut';
            $locationPlaceholder = 'Lokasi tidak diketahui';
            $showCompany = $companyPlaceholder;
            $showLocation = $locationPlaceholder;
            $separator = ' — ';
          } else {
            $showCompany = $companyDisplay;
            $showLocation = $locationDisplayJob;
            $separator = ($showCompany && $showLocation) ? ' — ' : '';
          }

          $postedModel = $job->date_posted ?? $job->created_at ?? null;
          try {
            $posted = $postedModel ? \Carbon\Carbon::parse($postedModel)->timezone(config('app.timezone','Asia/Jakarta')) : null;
          } catch (\Throwable $e) { $posted = null; }
        @endphp

        <div class="col-12 col-md-6 col-lg-4">
          <article class="card h-100 shadow-sm hover-card p-3 border-0 bg-dark text-light">
            <div class="d-flex flex-column h-100">
              <div class="mb-2">
                <a href="{{ $href }}" @if($isExternal) target="_blank" rel="nofollow noopener" @endif class="h6 result-title text-decoration-none text-light">
                  {{ $job->title }}
                </a>
              </div>

              <div class="small-muted mb-2">
                @if(isset($companyPlaceholder))
                  {{ $showCompany }}{{ $separator }}{{ $showLocation }}
                @else
                  {{ $showCompany ?? '' }}{{ $separator }}{{ $showLocation ?? '' }}
                @endif
              </div>

              <div class="flex-grow-1 small-muted mb-2"></div>

              <div class="d-flex justify-content-between align-items-center mt-auto small-muted">
                <div>{{ $posted ? $posted->format('d M Y') : '' }}</div>
              </div>
            </div>
          </article>
        </div>
      @endforeach
    </div>

    <div class="mt-4">
      {{ $jobs->links('pagination::bootstrap-5') }}
    </div>

    {{-- show city quick links if query present OR location-only view --}}
    @if( !empty($qRaw) || (!empty($lokasiRaw) && empty($qRaw)) )
      <div class="mt-3">
        <div class="small-muted mb-2">
          @if(!empty($qRaw))
            Cari "{{ $qRaw }}" di kota besar:
          @else
            Jelajahi lokasi lain:
          @endif
        </div>

        <div class="d-flex flex-wrap gap-2">
          @foreach($majorCities as $city)
            @php
              $citySlug = Str::slug($city, '-');
              $kataSlug = $qRaw ? Str::slug($qRaw, '-') : '';
              $link = $kataSlug ? url('/cari/' . $kataSlug . '/' . $citySlug) : url('/cari/lokasi/' . $citySlug);
              $label = ucwords(str_replace(['-'], [' '], $city));
            @endphp
            <a href="{{ $link }}" class="btn btn-sm btn-outline-light">{{ $label }}</a>
          @endforeach
        </div>
      </div>
    @endif

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
  .muted { color: #ccc; }
  .result-title:hover { color: #fff; }
  .gap-2 { gap: .5rem; }
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
        } catch (e) {}
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

