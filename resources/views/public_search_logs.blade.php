@extends('layouts.app')

@section('title', 'Telusuri pencarian terbaru')

@section('content')
@php
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;

// Ambil semua log
$logs = \App\Models\SearchLog::orderByDesc('created_at')->get();

// --- KATA KUNCI ---
$keywords = $logs->pluck('q')
    ->map(fn($v) => trim((string)$v))
    ->filter()
    ->unique()
    ->values()
    ->all();

// --- LOKASI ---
$locations = $logs->pluck('params')
    ->map(function($p){
        if (!$p) return null;
        $arr = json_decode($p, true);
        return trim($arr['lokasi'] ?? '');
    })
    ->filter()
    ->unique()
    ->values()
    ->all();

// --- KOMBINASI (q + lokasi) ---
$combos = [];
foreach ($logs as $log) {
    $q  = trim((string)($log->q ?? ''));
    $arr = $log->params ? json_decode($log->params, true) : [];
    $lok = trim((string)($arr['lokasi'] ?? ''));

    if ($q !== '' && $lok !== '') {
        $combos[] = "{$q} | {$lok}";
    }
}
$combos = collect($combos)->unique()->values()->all();

// Jadi 4 kolom data
$col1 = $keywords;
$col2 = $locations;
$col3 = $combos;
$col4 = []; // bisa diisi kombinasi lain jika dibutuhkan


// Flatten semua kolom agar dapat dipagination 100 item
$all = array_merge(
    array_map(fn($v)=>['type'=>'q','value'=>$v], $col1),
    array_map(fn($v)=>['type'=>'lokasi','value'=>$v], $col2),
    array_map(fn($v)=>['type'=>'combo','value'=>$v], $col3)
);

$perPage = 100;
$currentPage = max(1, (int) request()->query('page', 1));
$total = count($all);
$offset = ($currentPage - 1) * $perPage;

$pageItems = array_slice($all, $offset, $perPage);

$paginator = new LengthAwarePaginator($pageItems, $total, $perPage, $currentPage, [
    'path' => URL::current(),
    'query' => request()->query(),
]);

// Bagi 4 kolom untuk tampilan
$chunks = array_chunk($pageItems, ceil(count($pageItems)/4));
@endphp


<div class="mb-4">
  <h1 class="h4 text-light">Telusuri pencarian terbaru</h1>
  <div style="color:#ddd;">Kata kunci, lokasi, dan kombinasi pencarian pengguna.</div>
</div>

<div class="card p-3 bg-dark border-secondary">
  <div class="row">

    @for($i=0; $i<4; $i++)
      <div class="col-12 col-md-3">
        <ul class="list-unstyled">
          @if(isset($chunks[$i]))
            @foreach($chunks[$i] as $item)
              @php
                $type = $item['type'];
                $value = $item['value'];

                // build link
                if ($type === 'q') {
                    $url = url('/cari/'.Str::slug($value,'-'));
                } elseif ($type === 'lokasi') {
                    $url = url('/cari/lokasi/'.Str::slug($value,'-'));
                } else { // combo "q | lokasi"
                    [$q,$lok] = array_map('trim', explode('|',$value));
                    $url = url('/cari/'.Str::slug($q,'-').'/'.Str::slug($lok,'-'));
                }
              @endphp

              <li class="mb-2">
                <a href="{{ $url }}" class="text-light text-decoration-none">
                  {{ $value }}
                </a>
              </li>
            @endforeach
          @endif
        </ul>
      </div>
    @endfor

  </div>

  <div class="mt-3 text-end">
    {{ $paginator->links('pagination::bootstrap-5') }}
  </div>
</div>

@endsection

@push('styles')
<style>
  a { color:#e6eef8; }
  a:hover { color:#fff; text-decoration:underline; }
</style>
@endpush

