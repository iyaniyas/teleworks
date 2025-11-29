<?php
namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $profile = auth()->user()->profile;
        return view('seeker.profile.edit', compact('profile'));
    }

    public function update(Request $r)
    {
        $r->validate([
            'headline'=>'nullable|string|max:255',
            'location'=>'nullable|string|max:255',
            'summary'=>'nullable|string',
            'skills'=>'nullable|string',
            'resume'=>'nullable|file|mimes:pdf,doc,docx|max:5120',
            'picture'=>'nullable|image|max:2048'
        ]);

        $user = auth()->user();

        $profileData = [
            'headline' => $r->headline,
            'location' => $r->location,
            'summary' => $r->summary,
            'skills' => $r->skills ? array_values(array_filter(array_map('trim', explode(',', $r->skills)))) : null
        ];

        $profile = $user->profile ?: $user->profile()->create([]);
        $profile->update($profileData);

        if ($r->hasFile('picture')) {
            $path = $r->file('picture')->store("profiles/{$user->id}", 'public');
            $profile->picture_path = $path;
            $profile->save();
        }

        if ($r->hasFile('resume')) {
            $path = $r->file('resume')->store("resumes/{$user->id}", 'public');
            // set existing resumes inactive if you want
            \App\Models\Resume::where('user_id', $user->id)->update(['is_active' => false]);
            \App\Models\Resume::create([
                'user_id' => $user->id,
                'title' => 'CV '.$user->name,
                'file_path' => $path,
                'is_active' => true
            ]);
        }

        return back()->with('success','Profil diperbarui.');
    }
}

