@extends('layouts.app')

@section('title','Instruksi Pembayaran — Teleworks')

@section('content')
<div class="py-5">
  <div class="container">
    <h3 class="text-light">Instruksi Pembayaran (Midtrans)</h3>

    <p style="color:rgba(230,238,248,0.85);">
      Klik tombol di bawah untuk melanjutkan ke halaman pembayaran Midtrans.
    </p>

    @if(!empty($response['redirect_url']))
      <a href="{{ $response['redirect_url'] }}" class="btn btn-primary">Lanjut ke Pembayaran</a>
    @elseif(!empty($snapToken))
      <button id="paySnap" class="btn btn-primary">Bayar (Snap Popup)</button>
      <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
      <script>
        document.getElementById('paySnap').addEventListener('click', function(){
          snap.pay('{{ $snapToken }}', {
            onSuccess: function(result){ console.log(result); location.href = "{{ route('pricing') }}"; },
            onPending: function(result){ console.log(result); alert('Pembayaran menunggu konfirmasi'); },
            onError: function(result){ console.log(result); alert('Terjadi kesalahan pembayaran'); }
          });
        });
      </script>
    @else
      <pre>{{ json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
    @endif

  </div>
</div>
@endsection

