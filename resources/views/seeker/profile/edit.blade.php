@extends('layouts.app')

@section('content')
<div class="container" style="padding:28px;">
  <div style="max-width:980px;margin:0 auto;background:rgba(255,255,255,0.02);padding:20px;border-radius:10px;">
    <h2 style="color:#e6eef8">Edit Profil</h2>

    <form method="POST" action="{{ route('seeker.profile.update') }}" enctype="multipart/form-data">
      @csrf
      @method('PATCH')

      <div style="display:flex;gap:14px;">
        <div style="flex:2">
          <div class="mb-3">
            <label class="field-label">Headline</label>
            <input name="headline" class="form-control" value="{{ old('headline', auth()->user()->profile->headline ?? '') }}">
          </div>

          <div class="mb-3">
            <label class="field-label">Lokasi</label>
            <input name="location" class="form-control" value="{{ old('location', auth()->user()->profile->location ?? '') }}">
          </div>

          <div class="mb-3">
            <label class="field-label">Ringkasan</label>
            <textarea name="summary" rows="5" class="form-control">{{ old('summary', auth()->user()->profile->summary ?? '') }}</textarea>
          </div>

          <div class="mb-3">
            <label class="field-label">Skills (pisahkan dengan koma)</label>
            <input name="skills" class="form-control" value="{{ old('skills', auth()->user()->profile && auth()->user()->profile->skills ? implode(',', json_decode(auth()->user()->profile->skills)) : '') }}">
          </div>

          <div class="mb-3">
            <label class="field-label">Unggah CV (PDF)</label>
            <input type="file" name="resume" accept=".pdf,.doc,.docx" class="form-control">
          </div>

          <div style="display:flex;gap:8px;margin-top:12px;">
            <button class="btn btn-primary">Simpan Profil</button>
            <a href="{{ route('seeker.dashboard') }}" class="btn btn-outline">Batal</a>
          </div>
        </div>

        <div style="flex:1">
          <div style="background:rgba(255,255,255,0.01);padding:12px;border-radius:8px;">
            <div style="width:100%;height:120px;border-radius:8px;background:rgba(255,255,255,0.03);display:flex;align-items:center;justify-content:center;color:#e6eef8;font-weight:700;font-size:28px;">
              {{ strtoupper(substr(auth()->user()->name,0,1)) }}
            </div>
            <p style="color:#9fb0c8;margin-top:10px">Foto profil & resume akan membantu perusahaan melihat profesionalitasmu.</p>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

