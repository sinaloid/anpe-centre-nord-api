<?php

namespace App\Http\Controllers;

use App\Models\Reponse;
use App\Models\Commentaire;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReponseController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Réponses des commentaires:"},
     *      summary="Liste des réponses",
     *      description="Retourne la liste des reponses",
     *      path="/api/reponses",
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
        $data = Reponse::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun réponse trouvée'], 404);
        }

        return response()->json(['message' => 'Réponse récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Réponses des commentaires:"},
     *     description="Crée une nouvelle réponse et retourne la réponse créée",
     *     path="/api/reponses",
     *     summary="Création d'une réponse",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="commentaire", type="string", example="Slug du commentaire"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem ipsum")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Réponse créée avec succès"),
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
            'commentaire' => 'required|string|max:10',
            'description' => 'required|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $commentaire = Commentaire::where(["slug" => $request->commentaire,"is_deleted" => false])->first();

        if(!$commentaire){
            return response()->json(['message' => 'Commentaire non trouvé'], 404);
        }

        $data = Reponse::create([
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);

        Publication::create([
            'user_id' => Auth::user()->id,
            'post_id' => $commentaire->publications->first()->post_id,
            'commentaire_id' => $commentaire->id,
            'reponse_id' => $data->id,
            'type' => "REPONSE",
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Réponse créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Réponses des commentaires:"},
     *      summary="Récupère une réponse par son slug",
     *      description="Retourne une réponse",
     *      path="/api/reponses/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la réponse à récupérer",
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
        $data = Reponse::where(["slug"=> $slug, "is_deleted" => false])->first();

        if (!$data) {
            return response()->json(['message' => 'Réponse non trouvée'], 404);
        }

        return response()->json(['message' => 'Réponse trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Réponses des commentaires:"},
     *     description="Modifie une réponse et retourne la réponse modifiée",
     *     path="/api/reponses/{slug}",
     *     summary="Modification d'une réponse",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem ipsum")
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la réponse à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Réponse modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Réponse non trouvée"),
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
            'description' => 'required|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = Reponse::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Réponse non trouvée'], 404);
        }

        $data->update([
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'Réponse modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Réponses des commentaires:"},
     *      summary="Supprime une réponse par son slug",
     *      description="Retourne la réponse supprimée",
     *      path="/api/reponses/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Réponse supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Réponse non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la réponse à supprimer",
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

        $data = Reponse::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Réponse non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Réponse supprimée avec succès',"data" => $data]);
    }
}
