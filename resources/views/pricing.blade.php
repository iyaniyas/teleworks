{{-- resources/views/pricing.blade.php --}}
@extends('layouts.app')

@section('title','Pricing — Teleworks')
@section('meta_description','Pilih paket pemasangan job berbayar. Pembayaran diproses melalui Midtrans. Pilih paket dan lanjutkan ke pembayaran.') 

@section('content')
<div class="text-center py-5">
  <h1 class="display-5 fw-bold text-light">Paket Pasang Lowongan</h1>
  <p class="lead" style="color:rgba(230,238,248,0.85);">
    Tingkatkan visibilitas lowongan Anda dengan paket berbayar kami. Pembayaran diproses melalui <strong>Midtrans</strong>. Pilih paket yang sesuai kebutuhan perusahaan Anda lalu lanjutkan ke pembayaran.
  </p>

  {{-- GANTI: tombol lama diganti dengan block CTA employer --}}
  <div class="pricing-page my-4">
    <h2 class="h4 text-light">Pilih Paket</h2>
    <p style="color:rgba(230,238,248,0.85);">Pilih paket di bawah untuk mem-publish lowongan Anda.</p>

    {{-- CTA: arahkan employer untuk membuat lowongan terlebih dahulu --}}
    <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary">
      Buat Lowongan & Pilih Paket
    </a>
  </div>
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
              <div style="color:rgba(230,238,248,0.7);">/ per posting (30 hari)</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang selama 30 hari</li>
              <li class="mb-2">• Tanpa prioritas</li>
              <li class="mb-2">• 1 lowongan per pembelian</li>
              <li class="mb-2">• Dukungan email</li>
            </ul>

            <div class="mt-auto">
              {{-- Tombol lama diganti: arahkan ke pembuatan job employer --}}
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Buat Lowongan & Pilih Paket
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
                <small style="color:rgba(230,238,248,0.7);">Pilihan populer</small>
              </div>
              <span class="badge bg-primary">Best value</span>
            </div>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 200.000</div>
              <div style="color:rgba(230,238,248,0.7);">/ per posting (60 hari)</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang selama 60 hari</li>
              <li class="mb-2">• Prioritas medium</li>
              <li class="mb-2">• 1 lowongan + boost</li>
              <li class="mb-2">• Dukungan chat & email</li>
            </ul>

            <div class="mt-auto">
              {{-- Tombol lama diganti --}}
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Buat Lowongan & Pilih Paket
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
                <small style="color:rgba(230,238,248,0.7);">Untuk perusahaan yang ingin maksimal</small>
              </div>
              <span class="badge bg-warning text-dark">Top</span>
            </div>

            <div class="my-4">
              <div class="h1 fw-bold text-light">Rp 300.000</div>
              <div style="color:rgba(230,238,248,0.7);">/ per posting (90 hari)</div>
            </div>

            <ul class="list-unstyled mb-4" style="color:rgba(230,238,248,0.9);">
              <li class="mb-2">• Tayang selama 90 hari</li>
              <li class="mb-2">• Prioritas tinggi</li>
              <li class="mb-2">• Highlight di listing</li>
              <li class="mb-2">• Dukungan prioritas + analytics</li>
            </ul>

            <div class="mt-auto">
              {{-- Tombol lama diganti --}}
              <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary w-100">
                Buat Lowongan & Pilih Paket
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="row mt-5">
      <div class="col-lg-8 mx-auto text-center">
        <p style="color:rgba(230,238,248,0.75);">
          Semua harga sudah termasuk biaya layanan. Pembayaran diproses melalui <strong>Midtrans</strong>. Setelah pembayaran sukses, lowongan akan aktif sesuai durasi paket. Anda akan diarahkan kembali ke Teleworks setelah proses pembayaran.
        </p>
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
            Pembayaran diproses melalui Midtrans. Setelah memilih paket, Anda akan diarahkan ke halaman pembayaran Midtrans (hosted/redirect atau popup). Selain redirect, Midtrans akan mengirimkan notifikasi ke server kami (webhook) untuk mengkonfirmasi status pembayaran.
          </div>
        </div>
      </div>

      <!-- FAQ 2 -->
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header" id="faq2">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseFaq2">
            Apakah ada batasan untuk jumlah lowongan?
          </button>
        </h2>
        <div id="collapseFaq2" class="accordion-collapse collapse">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Tidak ada batasan khusus — setiap pembelian paket berlaku untuk satu lowongan. Untuk penawaran korporat, silakan hubungi tim penjualan.
          </div>
        </div>
      </div>

      <!-- FAQ 3 -->
      <div class="accordion-item" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">
        <h2 class="accordion-header" id="faq3">
          <button class="accordion-button collapsed bg-transparent text-light" type="button"
                  data-bs-toggle="collapse" data-bs-target="#collapseFaq3">
            Bagaimana cara melihat status lowongan berbayar saya?
          </button>
        </h2>
        <div id="collapseFaq3" class="accordion-collapse collapse">
          <div class="accordion-body" style="color:rgba(230,238,248,0.85);">
            Masuk ke dashboard perusahaan. Anda akan melihat status <strong>is_paid</strong> dan tanggal kadaluarsa <strong>paid_until</strong> untuk setiap lowongan.
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

@endsection

