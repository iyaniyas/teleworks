@extends('layouts.app')

@section('content')
<div class="card bg-dark text-light mx-auto" style="max-width:980px;">
  <div class="card-body">
    <h3 class="mb-3 text-light">Edit Profil</h3>

    <form method="POST" action="{{ route('seeker.profile.update') }}" enctype="multipart/form-data">
      @csrf
      @method('PATCH')

      <div class="mb-3">
        <label class="form-label text-light">Headline</label>
        <input name="headline" class="form-control" value="{{ old('headline', auth()->user()->profile->headline ?? '') }}">
      </div>

      <div class="mb-3">
        <label class="form-label text-light">Lokasi</label>
        <input name="location" class="form-control" value="{{ old('location', auth()->user()->profile->location ?? '') }}">
      </div>

      <div class="mb-3">
        <label class="form-label text-light">Ringkasan</label>
        <textarea name="summary" rows="5" class="form-control">{{ old('summary', auth()->user()->profile->summary ?? '') }}</textarea>
      </div>

      <div class="mb-3">
        <label class="form-label text-light">Skills (pisahkan dengan koma)</label>
        <input name="skills" class="form-control" value="{{ old('skills', auth()->user()->profile && auth()->user()->profile->skills ? implode(',', json_decode(auth()->user()->profile->skills)) : '') }}">
      </div>

      <div class="mb-3">
        <label class="form-label text-light">Unggah CV (PDF)</label>
        <input type="file" name="resume" accept=".pdf,.doc,.docx" class="form-control">
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary">Simpan Profil</button>
        <a href="{{ route('seeker.dashboard') }}" class="btn btn-outline-light">Batal</a>
      </div>
    </form>
  </div>
</div>
@endsection

