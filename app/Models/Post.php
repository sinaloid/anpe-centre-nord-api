<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        "titre",
        "type",
        "categorie",
        "long_description",
        "description",
        "slug",
        "is_deleted",
    ];

    public function publications(){

        return $this->hasMany(Publication::class);
    }
}
