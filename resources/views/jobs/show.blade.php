@extends('layouts.app')
@section('title', 'Loker '.($job->title ?? 'Lowongan Kerja').' — Teleworks')
@php
  // Gunakan lokasi dari kolom job_location jika ada, kalau kosong tampilkan 'Indonesia'
  $lokasi = !empty($job->job_location) ? $job->job_location : 'Indonesia';
  $metaDescriptionText = 'Loker '.e($job->title).' di '.e($lokasi).' remote, WFH, dan freelance terbaru di Teleworks.';
@endphp

@section('meta_description', $metaDescriptionText)



@section('content')
<div class="container my-4">
<style>
/* ----- Basic page & theme ----- */
body { background-color: #0f1115; color: #e6eef8; }
a { color: #9ecbff; text-decoration: none; }
a:hover { color: #cfe6ff; text-decoration: underline; }

/* ----- Card / sections ----- */
.tw-card { background: #181a20; border: 1px solid #2a2d35; border-radius: .75rem; padding: 1.5rem; box-shadow: 0 0 15px rgba(0,0,0,0.4); }
.tw-section { background: #1f2229; border: 1px solid #2a2d35; border-radius: .5rem; padding: 1rem; }
.tw-muted { color: #9aa0ac; }
.tw-badge { background: linear-gradient(90deg,#10b981,#059669); color: white; font-weight: 600; border-radius: .3rem; padding: .25rem .5rem; font-size: .8rem; }
.tw-salary { color: #cde7ff; font-weight: 600; }
.tw-alert { background: #22252c; border: 1px solid #2e323b; color: #b0b6c3; border-radius: .5rem; padding: .75rem 1rem; }

/* ----- text / lists ----- */
.job-desc { line-height: 1.6; color: #dde6f5; }
.small-muted { color: #bbb !important; font-size: 0.875rem; }
.muted { color: #ccc; }
.highlight { color: #fff; font-weight: 500; }
.related-list a { display:block; padding:.4rem 0; border-bottom:1px dashed rgba(255,255,255,0.03); }
.city-links a { display:inline-block; margin-right:.5rem; margin-bottom:.25rem; padding:.35rem .6rem; background:#0e1720; border-radius:.4rem; text-decoration:none; color:#9ecbff; font-size:.9rem; }

/* ----- Bottom CTA bar (full-width) ----- */
/* Hidden by default (translateY(100%)), slide up when visible */
.cta-bar {
  position: fixed;
  left: 0;
  bottom: 0;
  width: 100%;
  z-index: 1500;
  background: rgba(15,17,21,0.95);
  border-top: 1px solid rgba(45,50,60,0.9);
  backdrop-filter: blur(6px);
  padding: .6rem 1rem;
  display: flex;
  justify-content: center;
  align-items: center;
  transform: translateY(100%);
  transition: transform 240ms cubic-bezier(.22,.9,.35,1), opacity 180ms ease;
  opacity: 0;
  pointer-events: none; /* disable interactions while hidden */
}

/* When active - visible */
.cta-bar.visible {
  transform: translateY(0);
  opacity: 1;
  pointer-events: auto;
}

/* Inner button style */
.cta-bar .cta-btn {
  display: inline-flex;
  align-items: center;
  gap: .6rem;
  padding: .65rem 1.1rem;
  border-radius: .6rem;
  background: linear-gradient(90deg,#2563eb,#1e40af);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 8px 24px rgba(0,0,0,0.45);
  -webkit-font-smoothing: antialiased;
}

/* subtle hover */
.cta-bar .cta-btn:hover {
  transform: translateY(-2px);
}

/* small screens: keep padding and comfortable hit area */
@media (max-width: 520px) {
  .cta-bar { padding: .55rem .6rem; }
  .cta-bar .cta-btn {
    width: 100%;
    justify-content: center;
    padding: .75rem 1rem;
    border-radius: .45rem;
  }
}

/* Accessibility focus */
.cta-bar .cta-btn:focus {
  outline: 3px solid rgba(99,102,241,0.22);
  outline-offset: 3px;
}

/* Make sure the main container has bottom padding so content isn't hidden */
body .container { padding-bottom: 5.25rem; } /* room for CTA bar */
</style>

<div class="mb-3">
  <a href="{{ url('/') }}">Beranda</a>
  <span class="mx-2 tw-muted">›</span>
  <a href="{{ url('/cari') }}">Cari Lowongan</a>
</div>

<div class="tw-card">
  <h1 class="h4 fw-bold mb-2" style="color:#f3f7ff;">{{ $job->title ?? 'Tanpa Judul' }}</h1>

  <div class="tw-section mb-4">
    <h2 class="h5 fw-bold mb-3" style="color:#cfe6ff;">Rincian Lowongan</h2>
    <ul class="list-unstyled small" style="line-height:1.8;">
      @if($job->date_posted)
        <li><strong>Dipublikasikan:</strong> {{ \Carbon\Carbon::parse($job->date_posted)->translatedFormat('d M Y') }}</li>
      @endif

      @if($job->valid_through)
        <li><strong>Berlaku Hingga:</strong> {{ \Carbon\Carbon::parse($job->valid_through)->translatedFormat('d M Y') }}</li>
      @endif

      @if($job->hiring_organization)
        <li><strong>Perusahaan:</strong> {{ $job->hiring_organization }}</li>
      @endif

      @if($job->job_location)
        <li><strong>Lokasi Kerja:</strong> {{ $job->job_location }}</li>
      @endif

      @if($job->job_location_type)
        <li><strong>Tipe Lokasi:</strong> {{ ucfirst($job->job_location_type) }}</li>
      @endif

      @if($job->applicant_location_requirements)
        @php
          // decode & normalize applicant location requirements,
          // map common country codes like ID/IDN -> Indonesia
          $raw = is_array($job->applicant_location_requirements) ? $job->applicant_location_requirements : (json_decode($job->applicant_location_requirements, true) ?: [$job->applicant_location_requirements]);
          $map = ['ID' => 'Indonesia','IDN' => 'Indonesia','id'=>'Indonesia','Id'=>'Indonesia'];
          $appReqDisplay = [];
          foreach($raw as $r) {
              $rStr = trim((string)$r);
              if ($rStr === '') continue;
              $u = strtoupper($rStr);
              if (isset($map[$u])) {
                  $appReqDisplay[] = $map[$u];
              } elseif (isset($map[$rStr])) {
                  $appReqDisplay[] = $map[$rStr];
              } else {
                  $appReqDisplay[] = $rStr;
              }
          }
          if (empty($appReqDisplay)) { $appReqDisplay = ['Indonesia']; }
        @endphp
        <li><strong>Syarat Lokasi Pelamar:</strong> {{ implode(', ', $appReqDisplay) }}</li>
      @endif

      @if($job->employment_type)
        <li><strong>Jenis Pekerjaan:</strong> {{ ucfirst($job->employment_type) }}</li>
      @endif

      {{-- Base Salary: show string or structured numbers --}}
      @if($job->base_salary_string || $job->base_salary_min || $job->base_salary_max)
        <li><strong>Gaji:</strong>
          @if(!empty($job->base_salary_string))
            {{ $job->base_salary_string }}
          @elseif($job->base_salary_min && $job->base_salary_max)
            {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_min) }} – {{ number_format($job->base_salary_max) }} / {{ $job->base_salary_unit ?? 'MONTH' }}
          @elseif($job->base_salary_min)
            mulai {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_min) }} / {{ $job->base_salary_unit ?? 'MONTH' }}
          @elseif($job->base_salary_max)
            s.d. {{ $job->base_salary_currency ?? 'IDR' }} {{ number_format($job->base_salary_max) }} / {{ $job->base_salary_unit ?? 'MONTH' }}
          @endif
        </li>
      @endif

      @if($job->identifier_value)
        <li><strong>ID Lowongan:</strong> {{ $job->identifier_name ?? 'job_id' }}: {{ $job->identifier_value }}</li>
      @endif

      <li><strong>Kirim Langsung:</strong> {{ !empty($job->direct_apply) ? 'Ya' : 'Tidak' }}</li>
    </ul>
  </div>

  <div class="tw-section mb-4 job-desc">
    {{-- Tambahkan judul posisi ke dalam deskripsi sesuai permintaan --}}
    <p><strong>Posisi:</strong> {{ $job->title }}</p>
    {!! $job->description_html ?? ($job->description ? nl2br(e($job->description)) : '<em class="tw-muted">Deskripsi belum tersedia.</em>') !!}
  </div>

  @if(empty($job->apply_url))
  <div class="tw-alert mb-4">Link lamaran belum tersedia.</div>
  @endif

  {{-- Related jobs (loker terbaru) --}}
  <div class="tw-section mb-4">
    <h3 class="h6 fw-bold mb-2" style="color:#cfe6ff;">Loker terbaru</h3>
    @if(!empty($relatedJobs) && $relatedJobs->count() > 0)
      <div class="related-list small">
        @foreach($relatedJobs as $r)
          <a href="{{ url('/loker/'.$r->id) }}" title="{{ $r->title ?? 'Lowongan' }}" class="muted">
            • {{ $r->title ?? 'Tanpa Judul' }}
            @if($r->date_posted)
              <span class="small-muted"> — {{ \Carbon\Carbon::parse($r->date_posted)->translatedFormat('d M Y') }}</span>
            @endif
          </a>
        @endforeach
      </div>
    @else
      <div class="small-muted">Tidak ada loker terkait.</div>
    @endif
  </div>

  {{-- 5 Kota Teratas di Indonesia (huruf kecil) --}}
  <div class="tw-section mb-4">
    <h3 class="h6 fw-bold mb-2" style="color:#cfe6ff;">5 Kota Teratas — Jelajahi berdasarkan kota</h3>
    <div class="city-links">
      <a href="https://www.teleworks.id/cari/lokasi/jakarta">jakarta</a>
      <a href="https://www.teleworks.id/cari/lokasi/bandung">bandung</a>
      <a href="https://www.teleworks.id/cari/lokasi/surabaya">surabaya</a>
      <a href="https://www.teleworks.id/cari/lokasi/medan">medan</a>
      <a href="https://www.teleworks.id/cari/lokasi/semarang">semarang</a>
    </div>
  </div>

</div>
</div>

{{-- ----- Bottom CTA bar (render hanya bila apply_url tersedia) ----- --}}
@if(!empty($job->apply_url))
<div id="teleworks-cta-bar" class="cta-bar">
  <a
    id="teleworks-cta-link"
    href="{{ $job->apply_url }}"
    target="_blank"
    rel="nofollow noopener noreferrer"
    class="cta-btn"
    aria-label="Lamar posisi {{ $job->title }}"
    title="Lamar Sekarang — {{ $job->title }}"
    tabindex="-1"  {{-- start non-focusable until bar is visible --}}
  >
    <span aria-hidden="true" class="fa fa-briefcase"></span>
    <span>Lamar Sekarang</span>
  </a>
</div>

{{-- Lightweight JS: tampilkan bar saat scroll ke bawah, sembunyikan saat scroll ke atas
     Accessibility:
     - Don't touch aria-hidden on container
     - Manage tabindex of the interactive link so it's not reachable when hidden
--}}
<script>
(function(){
  if (typeof window === 'undefined') return;

  const bar = document.getElementById('teleworks-cta-bar');
  const link = document.getElementById('teleworks-cta-link');
  if (!bar || !link) return;

  let lastScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
  let ticking = false;
  const threshold = 150; // minimal scroll from top before considering showing

  function showBar() {
    if (!bar.classList.contains('visible')) {
      bar.classList.add('visible');
      // enable keyboard focus on the link
      link.removeAttribute('tabindex');
    }
  }

  function hideBar() {
    if (bar.classList.contains('visible')) {
      bar.classList.remove('visible');
      // prevent the link from being focusable while hidden
      link.setAttribute('tabindex', '-1');
    } else {
      // ensure it's not focusable by default
      link.setAttribute('tabindex', '-1');
    }
  }

  function onScroll() {
    const current = window.pageYOffset || document.documentElement.scrollTop || 0;

    if (!ticking) {
      window.requestAnimationFrame(function() {
        // show when scrolling down and we've scrolled past threshold
        if (current > lastScroll && current > threshold) {
          showBar();
        } else {
          hideBar();
        }
        lastScroll = current <= 0 ? 0 : current; // avoid negative
        ticking = false;
      });
      ticking = true;
    }
  }

  // passive listener for performance
  window.addEventListener('scroll', onScroll, { passive: true });

  // On load: if page already scrolled below threshold, keep hidden until user scrolls directionally
  document.addEventListener('DOMContentLoaded', function(){
    const start = window.pageYOffset || document.documentElement.scrollTop || 0;
    if (start > threshold) {
      // keep hidden initially; link remains non-focusable
      hideBar();
    } else {
      hideBar();
    }
  });

  // Hide bar when focusing on form elements (avoid covering them)
  document.addEventListener('focusin', function(e){
    const t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) {
      hideBar();
    }
  });

  // Optional: keyboard shortcut "L" focuses the apply link when visible (accessibility enhancement)
  document.addEventListener('keydown', function(e){
    // ignore if modifier keys are used or if focus already in input
    if (e.altKey || e.ctrlKey || e.metaKey) return;
    const active = document.activeElement;
    if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) return;

    if ((e.key === 'l' || e.key === 'L') && bar.classList.contains('visible')) {
      e.preventDefault();
      link.focus();
    }
  });

})();
</script>
@endif

@endsection

@push('schema')
@if(!empty($jobPostingJsonLd))
<script type="application/ld+json">
{!! $jobPostingJsonLd !!}
</script>
@endif
@endpush

