<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileFormation extends Model
{
    use HasFactory;

    protected $fillable = [
        "diplome",
        "etablissement",
        "date_debut",
        "date_fin",
        "mention",
        "slug",
        "is_deleted",
        "profile_id",
    ];

    public function profile(){

        return $this->belongsTo(Profile::class);
    }
}
