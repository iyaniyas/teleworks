{{-- resources/views/pricing.blade.php --}}
@extends('layouts.app')

@section('title','Pasang Loker Fitur AI — Teleworks')
@section('meta_description','Biaya Pasang Loker dengan fitur AI. Pilih paket pemasangan job berbayar. Pembayaran diproses melalui Midtrans. Pilih paket dan lanjutkan ke pembayaran.') 

@section('content')
<div class="text-center py-5">
  <h1 class="display-5 fw-bold text-light">Pasang Loker dengan Fitur AI!</h1>
  <p class="lead" style="color:rgba(230,238,248,0.85);">
    Pasang loker berbayar dan tampilkan iklan Anda selama periode tertentu. Pembayaran diproses melalui <strong>Midtrans</strong>. Pilih paket yang sesuai kebutuhan perusahaan Anda lalu lanjutkan ke pembuatan loker.
  </p>
</div>

<section id="plans" class="py-5">
  <div class="container">
    <div class="row g-4 justify-content-center">

      <!-- Basic -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm"
             style="background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="card-title mb-1 text-light">Basic</h5>
                <small style="color:rgba(230,238,248,0.7);">Paket untuk percobaan</small>
              </div>
              <span class="badge bg-secondary">Paling ekonomis</span>
            </div>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 100.000</div>
              <div style="color:rgba(230,238,248,0.7);">/ per posting</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang selama 30 hari</li>
              <li class="mb-2">• Termasuk bantuan AI untuk menyusun deskripsi loker dasar (auto job description)</li>
            </ul>

            <div class="mt-auto">
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Buat Loker &amp; Pilih Paket
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Standard -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-lg"
             style="background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015)); border:1px solid rgba(255,255,255,0.03);">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="card-title mb-1 text-light">Standard</h5>
                <small style="color:rgba(230,238,248,0.7);">Pilihan seimbang</small>
              </div>
              <span class="badge bg-primary">Best value</span>
            </div>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 200.000</div>
              <div style="color:rgba(230,238,248,0.7);">/ per posting</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang selama 60 hari</li>
              <li class="mb-2">• Auto job description berbasis AI dengan struktur lebih lengkap dan profesional</li>
              <li class="mb-2">• Rekomendasi awal skor kecocokan kandidat dengan loker untuk membantu proses screening</li>
            </ul>

            <div class="mt-auto">
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Buat Loker &amp; Pilih Paket
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Premium -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm"
             style="background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="card-title mb-1 text-light">Premium</h5>
                <small style="color:rgba(230,238,248,0.7);">Paket durasi terpanjang</small>
              </div>
              <span class="badge bg-warning text-dark">Top</span>
            </div>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 300.000</div>
              <div style="color:rgba(230,238,248,0.7);">/ per posting</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang selama 90 hari</li>
              <li class="mb-2">• Auto job description canggih berbasis AI, siap pakai dan mudah disesuaikan</li>
              <li class="mb-2">• Skor kecocokan kandidat vs loker untuk memprioritaskan pelamar paling relevan</li>
              <li class="mb-2">• Mendukung proses screening yang lebih cepat dan konsisten</li>
            </ul>

            <div class="mt-auto">
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Buat Loker &amp; Pilih Paket
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="row mt-5">
      <div class="col-lg-8 mx-auto text-center">
        <p style="color:rgba(230,238,248,0.75);">
          Semua harga sudah termasuk biaya layanan. Pembayaran diproses melalui <strong>Midtrans</strong>. Setelah pembayaran sukses, loker akan aktif sesuai durasi paket.
        </p>
      </div>
    </div>
  </div>
</section>

{{-- Fitur AI --}}
<section id="ai-features" class="py-5">
  <div class="container">
    <div class="row justify-content-center mb-4">
      <div class="col-lg-8 text-center">
        <h3 class="text-light mb-3">Fitur AI untuk Mempercepat Rekrutmen</h3>
        <p style="color:rgba(230,238,248,0.8);">
          Teleworks dilengkapi fitur kecerdasan buatan yang membantu perusahaan membuat loker lebih cepat dan menilai kecocokan kandidat dengan lebih objektif.
        </p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="h-100 p-4 rounded-3"
             style="background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); border:1px solid rgba(255,255,255,0.06);">
          <h5 class="text-light mb-2">Auto Job Description</h5>
          <p class="mb-3" style="color:rgba(230,238,248,0.85);">
            Cukup isi informasi inti seperti posisi, level, lokasi, dan kebutuhan utama. Sistem AI akan menyusun deskripsi loker yang rapi, profesional, dan mudah dipahami kandidat.
          </p>
          <ul class="mb-0" style="color:rgba(230,238,248,0.85);">
            <li>Mengurangi waktu penulisan deskripsi loker</li>
            <li>Bahasa yang konsisten dan profesional</li>
            <li>Mudah disesuaikan sebelum dipublikasikan</li>
          </ul>
        </div>
      </div>

      <div class="col-md-6">
        <div class="h-100 p-4 rounded-3"
             style="background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); border:1px solid rgba(255,255,255,0.06);">
          <h5 class="text-light mb-2">Scoring Kandidat vs Loker</h5>
          <p class="mb-3" style="color:rgba(230,238,248,0.85);">
            Setiap lamaran dapat dinilai menggunakan AI berdasarkan kecocokan dengan persyaratan loker: pengalaman, keterampilan, dan informasi pada CV kandidat.
          </p>
          <ul class="mb-0" style="color:rgba(230,238,248,0.85);">
            <li>Skor kecocokan kandidat terhadap loker</li>
            <li>Membantu HR memprioritaskan kandidat yang paling relevan</li>
            <li>Mengurangi waktu screening manual awal</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>



<section id="faq" class="py-5">
  <div class="container">
    <h3 class="mb-3 text-center text-light">Pertanyaan yang sering diajukan</h3>

    <div class="accordion" id="faqAccordion">

      <!-- FAQ 1 -->
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header" id="faq1">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseFaq1">
            Bagaimana proses pembayaran?
          </button>
        </h2>
        <div id="collapseFaq1" class="accordion-collapse collapse" aria-labelledby="faq1">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Pembayaran diproses melalui Midtrans. Setelah memilih paket dan membuat loker, Anda akan diarahkan ke halaman pembayaran Midtrans. Midtrans juga akan mengirimkan notifikasi ke server kami (webhook) untuk mengkonfirmasi status pembayaran.
          </div>
        </div>
      </div>

      <!-- FAQ 2 -->
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header" id="faq2">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseFaq2">
            Apakah ada batasan untuk jumlah loker?
          </button>
        </h2>
        <div id="collapseFaq2" class="accordion-collapse collapse">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Setiap pembelian paket berlaku untuk satu posting loker. Untuk kebutuhan korporat atau volume besar, silakan hubungi tim kami untuk penawaran khusus.
          </div>
        </div>
      </div>

      <!-- FAQ 3 -->
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header" id="faq3">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseFaq3">
            Bagaimana cara melihat status loker berbayar saya?
          </button>
        </h2>
        <div id="collapseFaq3" class="accordion-collapse collapse">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Masuk ke dashboard perusahaan. Anda dapat melihat status pembayaran dan tanggal kadaluarsa tayang loker di daftar loker Anda.
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

@endsection

