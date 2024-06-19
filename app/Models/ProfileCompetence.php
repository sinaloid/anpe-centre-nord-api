<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileCompetence extends Model
{
    use HasFactory;

    protected $fillable = [
        "competence",
        "niveau",
        "slug",
        "is_deleted",
        "profile_id",
    ];

    public function profile(){

        return $this->belongsTo(Profile::class);
    }
}
