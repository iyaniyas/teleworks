<?php
namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = \App\Models\JobApplication::with('job')->where('user_id', auth()->id())->latest()->paginate(12);
        return view('seeker.applications.index', compact('applications'));
    }

    public function withdraw($id)
    {
        $app = \App\Models\JobApplication::where('id',$id)->where('user_id', auth()->id())->firstOrFail();
        // safer: delete the application
        $app->delete();
        return back()->with('success','Lamaran berhasil ditarik.');
    }
}

