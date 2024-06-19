<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        "apropos",
        "email",
        "telephone",
        "ville",
        "adresse",
        "annee_experience",
        "niveau_etude",
        "slug",
        "is_deleted",
        "user_id",
    ];

    public function user(){

        return $this->belongsTo(User::class);
    }
}
