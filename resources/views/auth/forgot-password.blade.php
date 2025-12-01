@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card bg-dark text-light">
      <div class="card-body">
        <h3 class="mb-1">Lupa Password</h3>
        <p class="muted-light mb-3">Masukkan email terdaftar. Kami akan mengirim link untuk mereset password.</p>

        {{-- status (link terkirim) --}}
        @if (session('status'))
          <div class="alert alert-success" role="alert">
            {{ session('status') }}
          </div>
        @endif

        {{-- error umum --}}
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0 small">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
          @csrf

          <div class="mb-3">
            <label for="email" class="form-label text-light">Email</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="d-grid">
            <button class="btn btn-primary btn-lg">Kirim Link Reset Password</button>
          </div>

          <div class="text-center mt-3 muted-light">
            Ingat password? <a href="{{ route('login') }}" class="text-accent">Masuk</a>
          </div>
        </form>
      </div>
    </div>

    <div class="text-center mt-3 small muted-light">
      Belum punya akun? <a href="{{ route('register') }}" class="text-accent">Daftar</a>
    </div>
  </div>
</div>
@endsection

