<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relasi ke profil job seeker (jika ada).
     */
    public function profile()
    {
        return $this->hasOne(\App\Models\Profile::class);
    }

    /**
     * Relasi ke resume yang di-upload user.
     */
    public function resumes()
    {
        return $this->hasMany(\App\Models\Resume::class);
    }

    /**
     * Relasi ke bookmark lowongan.
     */
    public function bookmarks()
    {
        return $this->hasMany(\App\Models\Bookmark::class);
    }

    /**
     * Relasi ke aplikasi lamaran (job applications).
     */
    public function applications()
    {
        return $this->hasMany(\App\Models\JobApplication::class);
    }

    /**
     * Relasi many-to-many ke perusahaan melalui tabel pivot company_user.
     */
    public function companies()
    {
        return $this->belongsToMany(\App\Models\Company::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Relasi single company utama via kolom users.company_id.
     * Dipakai di Employer\CompanyController, dll.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }
}

