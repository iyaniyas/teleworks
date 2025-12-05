@extends('layouts.app')

@section('content')
<div class="container py-5 text-center text-light">
  <h2>Hasil Pembayaran</h2>

  @if(isset($success))
    <div class="alert alert-success">
      {{ $success }}
    </div>

  @elseif(isset($error))
    <div class="alert alert-warning">
      {{ $error }}
    </div>

  @elseif(isset($info))
    <div class="alert alert-info">
      {{ $info }}
      <p class="mt-3">
        Jika Anda yakin sudah membayar, tunggu beberapa menit. Sistem akan memperbarui status secara otomatis.
      </p>
    </div>

  @else
    <div class="alert alert-info">
      Status pembayaran belum dapat dipastikan saat ini.
    </div>
    <p class="mt-3">
      Jika Anda yakin sudah membayar, cek kembali di dashboard atau tunggu notifikasi berikutnya.
    </p>
  @endif

  <a href="{{ url('employer/dashboard') }}" class="btn btn-primary mt-3">
    Kembali ke Dashboard
  </a>
</div>
@endsection

