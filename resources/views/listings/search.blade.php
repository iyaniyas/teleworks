{{-- resources/views/listings/search.blade.php --}}
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Hasil Pencarian — Teleworks</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#0b1220; color:#e6eef8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial; }
    .card { background: #0f1724; border: 1px solid rgba(255,255,255,0.04); }
    .muted { color: rgba(230,238,248,0.6); }
    .small-muted { color: rgba(230,238,248,0.55); font-size:.9rem; }
    .result-title { color:#e6eef8; text-decoration:none; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 mb-0">Hasil Pencarian</h1>
      <a href="{{ url('/') }}" class="text-decoration-none muted">Beranda</a>
    </div>

    {{-- Search form (agar user bisa refine langsung) --}}
    <div class="card mb-3 p-3">
      <form method="GET" action="{{ route('listings.search') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
          <input type="search" name="kata" value="{{ old('kata', $q ?? '') }}" class="form-control form-control-dark" placeholder="Kata kunci..." />
        </div>
        <div class="col-md-4">
          <input type="text" name="lokasi" value="{{ old('lokasi', $lokasi ?? '') }}" class="form-control form-control-dark" placeholder="Lokasi (kota/kabupaten)" />
        </div>
        <div class="col-md-3 text-end">
          <button class="btn btn-outline-light btn-sm">Cari</button>
        </div>
      </form>
    </div>

    {{-- Summary --}}
    <div class="mb-3 small-muted">
      Menampilkan <strong>{{ $results->total() ?? 0 }}</strong> hasil untuk 
      <span class="badge bg-secondary">{{ $q ?: '—' }}</span>
      @if(!empty($lokasi)) di <span class="badge bg-secondary">{{ $lokasi }}</span> @endif
    </div>

    {{-- Results list --}}
    @if($results->count())
      @foreach($results as $item)
        <article class="card mb-2 p-3">
          <div class="d-flex justify-content-between">
            <div>
              <a href="{{ url('/listings/'.$item->id) }}" class="h6 result-title">{{ $item->title }}</a>
              <div class="small-muted">{{ $item->location ?? '—' }} · {{ $item->created_at ? $item->created_at->format('d M Y') : '' }}</div>
            </div>
            <div class="text-end small-muted">
              <div>{{ $item->category ?? '' }}</div>
            </div>
          </div>

          <p class="mt-2 muted mb-0">
            {{ \Illuminate\Support\Str::limit(strip_tags($item->description ?? ''), 180) }}
            <a href="{{ url('/listings/'.$item->id) }}" class="text-decoration-none"> &raquo; baca</a>
          </p>
        </article>
      @endforeach

      <div class="mt-3">
        {{ $results->links('pagination::bootstrap-5') }}
      </div>

    @else
      <div class="card p-3">
        <p class="mb-0 muted">Tidak ditemukan hasil. Coba variasi kata kunci atau hilangkan filter lokasi.</p>
      </div>
    @endif

    <footer class="mt-5 muted small-muted">&copy; {{ date('Y') }} Teleworks</footer>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

