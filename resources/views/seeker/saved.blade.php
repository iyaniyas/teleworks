@extends('layouts.app')

@section('content')
<h3 class="text-light">Lowongan Tersimpan</h3>
<p class="muted-light">Lowongan yang kamu simpan untuk dilamar nanti.</p>

<div class="mt-3 list-group">
  @foreach($saved as $item)
    <div class="list-group-item list-group-item-dark d-flex justify-content-between align-items-center">
      <div>
        <a href="{{ route('jobs.show', $item->job->id) }}" class="h6 text-light">{{ $item->job->title }}</a>
        <div class="small muted-light">{{ $item->job->hiring_organization ?? $item->job->company }}</div>
      </div>
      <form method="POST" action="{{ route('jobs.bookmark', $item->job->id) }}">
        @csrf
        <button class="btn btn-sm btn-outline-light">Hapus</button>
      </form>
    </div>
  @endforeach
</div>
@endsection

