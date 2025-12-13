{{-- resources/views/pricing.blade.php --}}
@extends('layouts.app')

@section('title','Pasang Loker dengan Fitur AI — Teleworks')
@section('meta_description','Pasang lowongan kerja di Teleworks dengan dukungan fitur AI untuk membantu penyusunan loker dan peringkasan pelamar. Proses rekrutmen lebih terarah, keputusan tetap di tangan HR.')

@section('content')

{{-- HERO --}}
<section class="py-5 text-center">
  <div class="container">
    <h1 class="fw-bold text-light mb-3">
      Pasang Loker dengan Fitur AI
    </h1>
    <p class="lead mx-auto" style="max-width:720px;color:rgba(230,238,248,0.85);">
      Teleworks membantu perusahaan mempublikasikan lowongan kerja sekaligus
      menyederhanakan proses rekrutmen dengan fitur AI yang menganalisis dan
      merangkum pelamar secara objektif. AI membantu, keputusan tetap di tangan HR.
    </p>
  </div>
</section>

{{-- PRICING --}}
<section id="plans" class="py-4">
  <div class="container">
    <div class="row g-4 justify-content-center">

      {{-- BASIC --}}
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0" style="background:rgba(255,255,255,0.03);">
          <div class="card-body d-flex flex-column">
            <h5 class="text-light mb-1">Basic</h5>
            <small style="color:rgba(230,238,248,0.7);">Cocok untuk uji coba</small>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 100.000</div>
              <div style="color:rgba(230,238,248,0.7);">per lowongan</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang 30 hari</li>
              <li class="mb-2">• 1 lowongan aktif</li>
            </ul>

            <div class="mt-auto">
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Pasang Loker
              </a>
            </div>
          </div>
        </div>
      </div>

      {{-- STANDARD --}}
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-lg" style="background:rgba(255,255,255,0.04);">
          <div class="card-body d-flex flex-column">
            <h5 class="text-light mb-1">Standard</h5>
            <small style="color:rgba(230,238,248,0.7);">Paling banyak dipilih</small>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 200.000</div>
              <div style="color:rgba(230,238,248,0.7);">per lowongan</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang 60 hari</li>
              <li class="mb-2">• 1 lowongan aktif</li>
            </ul>

            <div class="mt-auto">
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Pasang Loker
              </a>
            </div>
          </div>
        </div>
      </div>

      {{-- PREMIUM --}}
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0" style="background:rgba(255,255,255,0.03);">
          <div class="card-body d-flex flex-column">
            <h5 class="text-light mb-1">Premium</h5>
            <small style="color:rgba(230,238,248,0.7);">Durasi terpanjang</small>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 300.000</div>
              <div style="color:rgba(230,238,248,0.7);">per lowongan</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang 90 hari</li>
              <li class="mb-2">• 1 lowongan aktif</li>
            </ul>

            <div class="mt-auto">
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Pasang Loker
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

{{-- AI PROCESS --}}
<section id="ai-process" class="py-5">
  <div class="container">
    <div class="text-center mb-4">
      <h3 class="text-light mb-2">Bagaimana Fitur AI Bekerja</h3>
      <p class="mx-auto" style="max-width:720px;color:rgba(230,238,248,0.8);">
        Fitur AI di Teleworks dirancang untuk membantu HR memahami pelamar dengan lebih cepat,
        bukan menggantikan proses seleksi manusia.
      </p>
    </div>

    <div class="row g-4">
      <div class="col-12 col-md-6">
        <div class="p-4 rounded" style="background:rgba(255,255,255,0.03);">
          <h5 class="text-light mb-2">1. Penyusunan Loker</h5>
          <p style="color:rgba(230,238,248,0.85);">
            AI membantu menyusun deskripsi lowongan berdasarkan informasi inti yang Anda isi,
            sehingga struktur loker lebih rapi dan konsisten.
          </p>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="p-4 rounded" style="background:rgba(255,255,255,0.03);">
          <h5 class="text-light mb-2">2. Analisis Pelamar</h5>
          <p style="color:rgba(230,238,248,0.85);">
            Setiap pelamar dianalisis kesesuaiannya dengan loker, lalu diberikan skor dan
            catatan ringkas untuk membantu proses screening awal.
          </p>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="p-4 rounded" style="background:rgba(255,255,255,0.03);">
          <h5 class="text-light mb-2">3. Ringkasan Kandidat</h5>
          <p style="color:rgba(230,238,248,0.85);">
            AI merangkum gambaran umum kualitas pelamar dan menyoroti kandidat paling relevan
            agar HR dapat fokus ke tahap interview.
          </p>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="p-4 rounded" style="background:rgba(255,255,255,0.03);">
          <h5 class="text-light mb-2">4. Keputusan Akhir</h5>
          <p style="color:rgba(230,238,248,0.85);">
            Semua keputusan rekrutmen tetap dilakukan oleh HR. AI tidak menerima atau
            menolak kandidat secara otomatis.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- FAQ --}}
<section id="faq" class="py-5">
  <div class="container">
    <h3 class="mb-3 text-center text-light">Pertanyaan yang Sering Diajukan</h3>

    <div class="accordion" id="faqAccordion">

      {{-- FAQ Payment --}}
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#faqPay">
            Bagaimana proses pembayaran?
          </button>
        </h2>
        <div id="faqPay" class="accordion-collapse collapse">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Pembayaran diproses melalui Midtrans. Setelah pembayaran berhasil,
            loker akan aktif sesuai durasi paket yang dipilih.
          </div>
        </div>
      </div>

      {{-- FAQ AI --}}
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#faqAi">
            Apakah AI menentukan kandidat diterima atau ditolak?
          </button>
        </h2>
        <div id="faqAi" class="accordion-collapse collapse">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Tidak. AI hanya memberikan analisis dan ringkasan sebagai bahan pertimbangan.
            Keputusan rekrutmen sepenuhnya berada di tangan HR.
          </div>
        </div>
      </div>

      {{-- FAQ Data --}}
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#faqData">
            Apakah data kandidat aman?
          </button>
        </h2>
        <div id="faqData" class="accordion-collapse collapse">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Data kandidat hanya digunakan untuk proses rekrutmen di platform Teleworks
            dan tidak dibagikan ke pihak lain tanpa izin.
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

@endsection

