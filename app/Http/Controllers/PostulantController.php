<?php

namespace App\Http\Controllers;

use App\Models\UserOffreCandidature;
use App\Models\User;
use App\Models\Offre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PostulantController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Postulants"},
     *      summary="Liste des candidatures",
     *      description="Retourne la liste des candidatures",
     *      path="/api/postulant-candidatures",
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
        $data = UserOffreCandidature::where([
            "is_deleted" => false,
            "user_id" => Auth::user()->id,
            "type" => "CANDIDATURE"
        ])->whereNotNull('candidature_id')->with("offre","candidature")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune candidature trouvée'], 404);
        }

        return response()->json(['message' => 'Candidatures récupérées', 'data' => $data], 200);
    }

    /**@OA\Post(
     *    tags={"Postulants"},
     *     description="Crée une nouvelle offre et retourne l'offre créée",
     *     path="/api/postulant-candidatures",
     *     summary="Création d'une offre",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recruteur","offre"},
     *             @OA\Property(property="recruteur", type="string", example="slug du recruteur"),
     *             @OA\Property(property="offre", type="string", example="slug de l'offre"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Offre créée avec succès"),
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
            'recruteur' => 'required|string|max:10',
            'offre' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $recruteur = User::where(["slug" => $request->recruteur,"is_deleted" => false])->first();
        $offre = Offre::where(["slug" => $request->offre,"is_deleted" => false])->first();

        if(!$recruteur){
            return response()->json(['message' => 'Recruteur non trouvé'], 404);
        }

        if(!$offre){
            return response()->json(['message' => 'Offre non trouvé'], 404);
        }

        $is_exit = UserOffreCandidature::where([
            "user_id" => $recruteur->id,
            "offre_id" => $offre->id,
            "is_deleted" => false
        ])->first();

        if($is_exit){
            return response()->json(['message' => 'Offre existe déjà', 'data' => $is_exit], 200);
        }

        $data = UserOffreCandidature::create([
            'user_id' => $recruteur->id,
            'offre_id' => $offre->id,
            'type' => "OFFRE",
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Offre créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *     tags={"Postulants"},
     *      summary="Récupération d'une candidature par son slug",
     *      description="Retourne une candidature",
     *      path="/api/postulant-candidatures/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la candidature du postulant à récupérer",
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
        $data = UserOffreCandidature::where([
            "slug"=> $slug,
            "is_deleted" => false,
            "user_id" => Auth::user()->id,
            "type" => "CANDIDATURE"
        ])->whereNotNull('candidature_id')->with("offre","candidature")->get();

        if (!$data) {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }

        return response()->json(['message' => 'Offre trouvée', 'data' => $data], 200);
    }

    /**@OA\Put(
     *    tags={"Postulants"},
     *     description="Modifie une offre et retourne l'offre modifiée",
     *     path="/api/postulant-candidatures/{slug}",
     *     summary="Modification d'une offre",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recruteur","offre"},
     *             @OA\Property(property="recruteur", type="string", example="slug du recruteur"),
     *             @OA\Property(property="offre", type="string", example="slug de l'offre"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de l'offre du recruteur à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Offre modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Offre non trouvée"),
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
            'recruteur' => 'required|string|max:10',
            'offre' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $recruteur = User::where(["slug" => $request->recruteur,"is_deleted" => false])->first();
        $offre = Offre::where(["slug" => $request->offre,"is_deleted" => false])->first();

        if(!$recruteur){
            return response()->json(['message' => 'Recruteur non trouvé'], 404);
        }

        if(!$offre){
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }


        $data = UserOffreCandidature::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }

        $data->update([
            'user_id' => $recruteur->id,
            'offre_id' => $offre->id,
        ]);

        return response()->json(['message' => 'Offre modifié avec succès', 'data' => $data], 200);

    }

    /**@OA\Delete(
     *     tags={"Postulants"},
     *      summary="Suppression d'une offre par son slug",
     *      description="Retourne l'offre supprimée",
     *      path="/api/postulant-candidatures/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Offre supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Offre non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de l'offre du recruteur à supprimer",
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

        $data = UserOffreCandidature::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Offre supprimée avec succès',"data" => $data]);
    }
}
