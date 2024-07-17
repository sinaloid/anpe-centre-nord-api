<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidature extends Model
{
    use HasFactory;
    protected $fillable = [
        "label",
        "etat",
        "description",
        "slug",
        "is_deleted",
    ];

    public function user_offre_candidature(){

        return $this->hasOne(UserOffreCandidature::class);
    }

    public function ressource_candidatures(){

        return $this->hasMany(RessourceCandidature::class);
    }
}
