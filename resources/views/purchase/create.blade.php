@extends('layouts.app')

@section('title','Beli Paket — Teleworks')

@section('content')
<div class="py-5">
  <div class="container">
    <h3 class="text-light mb-3">Beli Paket</h3>

    {{-- Flash messages --}}
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form id="purchaseForm" action="{{ route('purchase.store') }}" method="post" novalidate>
      @csrf

      <div class="row">
        <!-- LEFT -->
        <div class="col-md-8">
          <label class="form-label text-light">Pilih Paket</label>

          <div class="row g-3">

            @foreach($packages as $p)
              @php
                $prePackageSlug = request()->query('package');
                $prePackageId = request()->query('package_id');
                $isSelected = old('package_id') == $p->id
                    || ($prePackageId && intval($prePackageId) === $p->id)
                    || ($prePackageSlug && $prePackageSlug === $p->slug);
              @endphp

              <div class="col-12 col-md-6">
                <label class="card h-100 p-3 package-card"
                       style="cursor:pointer; 
                       background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); 
                       border:1px solid rgba(255,255,255,0.05);">

                  <!-- HEADER -->
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-bold text-light">{{ $p->name }}</div>
                      <!-- FIXED COLOR -->
                      <small style="color:rgba(255,255,255,0.88);">
                        {{ $p->duration_days }} hari
                      </small>
                    </div>
                    <div class="text-end">
                      <div class="h5 fw-bold text-light">
                        Rp {{ number_format($p->price,0,',','.') }}
                      </div>
                    </div>
                  </div>

                  <!-- FEATURES (FIXED COLOR) -->
                  <div class="mt-2" style="color:rgba(255,255,255,0.88);">
                    @if(is_array($p->features))
                      <ul class="mb-0">
                        @foreach($p->features as $k => $v)
                          <li>
                            {{ ucfirst(str_replace('_',' ', $k)) }}:
                            {{ is_array($v) ? json_encode($v) : $v }}
                          </li>
                        @endforeach
                      </ul>
                    @else
                      <small style="color:rgba(255,255,255,0.78);">
                        Durasi: {{ $p->duration_days }} hari
                      </small>
                    @endif
                  </div>

                  <!-- RADIO -->
                  <div class="form-check mt-3">
                    <input class="form-check-input package-radio" type="radio"
                           name="package_id" id="pkg_{{ $p->id }}" value="{{ $p->id }}"
                           {{ $isSelected ? 'checked' : '' }}>
                    <label class="form-check-label text-light" for="pkg_{{ $p->id }}">
                      Pilih {{ $p->name }}
                    </label>
                  </div>

                </label>
              </div>

            @endforeach

          </div>
        </div>

        <!-- RIGHT -->
        <div class="col-md-4">
          <label class="form-label text-light">Job (opsional)</label>
          <input type="text" name="job_id" class="form-control"
                 placeholder="Masukkan job id (opsional)" value="{{ old('job_id') }}">

          <div class="mt-4">
            <button id="purchaseBtn" type="submit" class="btn btn-primary w-100">
              <span id="purchaseBtnText">Lanjut ke Pembayaran (Midtrans)</span>
              <span id="purchaseSpinner"
                    class="spinner-border spinner-border-sm ms-2 d-none"
                    role="status" aria-hidden="true"></span>
            </button>
          </div>

          <div class="mt-3"
               style="color:rgba(255,255,255,0.75); font-size:.9rem;">
            Pembayaran diproses melalui Midtrans. Setelah klik, Anda akan diarahkan
            ke halaman pembayaran Midtrans. Midtrans akan mengirim notifikasi
            ke server kami setelah transaksi selesai.
          </div>
        </div>

      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

  const form = document.getElementById('purchaseForm');
  const btn = document.getElementById('purchaseBtn');
  const spinner = document.getElementById('purchaseSpinner');
  const btnText = document.getElementById('purchaseBtnText');

  form.addEventListener('submit', function (e) {
    const selected = document.querySelector('input[name="package_id"]:checked');
    if (!selected) {
      e.preventDefault();
      alert('Silakan pilih paket terlebih dahulu.');
      return;
    }

    if (btn.dataset.submitted === '1') {
      e.preventDefault();
      return;
    }
    btn.dataset.submitted = '1';
    btn.classList.add('disabled');
    spinner.classList.remove('d-none');
    btnText.textContent = 'Menghubungkan ke Midtrans...';
  });

  // Make whole card clickable
  document.querySelectorAll('.package-card').forEach(function(card){
    card.addEventListener('click', function(){
      const radio = card.querySelector('input.package-radio');
      if (radio) radio.checked = true;
    });
  });

});
</script>
@endpush

