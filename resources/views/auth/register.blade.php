@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card bg-dark text-light">
      <div class="card-body">
        <h3 class="mb-1">Buat akun baru</h3>
        <p class="muted-light mb-3">Pilih peranmu lalu isi detail. Pilih "Perusahaan" jika kamu ingin memposting lowongan.</p>

        <form method="POST" action="{{ route('register') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label text-light">Nama Lengkap</label>
            <input name="name" class="form-control" value="{{ old('name') }}" required autofocus>
            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="mb-3">
            <label class="form-label text-light">Email</label>
            <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
          </div>

          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label text-light">Password</label>
              <input name="password" type="password" class="form-control" required>
            </div>
            <div class="col">
              <label class="form-label text-light">Konfirmasi Password</label>
              <input name="password_confirmation" type="password" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-light">Daftar sebagai</label>

            <div class="d-grid gap-2">
              <div class="btn-group" role="radiogroup" aria-label="Pilihan peran">
                <button type="button" class="btn btn-outline-light role-select active" data-value="job_seeker">Pencari Kerja</button>
                <button type="button" class="btn btn-outline-light role-select" data-value="company">Perusahaan / Rekruter</button>
              </div>
              <input type="hidden" name="role" id="role_input" value="{{ old('role','job_seeker') }}">
            </div>
          </div>

          <div class="d-grid">
            <button class="btn btn-primary btn-lg">Daftar</button>
          </div>

          <div class="text-center mt-3 muted-light">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-accent">Masuk</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function(){
    const btns = document.querySelectorAll('.role-select');
    const input = document.getElementById('role_input');
    btns.forEach(b => {
      b.addEventListener('click', () => {
        btns.forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        input.value = b.getAttribute('data-value');
      });
    });

    // set initial state based on hidden input
    const initial = input.value || 'job_seeker';
    btns.forEach(b => {
      if (b.getAttribute('data-value') === initial) b.classList.add('active');
    });
  });
</script>
@endsection

