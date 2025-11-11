@extends('layouts.app')

@php
  $tz   = config('app.timezone', 'Asia/Jakarta');
  $now  = now()->timezone($tz);
  $tgl  = $now->format('d M Y');
  $waktu= $now->format('d M Y H:i') . ' WIB';

  $seoMain = 'Lowongan ' . (!empty($q) ? ucwords($q) : 'Kerja')
           . (!empty($lokasi) ? ' di ' . $lokasi : '')
           . ($wfh == '1' ? ' WFH/Remote' : '');

  $seoTitle = trim($seoMain) . ' — Teleworks (' . $tgl . ')';
@endphp

@section('title', $seoTitle)

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
      {{ $seoMain }}
      @if($wfh == '1')
        <span class="badge bg-success ms-1">WFH</span>
      @endif
    </h1>
    <a href="{{ url('/') }}" class="text-decoration-none muted">Beranda</a>
  </div>

  <div class="card mb-3 p-3">
    <form method="GET" action="{{ route('search.index') }}" class="row g-2 align-items-center">
      <div class="col-md-5">
        <input type="search" name="q" value="{{ $q }}" class="form-control form-control-dark"
               placeholder="Posisi, perusahaan, kata kunci..." />
      </div>

      <div class="col-md-3">
        <input type="text" name="lokasi" value="{{ $lokasi }}" class="form-control form-control-dark"
               placeholder="Lokasi (kota/kabupaten)" />
      </div>

      <div class="col-md-2 d-flex align-items-center">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="wfh" id="wfh" value="1" {{ $wfh == '1' ? 'checked' : '' }}>
          <label class="form-check-label small-muted" for="wfh">WFH saja</label>
        </div>
      </div>

      <div class="col-md-2 text-end">
        <button class="btn btn-outline-light btn-sm w-100">Cari</button>
      </div>
    </form>
  </div>

  <div class="mb-1 small-muted">
    Diperbarui: <span class="highlight">{{ $waktu }}</span>
  </div>
  <div class="mb-3 small-muted">
    Menampilkan <span class="highlight">{{ number_format($jobs->total()) }}</span> hasil.
  </div>

  @if($jobs->count())
    {{-- === GRID MODE === --}}
    <div class="row g-3">
      @foreach($jobs as $job)
        @php
          $href = url('/loker/'.$job->id);
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
                @if(($job->is_wfh ?? 0) || ($job->is_remote ?? 0))
                  <span class="badge bg-success ms-1">WFH</span>
                @endif
                @if(!empty($job->source))
                  <span class="badge bg-info text-dark ms-1">{{ ucfirst($job->source) }}</span>
                @endif
              </div>

              <p class="flex-grow-1 muted small mb-2">
                {{ \Illuminate\Support\Str::limit(strip_tags($job->description), 120) }}
              </p>

              <div class="d-flex justify-content-between align-items-center mt-auto small-muted">
                <div>{{ optional($job->date_posted ?? $job->created_at)->timezone(config('app.timezone','Asia/Jakarta'))->format('d M Y') }}</div>
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
      <p class="mb-0 muted">Tidak ditemukan lowongan. Coba variasikan kata kunci atau hilangkan filter lokasi/WFH.</p>
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

