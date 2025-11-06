@extends('layouts.app')

@php
  $tz   = config('app.timezone', 'Asia/Jakarta');
  $now  = now()->timezone($tz);
  $tgl  = $now->format('d M Y');
  $waktu= $now->format('d M Y H:i') . ' WIB';

  // Susun frasa utama SEO
  $seoMain = 'Lowongan ' . (!empty($q) ? ucwords($q) : 'Kerja')
           . (!empty($lokasi) ? ' di ' . $lokasi : '')
           . ($wfh == '1' ? ' WFH/Remote' : '');

  // Judul halaman <title>
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
    @foreach($jobs as $job)
      @php
        // semua link utama diarahkan ke halaman detail internal Teleworks
        $href = url('/loker/'.$job->id);
      @endphp

      <article class="card mb-2 p-3">
        <div class="d-flex justify-content-between">
          <div>
            <a href="{{ $href }}"
               class="h6 result-title">
               {{ $job->title }}
            </a>
            <div class="small-muted">
              {{ $job->company }} — {{ $job->location }}
              @if($job->is_wfh) · <span class="badge bg-success">WFH</span>@endif
              @if(!empty($job->source)) · <span class="badge bg-info text-dark">{{ ucfirst($job->source) }}</span>@endif
            </div>
          </div>
          <div class="text-end small-muted">
            <div>{{ optional($job->created_at)->timezone(config('app.timezone','Asia/Jakarta'))->format('d M Y') }}</div>
            @if(!empty($job->type))
              <div class="mt-1">{{ $job->type }}</div>
            @endif
          </div>
        </div>

        <p class="mt-2 muted mb-0">
          {{ \Illuminate\Support\Str::limit(strip_tags($job->description), 180) }}
          <a href="{{ $href }}"
             class="text-decoration-none"> &raquo; baca</a>
        </p>
      </article>
    @endforeach

    <div class="mt-3">
      {{ $jobs->links('pagination::bootstrap-5') }}
    </div>
  @else
    <div class="card p-3">
      <p class="mb-0 muted">Tidak ditemukan lowongan. Coba variasikan kata kunci atau hilangkan filter lokasi/WFH.</p>
    </div>
  @endif
@endsection

