@extends('layouts.app')

@section('content')
<style>
  :root{
    --bg: #071029;
    --card: #0f1724;
    --muted: #9fb0c8;
    --text: #e6eef8;
    --accent: #4f46e5;
    --accent-2: #06b6d4;
    --radius: 12px;
    --glass: rgba(255,255,255,0.02);
  }

  body { background: linear-gradient(180deg, var(--bg) 0%, #041024 100%); color:var(--text); }

  .wrap {
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 16px;
  }

  .card {
    width:100%;
    max-width:720px;
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border-radius:var(--radius);
    padding:28px;
    box-shadow: 0 10px 30px rgba(2,6,23,0.7);
  }

  .brand {
    display:flex; align-items:center; gap:12px; margin-bottom:8px;
  }
  .brand .logo { width:40px;height:40px;border-radius:8px;background:linear-gradient(90deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700; }

  h1 { margin:0 0 6px; font-size:22px; color:var(--text); }
  p.lead { margin:0 0 18px; color:var(--muted); }

  label.field-label { display:block; margin-bottom:6px; color:var(--text); font-weight:600; }
  input.form-control { width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.04); background:var(--glass); color:var(--text); outline:none; }
  input.form-control:focus { border-color:var(--accent); box-shadow: 0 8px 22px rgba(79,70,229,0.08); }

  .invalid-feedback { color:#f87171; margin-top:6px; font-size:13px; }

  /* role simple stacked */
  .role-stack { display:flex; flex-direction:column; gap:12px; margin-top:10px; }
  .role-btn {
    display:flex; align-items:center; gap:12px;
    padding:14px 16px; border-radius:10px; cursor:pointer;
    border:1px solid rgba(255,255,255,0.04);
    background:transparent; transition: all .12s;
    color:var(--text);
  }
  .role-btn .left { width:44px; height:44px; border-radius:8px; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.03); flex-shrink:0; }
  .role-btn .title { font-weight:700; font-size:16px; }
  .role-btn .desc { font-size:13px; color:var(--muted); }
  .role-btn:hover { transform: translateY(-3px); border-color: rgba(79,70,229,0.18); box-shadow: 0 8px 24px rgba(2,6,23,0.6); }
  .role-btn.active { background: linear-gradient(90deg,var(--accent),#7c3aed); color:white; border-color:var(--accent); }
  .role-btn.active .desc { color: rgba(255,255,255,0.9); }

  .actions { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-top:18px; }
  .btn-primary { background: linear-gradient(90deg, var(--accent), #7c3aed); color:white; border:none; padding:10px 18px; border-radius:10px; font-weight:700; }
  .info { color:var(--muted); font-size:13px; }

  @media(max-width:640px){
    .card{ padding:18px; }
  }
</style>

<div class="wrap">
  <div class="card" role="form" aria-labelledby="registerHeading">
    <div class="brand">
      <div class="logo">TW</div>
      <div>
        <div style="font-weight:700; color:var(--text)">teleworks.id</div>
        <div style="font-size:13px; color:var(--muted)">Platform kerja remote & hybrid</div>
      </div>
    </div>

    <h1 id="registerHeading">Buat akun baru</h1>
    <p class="lead">Pilih peranmu lalu isi detail. Pilih "Perusahaan" jika kamu ingin memposting lowongan.</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
      @csrf

      <div class="field">
        <label class="field-label">Nama Lengkap</label>
        <input name="name" class="form-control" value="{{ old('name') }}" required autofocus>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="field">
        <label class="field-label">Email</label>
        <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="field" style="display:flex; gap:10px;">
        <div style="flex:1">
          <label class="field-label">Password</label>
          <input name="password" type="password" class="form-control" required>
          @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div style="flex:1">
          <label class="field-label">Konfirmasi Password</label>
          <input name="password_confirmation" type="password" class="form-control" required>
        </div>
      </div>

      <div style="margin-top:10px;">
        <label class="field-label">Daftar sebagai</label>

        <div class="role-stack" role="radiogroup" aria-label="Pilihan peran">
          <div tabindex="0" class="role-btn {{ old('role','job_seeker')==='job_seeker' ? 'active' : '' }}" data-value="job_seeker" role="radio" aria-checked="{{ old('role','job_seeker')==='job_seeker' ? 'true' : 'false' }}">
            <div class="left" aria-hidden="true">
              <!-- user svg -->
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path><path d="M4 20a8 8 0 0116 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </div>
            <div>
              <div class="title">Pencari Kerja</div>
              <div class="desc">Cari lowongan, unggah CV, dan lamar.</div>
            </div>
          </div>

          <div tabindex="0" class="role-btn {{ old('role')==='company' ? 'active' : '' }}" data-value="company" role="radio" aria-checked="{{ old('role')==='company' ? 'true' : 'false' }}">
            <div class="left" aria-hidden="true">
              <!-- briefcase -->
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="7" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.6"></rect><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </div>
            <div>
              <div class="title">Perusahaan / Rekruter</div>
              <div class="desc">Posting lowongan & kelola pelamar.</div>
            </div>
          </div>
        </div>

        <input type="hidden" name="role" id="role_input" value="{{ old('role','job_seeker') }}">
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="actions">
        <div class="info">Dengan mendaftar, Anda setuju pada <a href="{{ route('privacy') }}" style="color:var(--accent)">Ketentuan & Privasi</a>.</div>
        <button type="submit" class="btn-primary">Daftar</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function(){
    const cards = document.querySelectorAll('.role-btn');
    const input = document.getElementById('role_input');

    function setActive(card){
      cards.forEach(c => {
        c.classList.remove('active');
        c.setAttribute('aria-checked','false');
      });
      card.classList.add('active');
      card.setAttribute('aria-checked','true');
      input.value = card.getAttribute('data-value');
    }

    cards.forEach(card => {
      card.addEventListener('click', () => setActive(card));
      card.addEventListener('keydown', (e) => {
        if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setActive(card); }
        if(e.key === 'ArrowDown' || e.key === 'ArrowRight'){ e.preventDefault(); const next = card.nextElementSibling || cards[0]; next.focus(); }
        if(e.key === 'ArrowUp' || e.key === 'ArrowLeft'){ e.preventDefault(); const prev = card.previousElementSibling || cards[cards.length-1]; prev.focus(); }
      });
    });

    // initial
    const active = document.querySelector('.role-btn.active');
    if(active) setActive(active);
  })();
</script>
@endsection

