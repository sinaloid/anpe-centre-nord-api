<?php

namespace App\Http\Controllers;

use App\Models\Preference;
use App\Models\Offre;
use App\Models\User;
use App\Models\UserOffrepréférence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PreferenceController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Préférences utilisateur"},
     *      summary="Liste des préférences",
     *      description="Retourne la liste des préférences",
     *      path="/api/preferences",
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
        $data = Preference::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune préférence trouvée'], 404);
        }

        return response()->json(['message' => 'préférences récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Préférences utilisateur"},
     *     description="Crée une nouvelle préférence et retourne la préférence créée",
     *     path="/api/preferences",
     *     summary="Création d'une préférence",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="type", type="string", example="OFFRES, VILLE, TYPE_CONTRAT"),
     *             @OA\Property(property="type_slug", type="string", example="slug du type"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="préférence créée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'type_slug' => 'required|string|max:10|unique:preferences',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = Preference::create([
            'type' => $request->input('type'),
            'type_slug' => $request->input('type_slug'),
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'préférence créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Préférences utilisateur"},
     *      summary="Récupère une préférence par son slug",
     *      description="Retourne une préférence",
     *      path="/api/preferences/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la préférence à récupérer",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function show($slug)
    {
        $data = Preference::where(["slug"=> $slug, "is_deleted" => false])->first();

        if (!$data) {
            return response()->json(['message' => 'préférence non trouvée'], 404);
        }

        return response()->json(['message' => 'préférence trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Préférences utilisateur"},
     *     description="Modifie une préférence et retourne la offre préférence",
     *     path="/api/preferences/{slug}",
     *     summary="Modification d'une préférence",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="type", type="string", example="OFFRES, VILLE, TYPE_CONTRAT"),
     *             @OA\Property(property="type_slug", type="string", example="slug du type"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la préférence à modifiée",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="préférence modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="préférence non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function update(Request $request, $slug)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'type_slug' => 'required|string|max:10',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = Preference::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'préférence non trouvée'], 404);
        }

        $data->update([
            'type' => $request->input('type'),
            'type_slug' => $request->input('type_slug'),
        ]);

        return response()->json(['message' => 'préférence modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Préférences utilisateur"},
     *      summary="Supprime une préférence par son slug",
     *      description="Retourne l'préférence supprimée",
     *      path="/api/preferences/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="préférence supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="préférence non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la préférence à supprimer",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function destroy($slug)
    {

        $data = Preference::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'préférence non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'préférence supprimée avec succès',"data" => $data]);
    }
}
