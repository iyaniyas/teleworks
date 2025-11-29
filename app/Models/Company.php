<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['owner_id','name','slug','domain','logo_path','description','is_verified','is_suspended'];

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'company_user')->withPivot('role')->withTimestamps();
    }

    public function jobs()
    {
        return $this->hasMany(\App\Models\Job::class);
    }

    public function company()
	{
    return $this->belongsTo(\App\Models\Company::class, 'company_id');
	}

}

