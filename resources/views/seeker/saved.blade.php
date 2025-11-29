@extends('layouts.app')

@section('content')
<div class="container" style="padding:28px;">
  <h2 style="color:#e6eef8">Lowongan Tersimpan</h2>
  <p style="color:#9fb0c8">Lowongan yang kamu simpan untuk dilamar nanti.</p>

  <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px;">
    @foreach($saved as $item)
      <div style="background:rgba(255,255,255,0.02);padding:12px;border-radius:10px;display:flex;justify-content:space-between;align-items:center">
        <div>
          <a href="{{ route('jobs.show', $item->job->id) }}" style="color:#e6eef8;font-weight:700">{{ $item->job->title }}</a>
          <div style="color:#9fb0c8;font-size:13px">{{ $item->job->hiring_organization ?? $item->job->company }}</div>
        </div>
        <form method="POST" action="{{ route('jobs.bookmark', $item->job->id) }}">
          @csrf
          <button class="btn btn-outline">Hapus</button>
        </form>
      </div>
    @endforeach
  </div>
</div>
@endsection

