<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  @php
    $providedTimestamp = $timestamp ?? null;
    $baseTitle = trim($__env->yieldContent('title')) ?: 'Teleworks';
    $metaDesc = trim($__env->yieldContent('meta_description')) ?: 'Mencari kerja dari jarak jauh, langsung dari rumah.';

    $currentUrl = url()->current();
    $siteName = 'Teleworks';
    $ogImage = asset('og-image.jpg');
  @endphp

  <title>{{ $baseTitle }}@if($providedTimestamp) {{ $providedTimestamp }}@endif</title>
  <meta name="description" content="{{ e($metaDesc) }}@if($providedTimestamp) (Diperbarui {{ $providedTimestamp }})@endif" />
  <meta name="robots" content="index, follow" />

  {{-- FAVICON --}}
  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

  {{-- OPEN GRAPH --}}
  <meta property="og:site_name" content="{{ $siteName }}" />
  <meta property="og:title" content="{{ $baseTitle }}@if($providedTimestamp) {{ $providedTimestamp }}@endif" />
  <meta property="og:description" content="{{ $metaDesc }}" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ $currentUrl }}" />
  <meta property="og:image" content="{{ $ogImage }}" />
  <meta property="og:locale" content="id_ID" />

  {{-- TWITTER CARD --}}
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $baseTitle }}@if($providedTimestamp) {{ $providedTimestamp }}@endif" />
  <meta name="twitter:description" content="{{ $metaDesc }}" />
  <meta name="twitter:image" content="{{ $ogImage }}" />

  {{-- STRUCTURED DATA: WEBSITE NAME --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Teleworks",
    "alternateName": "Teleworks Indonesia",
    "url": "{{ url('/') }}"
  }
  </script>

  {{-- Vite --}}
  @vite('resources/js/app.js')

  @stack('head')
</head>
<body class="site-shell d-flex flex-column" style="background:#081425;color:#e6eef8;min-height:100vh;">
  {{-- NAVBAR --}}
  <nav class="navbar navbar-expand-lg navbar-dark" style="background:#061122;border-bottom:1px solid rgba(255,255,255,0.04);">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
        <span class="tw-gradient-logo fs-4 fw-bold" style="letter-spacing:1px;background:linear-gradient(90deg,#fff,#c9c9c9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
          TELE WORKS
        </span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
              aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNav">
        {{-- MENU KIRI --}}
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link py-2" href="{{ route('search.index') }}">Semua Loker</a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2" href="{{ route('public.searchlogs') }}">Pencarian Terbaru</a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2 fw-semibold text-warning" href="{{ route('pricing') }}">
              Pasang Loker
            </a>
          </li>
          <li class="nav-item d-lg-none">
            <a class="nav-link py-2" href="{{ route('about') }}">Tentang</a>
          </li>
        </ul>

        {{-- MENU KANAN --}}
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          @auth
            @php
              $user = auth()->user();
              $dashUrl = '/dashboard';
              if(method_exists($user,'hasRole')) {
                if($user->hasRole('job_seeker')) $dashUrl = route('seeker.dashboard');
                elseif($user->hasRole('company')) $dashUrl = route('employer.dashboard');
                elseif($user->hasRole('admin')) $dashUrl = route('admin.dashboard');
              } else {
                $dashUrl = route('dashboard');
              }
            @endphp

            <li class="nav-item d-none d-lg-block">
              <a class="nav-link py-2" href="{{ $dashUrl }}">Dashboard</a>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle py-2" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                {{ auth()->user()->name }}
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                <li><a class="dropdown-item" href="{{ $dashUrl }}">Dashboard</a></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="POST" action="{{ route('logout') }}" class="px-3">
                    @csrf
                    <button class="btn btn-link text-danger p-0" type="submit">Logout</button>
                  </form>
                </li>
              </ul>
            </li>
          @else
            <li class="nav-item">
              <a class="nav-link py-2" href="{{ route('login') }}">Masuk</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-sm btn-primary ms-2" href="{{ route('register') }}">Daftar</a>
            </li>
          @endauth
        </ul>
      </div>
    </div>
  </nav>

  {{-- MAIN --}}
  <main class="flex-fill">
    <div class="container py-5">
      @yield('content')
    </div>
  </main>

  {{-- FOOTER --}}
  <footer class="mt-auto" style="background:#061122;border-top:1px solid rgba(255,255,255,0.04);color:#cbd5e1;">
    <div class="container py-4">
      <div class="row">
        {{-- BRAND --}}
        <div class="col-12 col-md-3 mb-3">
          <a href="/" class="text-decoration-none">
            <span class="tw-gradient-logo fs-5 fw-bold" style="background:linear-gradient(90deg,#fff,#c9c9c9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
              TELE WORKS
            </span>
          </a>
          <p class="mb-0 small" style="color:#9fb0c8;">
            Cari kerja jarak jauh, langsung dari rumah. © {{ date('Y') }}
          </p>
        </div>

        {{-- NAVIGASI (Menu + Tentang) --}}
        <div class="col-6 col-md-3">
          <h2 class="h6 text-light">Navigasi</h2>
          <a href="{{ route('search.index') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;">Semua Loker</a>
          <a href="{{ route('public.searchlogs') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;">Pencarian Terbaru</a>
          <a href="{{ route('pricing') }}" class="d-block py-2 small fw-semibold" style="color:#facc15 !important;">Pasang Loker</a>
          <a href="{{ route('about') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;">Tentang Teleworks</a>
          <a href="{{ route('privacy') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;">Kebijakan Privasi</a>
        </div>

        {{-- PALING BANYAK DICARI (UMUM) --}}
        <div class="col-6 col-md-3">
          <h2 class="h6 text-light">Paling Banyak Dicari</h2>
          <ul class="list-unstyled small mb-0">
            <li>
              <a href="{{ url('/cari/desain') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Desain
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/writer') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Writer
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/copywriter') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Copywriter
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/data-entry') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Data entry
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/admin-online') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Admin online
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/customer-service') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Customer service
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/social-media') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Social media
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/digital-marketing') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Digital marketing
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/video-editor') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Video editor
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/live-streaming') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Live streaming
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/freelance') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Freelance
              </a>
            </li>
          </ul>
        </div>

        {{-- PALING DICARI (IT) --}}
        <div class="col-12 col-md-3 mt-3 mt-md-0">
          <h2 class="h6 text-light">Paling Dicari (IT)</h2>
          <ul class="list-unstyled small mb-0">
            <li>
              <a href="{{ url('/cari/software-engineer') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Software engineer
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/frontend') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Frontend
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/backend') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Backend
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/fullstack') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Fullstack
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/mobile') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Mobile
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/data-analyst') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Data analyst
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/data-scientist') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                Data scientist
              </a>
            </li>
            <li>
              <a href="{{ url('/cari/devops-engineer') }}" class="d-block py-1" style="color:#cbd5e1 !important;">
                DevOps engineer
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-3 small" style="color:#9fb0c8;">
        VPS oleh: s.id/0NRys Hasil pencarian dapat disimpan untuk analitik. Dibuat dengan ♥ iyaniyas.
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

