<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Job;
use Illuminate\Http\Request;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    public function __construct()
    {
        // Biarkan akses publik ke home (homepage jobs), index (listings index), show dan search
        $this->middleware('auth')->except(['home','index','show','search']);
    }

    /**
     * Home page — menampilkan loker (paginated) + top searches (WFH) + top cities (statik)
     */
	public function home(Request $request)
	{	
    // Ambil loker (tetap dipanggil jika model Job ada, tapi kita fallback jika error)
    $jobs = collect();
    try {
        $jobs = Job::query()
            ->whereNotNull('date_posted')
            ->orderByDesc('date_posted')
            ->paginate(12);
    } catch (\Throwable $e) {
        $jobs = collect();
    }

    // 20 istilah WFH (spesifik)
    $topSearches = collect([
        'admin wfh',
        'admin online wfh',
        'cs wfh',
        'customer service wfh',
        'admin chat wfh',
        'data entry wfh',
        'freelance wfh',
        'part time wfh',
        'full time wfh',
        'kerja dari rumah',
        'remote job indonesia',
        'content writer wfh',
        'copywriter wfh',
        'designer wfh',
        'digital marketing wfh',
        'social media wfh',
        'virtual assistant wfh',
        'frontend wfh',
        'backend wfh',
        'fullstack wfh'
    ])->slice(0, 20)->values();

    // 30 kota besar (statik)
    $topCities = collect([
        'jakarta','surabaya','bandung','bekasi','depok','tangerang','semarang','medan','makassar','palembang',
        'denpasar','yogyakarta','malang','batam','balikpapan','bandar lampung','pekanbaru','banjarmasin','samarinda','padang',
        'manado','kupang','pontianak','mataram','jambi','cirebon','tasikmalaya','probolinggo','bengkulu','kediri'
    ])->slice(0, 30)->values();

    return view('home', compact('jobs','topSearches','topCities'));
	}

    /**
     * Index untuk Listing resource (mis. listing barang/jasa)
     */
    public function index()
    {
        $listings = Listing::where('is_active', true)->latest()->paginate(10);
        return view('listings.index', compact('listings'));
    }

    public function create()
    {
        return view('listings.create');
    }

    public function store(StoreListingRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // handle image jika ada
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('listings', 'public'); // storage/app/public/listings
            $data['image'] = $path;
        }

        // set expires_at jika ada input expires_in_days
        if ($request->filled('expires_in_days')) {
            $data['expires_at'] = now()->addDays($request->input('expires_in_days'));
        } else {
            // default 45 hari
            $data['expires_at'] = now()->addDays(45);
        }

        $listing = Listing::create($data);

        return redirect()->route('listings.show', $listing)->with('success','Listing dibuat.');
    }

    public function show(Listing $listing)
    {
        if (!$listing->is_active) abort(404);
        return view('listings.show', compact('listing'));
    }

    public function edit(Listing $listing)
    {
        $this->authorize('update', $listing); // pastikan policy ada atau cek owner
        return view('listings.edit', compact('listing'));
    }

    public function update(UpdateListingRequest $request, Listing $listing)
    {
        $this->authorize('update', $listing);
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // hapus file lama
            if ($listing->image) Storage::disk('public')->delete($listing->image);
            $path = $request->file('image')->store('listings', 'public');
            $data['image'] = $path;
        }

        if ($request->filled('expires_in_days')) {
            $data['expires_at'] = now()->addDays($request->input('expires_in_days'));
        }

        $listing->update($data);

        return redirect()->route('listings.show', $listing)->with('success','Listing diperbarui.');
    }

    public function destroy(Listing $listing)
    {
        $this->authorize('delete', $listing);
        if ($listing->image) Storage::disk('public')->delete($listing->image);
        $listing->delete();
        return redirect()->route('listings.index')->with('success','Listing dihapus.');
    }

    /**
     * (opsional) search method untuk Listing model
     */
    public function search(Request $request)
    {
        $q = $request->input('kata') ?? $request->input('q');
        $lokasi = $request->input('lokasi');

        $query = Listing::query()->where('is_active', true);

        if ($q) {
            $query->where(function($s) use ($q) {
                $s->where('title','like',"%{$q}%")
                  ->orWhere('description','like',"%{$q}%");
            });
        }
        if ($lokasi) {
            $query->where('location','like', "%{$lokasi}%");
        }

        $results = $query->latest()->paginate(12)->withQueryString();
        return view('listings.search', compact('results','q','lokasi'));
    }
}

