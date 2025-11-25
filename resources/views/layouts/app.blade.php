<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  @php
    // gunakan $timestamp jika disediakan oleh view (mis. waktu publish job)
    // format sebaiknya sudah diberikan oleh controller (contoh: 'd M Y, H:i WIB')
    $providedTimestamp = $timestamp ?? null;

    $baseTitle = trim($__env->yieldContent('title')) ?: 'Teleworks — Job Remote WFH';
    $metaDesc = trim($__env->yieldContent('meta_description')) ?: 'Temukan lowongan kerja remote, WFH, dan freelance terbaru di Teleworks.';
  @endphp

  <title>{{ $baseTitle }}@if($providedTimestamp) — {{ $providedTimestamp }}@endif</title>

  <meta name="description" content="{{ $metaDesc }}@if($providedTimestamp) (Diperbarui {{ $providedTimestamp }})@endif" />
  <meta name="robots" content="index, follow" />

  <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/Inter-Variable.woff2') }}" crossorigin>

  @vite('resources/js/app.js')

  @stack('schema')
  @stack('head')

  <style>
    .tw-gradient-logo {
      background: linear-gradient(90deg, #ffffff, #c9c9c9);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
  </style>
</head>
<body class="bg-[#0b1220] text-[#e6eef8] min-h-screen flex flex-col">
  <div class="container mx-auto px-4 py-4 flex-1">
    {{-- HEADER --}}
    <header class="flex items-center justify-between mb-6">
      <a href="{{ route('home') }}"
         class="text-3xl font-bold tracking-wide tw-gradient-logo text-uppercase"
         style="letter-spacing: 1px;">
         TELEWORKS
      </a>

      <nav class="flex items-center space-x-4">
        <a href="{{ route('search.index') }}"
           class="text-light text-sm px-3 py-2 rounded-md hover:bg-white/5">
          Semua Lowongan
        </a>
      </nav>
    </header>

    {{-- MAIN CONTENT --}}
    <main>
      @yield('content')
    </main>
  </div>

  {{-- FOOTER --}}
  <footer class="bg-[#070812] border-t border-[#1a1f26] mt-10">
    <div class="container mx-auto px-4 py-6">
      <div class="md:flex md:justify-between md:items-start">
        <div class="mb-4 md:mb-0">
          <a href="/"><span class="text-xl font-bold tw-gradient-logo">TELEWORKS</span></a>
          <p class="text-light text-sm mt-2">Temukan pekerjaan remote, WFH, dan freelance terbaru. © {{ date('Y') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm text-light">
          <div>
            <div class="font-medium text-light mb-1">Menu</div>
            <a href="{{ route('search.index') }}" class="block py-0.5 text-light">Semua Lowongan</a>
          </div>
          <div>
            <div class="font-medium text-light mb-1">Tentang</div>
            <a href="/about" class="block py-0.5 text-light">Tentang Teleworks</a>
            <a href="/privacy" class="block py-0.5 text-light">Kebijakan Privasi</a>
          </div>
        </div>
      </div>

      <div class="mt-6 text-xs text-light">
        Hasil pencarian dapat disimpan untuk analitik. Dibuat dengan ♥ iyaniyas.
      </div>
    </div>


<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-1Z77NN195L"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-1Z77NN195L');
</script>


  </footer>

  @stack('scripts')
</body>
</html>

