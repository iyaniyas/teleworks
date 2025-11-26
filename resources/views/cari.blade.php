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

  $majorCities = [
    'jakarta','surabaya','bandung','medan','semarang',
    'makassar','palembang','tangerang','bekasi','yogyakarta'
  ];

  $externalRendered = $external_rendered ?? false;

  /* =======================
     META TITLE + DESCRIPTION
     ======================= */
  $metaTitlePart = trim('Lowongan ' . ($qDisplay ?: 'kerja') . ($lokasiDisplay ? ' di ' . $lokasiDisplay : ''));

  $metaDescription =
      $metaTitlePart .
      ' — Temukan lowongan terbaru, remote, dan peluang kerja yang relevan. Telusuri berdasarkan posisi atau lokasi untuk hasil yang lebih spesifik.';
@endphp

@section('title', $metaTitlePart)
@section('meta_description', $metaDescription)

@push('head')
  <link rel="canonical" href="{{ $canonicalUrl }}" />

  {{-- ★ DINAMIS — sudah memakai $metaDescription --}}
  <meta name="description" content="{{ e($metaDescription) }}" />

  <!-- Open Graph -->
  <meta property="og:title" content="{{ e($metaTitlePart) }}" />
  <meta property="og:description" content="{{ e($metaDescription) }}" />
  <meta property="og:url" content="{{ $canonicalUrl }}" />
  <meta property="og:type" content="website" />
@endpush


