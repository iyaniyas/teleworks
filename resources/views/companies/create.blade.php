@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Buat Perusahaan</h1>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <form action="{{ route('companies.store') }}" method="POST">
    @csrf
    <div class="mb-3">
      <label>Nama Perusahaan</label>
      <input type="text" name="name" class="form-control" value="{{ old('name') }}">
      @error('name')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label>Domain (opsional)</label>
      <input type="text" name="domain" class="form-control" value="{{ old('domain') }}">
      @error('domain')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label>Deskripsi (opsional)</label>
      <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
    </div>

    <button class="btn btn-primary">Buat Perusahaan</button>
  </form>
</div>
@endsection

