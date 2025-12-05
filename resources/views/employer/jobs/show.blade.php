@extends('layouts.app')

@section('content')
<h1>{{ $job->title }}</h1>
<p>{{ $job->description }}</p>

<a class="btn btn-primary" href="{{ route('purchase.create', ['job_id' => $job->id]) }}">
    Beli Paket untuk Lowongan Ini
</a>
@endsection

