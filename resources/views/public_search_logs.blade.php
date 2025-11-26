@extends('layouts.app')

@section('title', 'Pencarian Terbaru')

@section('content')
  <div class="mb-4">
    <h1 class="h4 text-light">Pencarian Terbaru</h1>
    <div style="color:#ddd;">Daftar pencarian terbaru dari pengguna Teleworks</div>
  </div>

  <div class="card p-3 mb-3 bg-dark border-secondary">
    <form method="GET" action="{{ route('public.searchlogs') }}" class="row g-2 align-items-center">
      <div class="col-md-6">
        <input type="search" name="q" value="{{ $q }}" placeholder="Filter kata kunci..." class="form-control form-control-dark" />
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-light btn-sm w-100">Filter</button>
      </div>
    </form>
  </div>

  <div class="card p-2 bg-dark border-secondary">
    <div class="table-responsive">
      <table class="table table-dark table-hover mb-0">
        <thead class="table-secondary text-dark">
          <tr>
            <th style="width:1%">#</th>
            <th>Kata kunci</th>
            <th style="width:22%">Filter</th>
            <th style="width:12%">Hasil</th>
            <th style="width:18%">Waktu</th>
          </tr>
        </thead>
        <tbody>

          @forelse ($logs as $log)
            @php
              $filters = $log->filters ? json_decode($log->filters, true) : [];
              $qVal = trim($log->q ?? '');
              $locVal = trim($filters['lokasi'] ?? '');

              // build slug versions
              $qSlug = $qVal ? \Illuminate\Support\Str::slug($qVal, '-') : null;
              $locSlug = $locVal ? \Illuminate\Support\Str::slug($locVal, '-') : null;

              // build link
              if ($qSlug && $locSlug) {
                  $url = url('/cari/'.$qSlug.'/'.$locSlug);
              } elseif ($qSlug) {
                  $url = url('/cari/'.$qSlug);
              } elseif ($locSlug) {
                  $url = url('/cari/lokasi/'.$locSlug);
              } else {
                  $url = null;
              }
            @endphp

            <tr>
              <td>{{ $loop->iteration + (($logs->currentPage()-1) * $logs->perPage()) }}</td>

              <td>
                @if($url)
                  <a href="{{ $url }}" class="text-light text-decoration-none">
                    <strong>{{ $qVal ?: '(kosong)' }}</strong>
                  </a>
                @else
                  <span style="color:#eee;"><strong>(kosong)</strong></span>
                @endif
              </td>

              <td style="color:#ccc;">
                @if(!empty($filters))
                  @foreach($filters as $k => $v)
                    <div><strong>{{ $k }}</strong>: {{ is_scalar($v) ? $v : json_encode($v) }}</div>
                  @endforeach
                @else
                  -
                @endif
              </td>

              <td style="color:#eee;">{{ $log->results_count }}</td>

              <td style="color:#ddd;">
                {{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }}
              </td>
            </tr>

          @empty
            <tr>
              <td colspan="5" class="text-center py-4" style="color:#ddd;">
                Belum ada data pencarian.
              </td>
            </tr>
          @endforelse

        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $logs->links('pagination::bootstrap-5') }}
  </div>
@endsection

