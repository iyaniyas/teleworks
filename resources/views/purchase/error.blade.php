@extends('layouts.app')

@section('title', 'Pembayaran Gagal — Teleworks')

@section('content')
<div class="container py-5 text-center text-light">

    <h2 class="mb-4">Pembayaran Gagal</h2>

    @isset($status)
        <p class="mb-3">
            Status transaksi dari Midtrans:  
            <strong class="text-danger">{{ $status }}</strong>
        </p>
    @endisset

    @isset($midtrans)
        <div class="text-start bg-dark p-3 rounded mx-auto" style="max-width:600px; font-size:.9rem;">
            <pre class="text-light" style="white-space:pre-wrap;">{{ json_encode($midtrans, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endisset

    <p class="mt-4" style="color:rgba(255,255,255,0.75);">
        Terjadi kesalahan dalam proses pembayaran.<br>
        Silakan coba lagi atau gunakan metode pembayaran lainnya.
    </p>

    <div class="mt-4">
        <a href="{{ route('purchase.create') }}" class="btn btn-outline-light me-3">Coba Lagi</a>
        <a href="{{ url('employer/dashboard') }}" class="btn btn-primary mt-3">
    Kembali ke Dashboard
</a>

    </div>

</div>
@endsection

