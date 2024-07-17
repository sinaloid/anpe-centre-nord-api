<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOffreCandidature extends Model
{
    use HasFactory;

    protected $fillable = [
        "type",
        "slug",
        "is_deleted",
        "user_id",
        "offre_id",
        "candidature_id",
    ];


    public function offre(){
        return $this->belongsTo(Offre::class);
    }

    public function candidature(){
        return $this->belongsTo(Candidature::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }



}
