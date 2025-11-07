<!doctype html> 
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  @php
    $timestamp = now()->format('d M Y, H:i');
    $baseTitle = trim($__env->yieldContent('title')) ?: 'Teleworks — Job Remote WFH';
    $metaDesc = trim($__env->yieldContent('meta_description')) ?: 'Temukan lowongan kerja remote, WFH, dan freelance terbaru di Teleworks.';
  @endphp

  <title>{{ $baseTitle }} — {{ $timestamp }}</title>
  <meta name="description" content="{{ $metaDesc }} (Diperbarui {{ $timestamp }})" />
  <meta name="robots" content="index, follow" />

  {{-- Preload font Inter (pilih salah satu sumber font) --}}
  {{-- Opsi A: font ada di public/fonts (tidak di-hash) --}}
  <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/Inter-Variable.woff2') }}" crossorigin>

  {{-- Opsi B: kalau font kamu taruh di resources/fonts dan diproses Vite, pakai ini, lalu HAPUS preload Opsi A di atas --}}
  {{-- <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/Inter-Variable.woff2') }}" crossorigin> --}}

  {{-- Vite entry: app.js sudah import app.css + bootstrap + alpine --}}
  @vite('resources/js/app.js')

  @stack('head')
</head>
<body class="bg-[#0b1220] text-[#e6eef8]">
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

  @stack('scripts')
</body>
</html>

