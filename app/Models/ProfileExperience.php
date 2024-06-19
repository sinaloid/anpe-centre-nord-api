<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileExperience extends Model
{
    use HasFactory;

    protected $fillable =[
        "titre",
        "entreprise",
        "date_debut",
        "date_fin",
        "description",
        "slug",
        "is_deleted",
        "profile_id"
    ];


    public function profile(){

        return $this->belongsTo(Profile::class);
    }


}
