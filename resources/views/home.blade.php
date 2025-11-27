@extends('layouts.app')

@section('title', 'Teleworks.id – #1 Cari Kerja dari Rumah')
@section('meta_description', 'Cari kerja jarak jauh, langsung dari rumah. Tersedia pekerjaan WFH full-time & part-time dari perusahaan terpercaya. Mulai karier fleksibel Anda hari ini!')

@php
  // timestamp untuk home: Tampilkan bulan dan tahun (contoh: "Nov 2025")
  $timestamp = now()->timezone(config('app.timezone','Asia/Jakarta'))->format('M Y');
@endphp

@section('content')
<div class="container my-4">

  {{-- HERO --}}
  <div class="text-center mb-4">
    <h1 class="text-3xl md:text-4xl font-extrabold leading-tight" style="color:#f3f7ff;">
      Teleworks.id
    </h1>
    <p class="mt-3 max-w-3xl mx-auto text-sm md:text-base tw-muted" style="color:#cfdcec;">
      Cari kerja jarak jauh, langsung dari rumah.
    </p>
  </div>

  {{-- SEARCH --}}
  <form action="{{ route('search.index') }}" method="get" class="card p-3 mb-4 tw-card" style="background:#181a20;">
    <div class="row g-2 align-items-center">
      <div class="col-md-5">
        <input name="q" type="search" value="{{ request('q') }}"
               placeholder="Tulis Kata Kunci"
               class="form-control form-control-dark" />
      </div>

      <div class="col-md-5">
        <input name="lokasi" type="text" value="{{ request('lokasi') }}"
               placeholder="Tulis Lokasi tekan Enter"
               class="form-control form-control-dark" />
      </div>

      <div class="col-md-2">
        <button type="submit" class="btn btn-outline-light w-100">Cari</button>
      </div>
    </div>
  </form>

  {{-- PREPARE DATA --}}
  @php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\DB;

    /**
     * Ambil 20 kata kunci terbaru dari tabel search_logs (kolom `q`).
     * Logika:
     *  - Ambil record terbaru (urut desc berdasarkan id - jika ada created_at, bisa diganti)
     *  - Ambil nilai q, lower-case + trim
     *  - Buang nilai kosong, ambil unique, ambil 20 teratas
     *
     * Catatan: idealnya query ini dilakukan di Controller, tapi saya taruh di view sesuai permintaan.
     */
    $terms = collect(
      DB::table('search_logs')
        ->whereNotNull('q')
        ->where('q', '!=', '')
        ->orderByDesc('id') // ubah ke orderByDesc('created_at') jika kolom created_at tersedia dan lebih akurat
        ->limit(200) // ambil lebih banyak dulu lalu unique di PHP untuk menjaga ordering terbaru per kata unik
        ->pluck('q')
    )
    ->map(function($t) {
      // normalisasi: trim & lowercase
      return trim(mb_strtolower($t));
    })
    ->filter()       // buang kosong
    ->unique()       // unique
    ->values()
    ->slice(0, 20);  // ambil 20 kata kunci terbaru

    // jika tidak ada kata kunci dari db, fallback ke daftar default (opsional)
    if ($terms->isEmpty()) {
      $terms = collect([
        'admin','admin online','cs','customer service','admin chat',
        'data entry','freelance','part time','full time','kerja dari rumah',
        'remote job indonesia','content writer','copywriter','designer','digital marketing',
        'social media','virtual assistant','frontend','backend','fullstack'
      ])->slice(0,20)->values();
    }

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
      <h2 class="text-sm font-semibold mb-3" style="color:#cfe6ff;">Kata Kunci Terbaru</h2>

      <div class="grid grid-cols-2 gap-x-6">
        <ul class="list-links space-y-2">
          @foreach($kwA as $term)
            @php
              $kataSlug = Str::slug($term, '-');
              $link = url('/cari/' . $kataSlug);
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ $term }}</a></li>
          @endforeach
        </ul>

        <ul class="list-links space-y-2">
          @foreach($kwB as $term)
            @php
              $kataSlug = Str::slug($term, '-');
              $link = url('/cari/' . $kataSlug);
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ $term }}</a></li>
          @endforeach
        </ul>
      </div>
    </div>

    {{-- RIGHT CARD: Kota Pencarian (2 kolom, 10 per kolom) --}}
    <div class="tw-card p-4">
      <h2 class="text-sm font-semibold mb-3" style="color:#cfe6ff;">Loker di Kota Besar Indonesia</h2>

      <div class="grid grid-cols-2 gap-x-6">
        <ul class="list-links space-y-2">
          @foreach($ctA as $city)
            @php
              $citySlug = Str::slug($city, '-');
              $link = url('/cari/lokasi/' . $citySlug);
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ strtolower($city) }}</a></li>
          @endforeach
        </ul>

        <ul class="list-links space-y-2">
          @foreach($ctB as $city)
            @php
              $citySlug = Str::slug($city, '-');
              $link = url('/cari/lokasi/' . $citySlug);
            @endphp
            <li><a href="{{ $link }}" class="text-light">{{ strtolower($city) }}</a></li>
          @endforeach
        </ul>
      </div>
    </div>

  </div>

  {{-- LINK KE SEMUA LOKER (DITAMBAHKAN) --}}
  <div class="text-center mt-6 mb-4">
    <a href="{{ route('search.index') }}" class="btn btn-outline-light px-4 py-2" role="button" aria-label="Lihat Semua Lowongan">
      Lihat Semua Lowongan
    </a>
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

