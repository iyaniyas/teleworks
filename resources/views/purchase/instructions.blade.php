@extends('layouts.app')

@section('title', 'Instruksi Pembayaran — Teleworks')

@section('content')
<div class="py-5">
  <div class="container">
    <div class="card p-4" style="background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border:1px solid rgba(255,255,255,0.03);">
      <h3 class="mb-3 text-light">Instruksi Pembayaran</h3>

      <p style="color:rgba(230,238,248,0.85);">
        Terima kasih. Silakan lakukan pembayaran sesuai instruksi di bawah. Setelah Flip mengkonfirmasi pembayaran, sistem akan mengaktifkan paket untuk lowongan Anda.
      </p>

      <div class="row">
        <div class="col-md-6">
          <h5 class="text-light">Ringkasan Pembayaran</h5>
          <ul class="list-unstyled" style="color:rgba(230,238,248,0.9);">
            <li><strong>Package:</strong> {{ $package->name }}</li>
            <li><strong>Amount:</strong> Rp {{ number_format($payment->amount, 0, ',', '.') }}</li>
            <li><strong>External ID:</strong> {{ $payment->external_id }}</li>
            <li><strong>Status:</strong> {{ ucfirst($payment->status) }}</li>
          </ul>
        </div>

        <div class="col-md-6">
          <h5 class="text-light">Instruksi dari Flip</h5>
          @php
            $resp = $response;
            $data = is_array($resp) && isset($resp['data']) ? $resp['data'] : (is_array($resp) ? $resp : null);
          @endphp

          @if($data && isset($data['instructions']))
            <div style="color:rgba(230,238,248,0.9);">
              {!! nl2br(e(json_encode($data['instructions'], JSON_PRETTY_PRINT))) !!}
            </div>
          @else
            <pre style="color:rgba(230,238,248,0.85); background:transparent; border:none;">{{ json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
          @endif
        </div>
      </div>

      <div class="mt-4">
        <a href="{{ route('pricing') }}" class="btn btn-outline-light">Kembali ke Pricing</a>
      </div>
    </div>
  </div>
</div>
@endsection

