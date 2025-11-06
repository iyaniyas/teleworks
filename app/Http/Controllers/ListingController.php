<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index','show','search']);
    }

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

        // set expires_at jika diasumsikan eks: expires_in_days
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
        $this->authorize('update', $listing); // buat policy atau cek owner
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

    // (opsional) search method
    public function search(Request $request)
    {
        $q = $request->input('kata');
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

