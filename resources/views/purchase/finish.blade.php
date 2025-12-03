@extends('layouts.app')

@section('content')
<div class="container py-5 text-center text-light">
  <h2>Hasil Pembayaran</h2>

  @if(isset($status) && $status === 'paid')
    <div class="alert alert-success">Pembayaran terkonfirmasi. Lowongan akan segera aktif.</div>
  @elseif(isset($status) && $status === 'error')
    <div class="alert alert-warning">Kami gagal memeriksa status pembayaran saat ini. Mohon cek kembali nanti.</div>
  @else
    <div class="alert alert-info">Status transaksi: <strong>{{ $status ?? 'unknown' }}</strong></div>
    <p>Jika Anda yakin sudah membayar, tunggu notifikasi. Webhook Midtrans akan memperbarui status di sistem kami.</p>
  @endif

  <a href="{{ url('employer/dashboard') }}" class="btn btn-primary mt-3">
    Kembali ke Dashboard
</a>

</div>
@endsection

