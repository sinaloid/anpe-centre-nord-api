<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RessourceCandidature extends Model
{
    use HasFactory;

    protected $fillable = [
        "original_name",
        "name",
        "url",
        "slug",
        "is_deleted",
        "candidature_id",
    ];
}
