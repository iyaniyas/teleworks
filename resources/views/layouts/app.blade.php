<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  @php
    $providedTimestamp = $timestamp ?? null;
    $baseTitle = trim($__env->yieldContent('title')) ?: 'Teleworks';
    $metaDesc = trim($__env->yieldContent('meta_description')) ?: 'Mencari kerja dari jarak jauh, langsung dari rumah.';
  @endphp

  <title>{{ $baseTitle }}@if($providedTimestamp) {{ $providedTimestamp }}@endif</title>
  <meta name="description" content="{{ e($metaDesc) }}@if($providedTimestamp) (Diperbarui {{ $providedTimestamp }})@endif" />
  <meta name="robots" content="index, follow" />

  {{-- Vite --}}
  @vite('resources/js/app.js')

  @stack('head')
</head>
<body class="site-shell d-flex flex-column" style="background:#081425;color:#e6eef8;min-height:100vh;">
  {{-- NAVBAR (responsive) --}}
  <nav class="navbar navbar-expand-lg navbar-dark" style="background:#061122;border-bottom:1px solid rgba(255,255,255,0.04);">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
        <span class="tw-gradient-logo fs-4 fw-bold" style="letter-spacing:1px;background:linear-gradient(90deg,#fff,#c9c9c9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">TELE WORKS</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
              aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link py-2" href="{{ route('search.index') }}">Semua Lowongan</a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2" href="{{ route('public.searchlogs') }}">Pencarian Terbaru</a>
          </li>
          <li class="nav-item d-lg-none">
            <a class="nav-link py-2" href="{{ route('about') }}">Tentang</a>
          </li>
        </ul>

        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          @auth
            {{-- Dashboard link based on role --}}
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
        <div class="col-md-6 mb-3">
          <a href="/" class="text-decoration-none" aria-label="Beranda Teleworks">
            <span class="tw-gradient-logo fs-5 fw-bold" style="background:linear-gradient(90deg,#fff,#c9c9c9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">TELE WORKS</span>
          </a>
          <p class="mb-0 small" style="color:#9fb0c8;">Cari kerja jarak jauh, langsung dari rumah. © {{ date('Y') }}</p>
        </div>

        <div class="col-md-6">
          <div class="row">
            <div class="col-6">
              {{-- gunakan heading semantik tapi tampilkan seperti h6 --}}
              <h2 class="h6 text-light">Menu</h2>

              {{-- gunakan utilitas Bootstrap untuk area sentuh --}}
              <a href="{{ route('search.index') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;" aria-label="Semua Lowongan">Semua Lowongan</a>
              <a href="{{ route('public.searchlogs') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;" aria-label="Pencarian Terbaru">Pencarian Terbaru</a>
            </div>

            <div class="col-6">
              <h2 class="h6 text-light">Tentang</h2>

              <a href="{{ route('about') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;" aria-label="Tentang Teleworks">Tentang Teleworks</a>
              <a href="{{ route('privacy') }}" class="d-block py-2 small" style="color:#cbd5e1 !important;" aria-label="Kebijakan Privasi">Kebijakan Privasi</a>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3 small" style="color:#9fb0c8;">
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

