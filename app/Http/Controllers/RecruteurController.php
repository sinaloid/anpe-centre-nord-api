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

class RecruteurController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Recruteur"},
     *      summary="Liste des offres",
     *      description="Retourne la liste des offres",
     *      path="/api/recruteur-offres",
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
            "candidature_id" => null,
            "type" => "OFFRE"
        ])->with("offre")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune offre trouvée'], 404);
        }

        return response()->json(['message' => 'Offres récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *     tags={"Recruteur"},
     *      summary="Récupération d'une offre par son slug",
     *      description="Retourne une offre",
     *      path="/api/recruteur-offres/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de l'offre du recruteur à récupérer",
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
            "slug"=> $slug, "is_deleted" => false,
            "user_id" => Auth::user()->id,
            "candidature_id" => null,
            "type" => "OFFRE"
        ])->with('offre')->first();

        if (!$data) {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }

        return response()->json(['message' => 'Offre trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Recruteur"},
     *      summary="Liste des candidatures du recruteur",
     *      description="Retourne la liste des candidatures du recruteur",
     *      path="/api/recruteur-offres-candidatures",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *     @OA\PathItem (
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function getAllCadidature()
    {
        $userId = Auth::user()->id;

        $offres = UserOffreCandidature::where([
            "is_deleted" => false,
            "user_id" => $userId,
            "candidature_id" => null,
            "type" => "OFFRE"
        ])->with('offre', 'candidature')->get();

        // Récupérer toutes les candidatures associées aux offres récupérées
        $offreIds = $offres->pluck('offre_id');

        $candidatures = UserOffreCandidature::whereIn('offre_id', $offreIds)
            ->where([
                'is_deleted' => false,
                'type' => 'CANDIDATURE'
            ])->whereNotNull('candidature_id')
            ->with('offre', 'candidature')
            ->get();


        // Filtrer les candidatures avec une offre_id présente dans les offres
        $data = $candidatures->filter(function ($candidature) use ($offreIds) {
            return $offreIds->contains($candidature->offre_id);
        })->values();

        /*$data = [];
        foreach($offres as $item){
            //var_dump($item);
            $candidatures = UserOffreCandidature::where([
                "is_deleted" => false,
                "offre_id" => $item->offre_id,
                "type" => "CANDIDATURE"
            ])->whereNotNull('candidature_id')->with("offre","candidature")->get();

           if(count($candidatures) !== 0){
            $data = array_merge($data, $candidatures);
           }
        }*/

        /*if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune offre trouvée'], 404);
        }*/

        return response()->json(['message' => 'Offres récupérées', 'data' => $candidatures], 200);
    }


    /**@OA\Post(
     *    tags={"Recruteur"},
     *     description="Crée une nouvelle offre et retourne l'offre créée",
     *     path="/api/recruteur-offres",
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
    /**@OA\Put(
     *    tags={"Recruteur"},
     *     description="Modifie une offre et retourne l'offre modifiée",
     *     path="/api/recruteur-offres/{slug}",
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
     *     tags={"Recruteur"},
     *      summary="Suppression d'une offre par son slug",
     *      description="Retourne l'offre supprimée",
     *      path="/api/recruteur-offres/{slug}",
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
