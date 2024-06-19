<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory;

    protected $fillable = [
        "type",
        "slug",
        "is_deleted",
        "user_id",
        "post_id",
        "commentaire_id",
        "reponse_id",
    ];

    public function post(){

        return $this->belongsTo(Post::class);
    }

    public function commentaire(){

        return $this->belongsTo(Commentaire::class);
    }

    public function reponse(){

        return $this->belongsTo(Reponse::class);
    }
}
