@extends('layouts.app')

@section('title', 'Teleworks.id – #1 Lowongan Kerja WFH Part Time')
@section('meta_description', 'Temukan lowongan kerja remote terbaik di Teleworks.id! Tersedia pekerjaan WFH full-time & part-time dari perusahaan terpercaya. Mulai karier fleksibel Anda hari ini!')

@section('content')
<div class="container my-4">

  {{-- HERO --}}
  <div class="text-center mb-4">
    <h1 class="text-3xl md:text-4xl font-extrabold leading-tight" style="color:#f3f7ff;">
      Teleworks.id – #1 Lowongan Kerja WFH Part Time
    </h1>
    <p class="mt-3 max-w-3xl mx-auto text-sm md:text-base tw-muted" style="color:#cfdcec;">
      Temukan lowongan kerja remote terbaik di Teleworks.id! Tersedia pekerjaan WFH full-time & part-time dari perusahaan terpercaya.
    </p>
  </div>

  {{-- SEARCH --}}
  <form action="{{ route('search.index') }}" method="get" class="card p-3 mb-4 tw-card" style="background:#181a20;">
    <div class="row g-2 align-items-center">
      <div class="col-md-5">
        <input name="q" type="search" value="{{ request('q') }}"
               placeholder="Cari pekerjaan WFH (mis. admin wfh)"
               class="form-control form-control-dark" />
      </div>

      <div class="col-md-5">
        <input name="lokasi" type="text" value="{{ request('lokasi') }}"
               placeholder="Lokasi (mis. Jakarta)"
               class="form-control form-control-dark" />
      </div>

      <div class="col-md-2">
        <button type="submit" class="btn btn-outline-light w-100">Cari</button>
      </div>
    </div>
  </form>

  {{-- PREPARE DATA --}}
  @php
    // keywords
    $terms = collect([
      'admin wfh','admin online wfh','cs wfh','customer service wfh','admin chat wfh',
      'data entry wfh','freelance wfh','part time wfh','full time wfh','kerja dari rumah',
      'remote job indonesia','content writer wfh','copywriter wfh','designer wfh','digital marketing wfh',
      'social media wfh','virtual assistant wfh','frontend wfh','backend wfh','fullstack wfh'
    ])->slice(0,20)->values();

    // cities: ambil 20, bagi 2 kolom, 10 per kolom
    $cities = collect([
      'jakarta','surabaya','bandung','bekasi','depok','tangerang','semarang','medan','makassar','palembang',
      'denpasar','yogyakarta','malang','batam','balikpapan','bandar lampung','pekanbaru','banjarmasin','samarinda','padang'
    ])->slice(0,20)->values();

    // Keywords split 2 columns
    $kwHalf = ceil($terms->count() / 2);
    $kwA = $terms->slice(0, $kwHalf);
    $kwB = $terms->slice($kwHalf);

    // Cities split 10 - 10
    $ctA = $cities->slice(0,10);
    $ctB = $cities->slice(10,10);
  @endphp

  {{-- TWO CARDS --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- LEFT CARD: Kata Kunci --}}
    <div class="tw-card p-4">
      <h3 class="text-sm font-semibold mb-3" style="color:#cfe6ff;">Kata Kunci WFH</h3>

      <div class="grid grid-cols-2 gap-x-6">
        <ul class="list-links space-y-2">
          @foreach($kwA as $term)
            @php
              $encoded = str_replace(' ', '+', strtolower($term));
              $link = url('/cari') . '?q=' . $encoded . '&lokasi=';
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ $term }}</a></li>
          @endforeach
        </ul>

        <ul class="list-links space-y-2">
          @foreach($kwB as $term)
            @php
              $encoded = str_replace(' ', '+', strtolower($term));
              $link = url('/cari') . '?q=' . $encoded . '&lokasi=';
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ $term }}</a></li>
          @endforeach
        </ul>
      </div>
    </div>

    {{-- RIGHT CARD: Kota Pencarian (2 kolom, 10 per kolom) --}}
    <div class="tw-card p-4">
      <h3 class="text-sm font-semibold mb-3" style="color:#cfe6ff;">Kota Pencarian (WFH)</h3>

      <div class="grid grid-cols-2 gap-x-6">
        <ul class="list-links space-y-2">
          @foreach($ctA as $city)
            @php
              $cityParam = str_replace(' ', '+', strtolower($city));
              $link = url('/cari') . '?q=&lokasi=' . $cityParam;
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ strtolower($city) }}</a></li>
          @endforeach
        </ul>

        <ul class="list-links space-y-2">
          @foreach($ctB as $city)
            @php
              $cityParam = str_replace(' ', '+', strtolower($city));
              $link = url('/cari') . '?q=&lokasi=' . $cityParam;
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ strtolower($city) }}</a></li>
          @endforeach
        </ul>
      </div>
    </div>

  </div>

</div>

<style>
.tw-card {
  background:#181a20;
  border:1px solid #2a2d35;
  border-radius:.75rem;
  box-shadow:0 0 15px rgba(0,0,0,0.4);
}

.list-links {
  padding-left: 0;
  margin: 0;
  list-style: none;
}
.list-links li {
  position: relative;
  padding-left: 1.2rem;
  color: #e6eef8;
}
.list-links li::before {
  content: "•";
  position: absolute;
  left: 0;
  top: 0;
  color: #e6eef8;
  font-size: 1rem;
  line-height: 1;
}
.list-links a {
  color: inherit;
  text-decoration: none;
}
.list-links a:hover {
  color:#cfe6ff;
  text-decoration: underline;
}
</style>

@endsection

