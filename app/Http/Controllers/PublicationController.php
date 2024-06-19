<?php

namespace App\Http\Controllers;

use App\Models\publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublicationController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Publications d'un utilisateur dans le forum"},
     *      summary="Liste des publications de l'utilisateur",
     *      description="Retourne la liste des publications de l'utilisateur connecté",
     *      path="/api/publications",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *     @OA\PathItem (
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index()
    {
        $data = Publication::where([
            "is_deleted" => false,
            "user_id" => Auth::user()->id,
            "commentaire_id" => null,
            "reponse_id" => null,
        ])->with("post")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune publication trouvée'], 404);
        }

        return response()->json(['message' => 'Publications récupérées', 'data' => $data], 200);
    }



}