@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-0">
        Lowongan {{ $qDisplay ?: 'kerja' }}{{ $lokasiDisplay ? ' di ' . $lokasiDisplay : '' }}
      </h1>
    </div>
  </div>


  <div class="card mb-3 p-3">
    <form method="GET" action="{{ route('search.index') }}" class="row g-2 align-items-center">
      <div class="col-md-5">
        <input id="search-q" type="search" name="q" value="{{ old('q', $qRaw ?? request('q')) }}"
               class="form-control form-control-dark" placeholder="Posisi, perusahaan, kata kunci..." autocomplete="off" />
      </div>

      <div class="col-md-5">
        <input id="search-lokasi" type="text" name="lokasi" value="{{ old('lokasi', $lokasiRaw ?? request('lokasi')) }}"
               class="form-control form-control-dark" placeholder="Lokasi (kota/kabupaten)" />
      </div>

      <div class="col-md-2 text-end">
        <button class="btn btn-outline-light btn-sm w-100">Cari</button>
      </div>
    </form>
  </div>


  @if(!empty($fallback_note))
    <div class="card mb-3 p-3 bg-dark text-light border-secondary">
      <div class="small-muted mb-2">{{ $fallback_note }}</div>
    </div>
  @endif


  {{-- HASIL --}}
  @if(isset($jobs) && $jobs->count())
    <div class="row g-3">
      @foreach($jobs as $job)
        @php
          $isExternal = $job->is_external ?? false;
          $href = $isExternal ? ($job->apply_url ?? $job->url ?? '#') : url('/loker/'.$job->id);

          $company = trim($job->company ?? '');
          $loc = trim($job->location ?? ($job->job_location ?? ''));
          $company = $company !== '' ? $company : 'Perusahaan tidak disebut';
          $loc = $loc !== '' ? $loc : 'Lokasi tidak diketahui';

          $postedModel = $job->date_posted ?? $job->created_at ?? null;
          try {
            $posted = $postedModel ? \Carbon\Carbon::parse($postedModel)->timezone('Asia/Jakarta') : null;
          } catch (\Throwable $e) {
            $posted = null;
          }
        @endphp

        <div class="col-12 col-md-6 col-lg-4">
          <article class="card h-100 shadow-sm hover-card p-3 border-0 bg-dark text-light">
            <div class="d-flex flex-column h-100">

              <div class="mb-2">
                <a href="{{ $href }}" class="h6 result-title text-decoration-none text-light"
                   @if($isExternal) target="_blank" rel="nofollow noopener" @endif>
                  {{ $job->title }}
                </a>
              </div>

              <div class="small-muted mb-2">
                {{ $company }} — {{ $loc }}
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


    @if(!$externalRendered)
      <div id="external-jobs-wrapper" class="mt-4" style="display:none;">
        <div class="card mb-3 p-3 bg-dark text-light border-secondary" id="external-jobs-note" style="display:none;">
          <div class="small-muted mb-2">Hasil tambahan dari sumber lain:</div>
        </div>

        <div id="external-jobs-list" class="row g-3"></div>

        <div id="external-jobs-empty" class="card p-3" style="display:none;">
          <p class="mb-0 muted">Tidak ada hasil tambahan.</p>
        </div>
      </div>
    @endif


    @if(!empty($qRaw) || (!empty($lokasiRaw) && empty($qRaw)))
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
              $slug = Str::slug($city, '-');
              $kata = $qRaw ? Str::slug($qRaw, '-') : '';
              $url = $kata ? url('/cari/'.$kata.'/'.$slug) : url('/cari/lokasi/'.$slug);
            @endphp
            <a href="{{ $url }}" class="btn btn-sm btn-outline-light">{{ ucwords(str_replace('-', ' ', $city)) }}</a>
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
  .hover-card { transition: 0.2s; }
  .hover-card:hover { transform: translateY(-4px); box-shadow: 0 4px 12px rgba(255,255,255,0.1); }
  .small-muted { color:#bbb !important; font-size:0.875rem; }
  .muted { color:#ccc; }
  .result-title:hover { color:#fff; }
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
            if (!isMobile() && el) {
                setTimeout(() => {
                    el.focus();
                    el.setSelectionRange(el.value.length, el.value.length);
                }, 40);
            }
        } catch {}
    }
    document.addEventListener('DOMContentLoaded', safeFocus);


    // AJAX LOADER
    var wrap = document.getElementById('external-jobs-wrapper');
    if (wrap) {
        var q = {!! json_encode($qRaw ?? '') !!};
        var lokasi = {!! json_encode($lokasiRaw ?? '') !!};

        var ajaxUrl = '{{ url('/ajax/external-jobs') }}' +
                      '?q=' + encodeURIComponent(q) +
                      '&lokasi=' + encodeURIComponent(lokasi);

        var list = document.getElementById('external-jobs-list');
        var note = document.getElementById('external-jobs-note');
        var empty = document.getElementById('external-jobs-empty');

        wrap.style.display = 'block';
        list.innerHTML = '<div class="col-12 text-center small-muted py-3">Memuat hasil tambahan…</div>';

        fetch(ajaxUrl)
          .then(r => r.json())
          .then(data => {
              list.innerHTML = '';
              if (!data.items || data.items.length === 0) {
                  note.style.display = 'none';
                  empty.style.display = 'block';
                  return;
              }
              note.style.display = 'block';

              data.items.forEach(it => {
                  var title = it.title || 'No title';
                  var comp = it.company || '';
                  var loc = it.location || '';
                  var apply = it.apply_url || it.url || '#';
                  var date = it.date_posted ? new Date(it.date_posted).toLocaleDateString('id-ID') : '';

                  var col = document.createElement('div');
                  col.className = 'col-12 col-md-6 col-lg-4';
                  col.innerHTML =
                    '<article class="card h-100 shadow-sm hover-card p-3 border-0 bg-dark text-light">' +
                    ' <div class="d-flex flex-column h-100">' +
                    '   <div class="mb-2"><a href="'+apply+'" target="_blank" rel="nofollow noopener" class="h6 text-light result-title">'+escapeHtml(title)+'</a></div>' +
                    '   <div class="small-muted mb-2">'+escapeHtml(comp)+(comp && loc ? ' — ' : '')+escapeHtml(loc)+'</div>' +
                    '   <div class="flex-grow-1"></div>' +
                    '   <div class="small-muted mt-auto">'+escapeHtml(date)+'</div>' +
                    ' </div>' +
                    '</article>';
                  list.appendChild(col);
              });
          })
          .catch(err => {
              console.warn(err);
              list.innerHTML = '';
              empty.style.display = 'block';
          });
    }

    function escapeHtml(s) {
      return (s+'').replace(/&/g,'&amp;').replace(/</g,'&lt;')
                   .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
                   .replace(/'/g,'&#039;');
    }
})();
</script>
@endpush

