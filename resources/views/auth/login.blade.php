@extends('layouts.app')

@section('content')
<style>
  :root{
    --bg: #071029;
    --card: #0f1724;
    --muted: #9fb0c8;
    --text: #e6eef8;
    --accent: #4f46e5;
    --radius: 12px;
    --glass: rgba(255,255,255,0.02);
  }

  body { background: linear-gradient(180deg, var(--bg) 0%, #041024 100%); color:var(--text); }

  .wrap {
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:36px 16px;
  }

  .card {
    width:100%;
    max-width:640px;
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border-radius:var(--radius);
    padding:28px;
    box-shadow: 0 10px 30px rgba(2,6,23,0.7);
  }

  .brand { display:flex; gap:12px; align-items:center; margin-bottom:6px; }
  .brand .logo { width:40px; height:40px; border-radius:8px; background:linear-gradient(90deg,var(--accent),#7c3aed); display:flex; align-items:center; justify-content:center; color:white; font-weight:700; }

  h1 { margin:0 0 6px; font-size:22px; color:var(--text); }
  p.lead { margin:0 0 18px; color:var(--muted); }

  label.field-label { display:block; margin-bottom:6px; color:var(--text); font-weight:600; }
  input.form-control { width:100%; padding:12px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.04); background:var(--glass); color:var(--text); outline:none; }
  input.form-control:focus { border-color:var(--accent); box-shadow: 0 8px 22px rgba(79,70,229,0.08); }

  .invalid-feedback { color:#f87171; margin-top:6px; font-size:13px; }

  .actions { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-top:18px; }
  .btn-primary { background: linear-gradient(90deg, var(--accent), #7c3aed); color:white; border:none; padding:10px 18px; border-radius:10px; font-weight:700; }
  .btn-outline { background:transparent; border:1px solid rgba(255,255,255,0.06); color:var(--text); padding:8px 14px; border-radius:10px; }

  .meta { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:8px; color:var(--muted); font-size:14px; }
  .small-link { color:var(--accent); text-decoration:none; }

  .show-pass {
    position:relative;
  }
  .show-pass button.toggle {
    position:absolute; right:8px; top:50%; transform:translateY(-50%);
    background:transparent; border:none; color:var(--muted); cursor:pointer; padding:6px; border-radius:6px;
  }

  @media(max-width:640px){
    .card{ padding:18px; }
  }
</style>

<div class="wrap">
  <div class="card" role="main" aria-labelledby="loginHeading">
    <div class="brand">
      <div class="logo">TW</div>
      <div>
        <div style="font-weight:700; color:var(--text)">teleworks.id</div>
        <div style="font-size:13px; color:var(--muted)">Masuk ke akunmu</div>
      </div>
    </div>

    <h1 id="loginHeading">Selamat datang kembali</h1>
    <p class="lead">Masuk untuk melanjutkan — temukan pekerjaan remote, atau kelola lowongan perusahaanmu.</p>

    @if(session('status'))
      <div style="background:rgba(255,255,255,0.02);padding:12px;border-radius:8px;margin-bottom:12px;color:var(--accent);">
        {{ session('status') }}
      </div>
    @endif

    @if(session('error'))
      <div style="background:rgba(255,0,0,0.06);padding:12px;border-radius:8px;margin-bottom:12px;color:#ffb4b4;">
        {{ session('error') }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
      @csrf

      <div class="field">
        <label class="field-label">Email</label>
        <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="email">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="field show-pass" style="margin-top:12px;">
        <label class="field-label">Password</label>
        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
        <button type="button" class="toggle" aria-label="Toggle password visibility" onclick="togglePassword()">
          <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="display:inline;">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a19.83 19.83 0 0 1 5.06-7.94"></path>
            <path d="M1 1l22 22"></path>
          </svg>
        </button>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="meta">
        <div style="display:flex;gap:10px;align-items:center;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <span style="color:var(--muted);font-size:14px;">Ingat saya</span>
          </label>
        </div>

        <div>
          @if (Route::has('password.request'))
            <a class="small-link" href="{{ route('password.request') }}">Lupa password?</a>
          @endif
        </div>
      </div>

      <div class="actions">
        <a href="{{ route('register') }}" class="btn btn-outline">Buat akun baru</a>
        <button type="submit" class="btn-primary">Masuk</button>
      </div>
    </form>
  </div>
</div>

<script>
  function togglePassword() {
    const pwd = document.getElementById('password');
    const eyeOpen = document.getElementById('eye-open');
    const eyeClosed = document.getElementById('eye-closed');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      eyeOpen.style.display = 'none';
      eyeClosed.style.display = 'inline';
    } else {
      pwd.type = 'password';
      eyeOpen.style.display = 'inline';
      eyeClosed.style.display = 'none';
    }
  }

  // accessibility: submit on enter in password (default) - keep default behavior
</script>
@endsection

