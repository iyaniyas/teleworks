<?php
namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SavedController extends Controller
{
    public function index()
    {
        $saved = \App\Models\Bookmark::with('job')->where('user_id', auth()->id())->paginate(12);
        return view('seeker.saved', compact('saved'));
    }

    public function toggle($jobId)
    {
        $userId = auth()->id();
        $exists = \App\Models\Bookmark::where('job_id',$jobId)->where('user_id',$userId)->first();
        if($exists) {
            $exists->delete();
            return back()->with('success','Dihapus dari saved');
        }
        \App\Models\Bookmark::create(['job_id'=>$jobId,'user_id'=>$userId]);
        return back()->with('success','Disimpan');
    }
}

