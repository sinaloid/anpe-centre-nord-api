<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offre extends Model
{
    use HasFactory;

    protected $fillable = [
        "label",
        "type",
        "entreprise",
        "type_contrat",
        "type_contrat_id",
        "niveau_etude",
        "niveau_etude_id",
        "niveau_experience",
        "niveau_experience_id",
        "salaire",
        "nombre_de_poste",
        'date_debut',
        "date_limite",
        "region",
        "region_id",
        "ville",
        "ville_id",
        "longitude",
        "latitude",
        "pieces_jointes",
        "etat",
        "description",
        "slug",
        "is_deleted",
    ];
}
