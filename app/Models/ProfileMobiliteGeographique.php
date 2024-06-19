<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileMobiliteGeographique extends Model
{
    use HasFactory;

    protected $fillable = [
        "ville",
        "slug",
        "is_deleted",
        "profile_id",
    ];

    public function profile(){
        return $this->belongsTo(Profile::class);
    }

}
