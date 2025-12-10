<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bookmark;
use App\Models\Job;

class SavedController extends Controller
{
    /**
     * Tampilkan daftar loker yang disimpan user
     */
    public function index()
    {
        $saved = Bookmark::with('job')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(12);

        return view('seeker.saved', compact('saved'));
    }

    /**
     * Simpan / hapus bookmark
     * Route: POST /jobs/{id}/bookmark
     */
    public function toggle(Request $request, $id)
    {
        // pastikan job ada
        $job = Job::findOrFail($id);

        $userId = auth()->id();

        // cek apakah sudah tersimpan
        $bookmark = Bookmark::where('user_id', $userId)
                            ->where('job_id', $job->id)
                            ->first();

        // kalau sudah ada = hapus
        if ($bookmark) {
            $bookmark->delete();

            return redirect()->back()->with('success', 'Loker dihapus dari daftar simpan.');
        }

        // kalau belum = simpan
        Bookmark::create([
            'user_id' => $userId,
            'job_id'  => $job->id
        ]);

        return redirect()->back()->with('success', 'Loker berhasil disimpan.');
    }
}

