<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'Teleworks — Job Remote WFH')</title>
  <meta name="robots" content="index, follow" />

  {{-- Bootstrap lokal --}}
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

  {{-- (Opsional) Font lokal kustom: taruh file woff2 di public/fonts/ --}}
  {{-- Hapus blok @font-face ini kalau tidak punya file font lokal --}}
  <style>
    /* Contoh: pakai Inter lokal jika ada */
    @font-face {
      font-family: "Inter";
      src: url("{{ asset('fonts/Inter-Variable.woff2') }}") format("woff2");
      font-weight: 100 900;
      font-style: normal;
      font-display: swap;
    }

    body {
      /* Urutan: pakai Inter lokal jika ada, lalu fallback ke sistem (semua lokal) */
      font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: #0b1220; color: #e6eef8;
    }
    .card { background: #0f1724; border: 1px solid rgba(255,255,255,0.04); }
    .muted { color: rgba(230,238,248,0.6); }
    .result-title { color: #e6eef8; text-decoration: none; }
    .small-muted { color: rgba(230,238,248,0.55); font-size: .85rem; }
  </style>

  @stack('head')
<meta name="robots" content="noindex, nofollow">
</head>
<body>
  <div class="container py-4">
    <header class="d-flex justify-content-between align-items-center mb-4">
      <a href="{{ url('/') }}" class="text-decoration-none text-light h4 mb-0">Teleworks</a>
      <nav>
        <a href="{{ url('/') }}" class="text-decoration-none muted me-3">Beranda</a>
        <a href="{{ route('search.index') }}" class="text-decoration-none muted">Cari Lowongan</a>
      </nav>
    </header>

    <main>
      @yield('content')
    </main>

    <footer class="mt-5 muted small-muted">
      &copy; {{ date('Y') }} Teleworks — Hasil pencarian disimpan untuk analitik.
    </footer>
  </div>

  {{-- Bootstrap bundle lokal (termasuk Popper) --}}
  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  @stack('scripts')
</body>
</html>

