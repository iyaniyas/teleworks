<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','headline','location','summary','skills','picture_path','visibility'];

    protected $casts = [
        'skills' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

