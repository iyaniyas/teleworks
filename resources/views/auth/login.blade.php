@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card bg-dark text-light">
      <div class="card-body">
        <h3 class="mb-1">Selamat datang kembali</h3>
        <p class="muted-light mb-3">Masuk untuk melanjutkan — temukan pekerjaan remote, atau kelola lowongan perusahaanmu.</p>

        @if(session('status'))
          <div class="alert alert-info">{{ session('status') }}</div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label text-light">Email</label>
            <input name="email" type="email" class="form-control" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label text-light">Password</label>
            <input id="password" name="password" type="password" class="form-control" required>
            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
              <label class="form-check-label muted-light" for="remember">Ingat saya</label>
            </div>

            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="text-accent small">Lupa password?</a>
            @endif
          </div>

          <div class="d-grid">
            <button class="btn btn-primary btn-lg">Masuk</button>
          </div>

          <div class="text-center mt-3 muted-light">
            Belum punya akun? <a href="{{ route('register') }}" class="text-accent">Daftar</a>
          </div>
	  <input type="hidden" name="intended" value="{{ request('intended') }}">
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

