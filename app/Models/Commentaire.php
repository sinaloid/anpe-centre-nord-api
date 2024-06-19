<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commentaire extends Model
{
    use HasFactory;

    protected $fillable = [
        "description",
        "slug",
        "is_deleted",
    ];

    public function publications(){
        return $this->hasMany(Publication::class);
    }
}
