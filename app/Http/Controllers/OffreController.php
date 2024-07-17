<?php

namespace App\Http\Controllers;

use App\Models\Offre;
use App\Models\UserOffreCandidature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OffreController extends Controller
{
    public function __construct()
    {
        // Appliquer le middleware d'authentification à toutes les méthodes sauf celles spécifiées
        //$this->middleware('auth:api')->except(['index', 'show','offreByType']);
    }
    /**
     * @OA\Get(
     *      tags={"Offres"},
     *      summary="Liste des offres",
     *      description="Retourne la liste des offres",
     *      path="/api/offres",
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
        $data = Offre::where("is_deleted",false)->paginate(10);

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune offre trouvée'], 404);
        }

        return response()->json(['message' => 'Offres récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Offres"},
     *     description="Crée une nouvelle offre et retourne l'offre créée",
     *     path="/api/offres",
     *     summary="Création d'une offre",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Recrutement de 10 chauffeurs"),
     *             @OA\Property(property="type", type="string", example="EMPLOI,STAGE,FORMATION,PROJET"),
     *             @OA\Property(property="description", type="string", example="L'entreprise SmartShop recrute 10 chauffeurs")
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
            'label' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'entreprise' => 'nullable|string|max:255',
            'type_contrat' => 'nullable|string|max:255',
            'niveau_etude' => 'nullable|string|max:255',
            'niveau_experience' => 'nullable|string|max:255',
            'salaire' => 'nullable|string|max:255',
            'nombre_de_poste' => 'nullable|string|max:255',
            'date_debut' => 'nullable|string|max:255',
            'date_limite' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'peices_jointes' => 'nullable|string|max:255',
            'etat' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = Offre::create([
            'label' => $request->input('label'),
            'type' => $request->input('type'),
            'entreprise' => $request->input('entreprise'),
            'type_contrat' => $request->input('type_contrat'),
            'niveau_etude' => $request->input('niveau_etude'),
            'niveau_experience' => $request->input('niveau_experience'),
            'nombre_de_poste' => $request->input('nombre_de_poste'),
            'salaire' => $request->input('salaire'),
            'date_debut' => $request->input('date_debut'),
            'date_limite' => $request->input('date_limite'),
            'region' => $request->input('region'),
            'ville' => $request->input('ville'),
            'longitude' => $request->input('longitude'),
            'latitude' => $request->input('latitude'),
            'etat' => "EN_ATTENTE",
            'pieces_jointes' => $request->input('pieces_jointes'),
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);

        UserOffreCandidature::create([
            'user_id' => Auth::user()->id,
            'offre_id' => $data->id,
            'type' => "OFFRE",
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Offre créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Offres"},
     *      summary="Récupère une offre par son slug",
     *      description="Retourne une offre",
     *      path="/api/offres/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la offre à récupérer",
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
        $data = Offre::where(["slug"=> $slug, "is_deleted" => false])->first();

        if (!$data) {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }

        return response()->json(['message' => 'Offre trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Offres"},
     *     description="Modifie une offre et retourne la offre modifiée",
     *     path="/api/offres/{slug}",
     *     summary="Modification d'une offre",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Recrutement de 10 chauffeurs"),
     *             @OA\Property(property="type", type="string", example="EMPLOI,STAGE,FORMATION,PROJET"),
     *             @OA\Property(property="description", type="string", example="L'entreprise SmartShop recrute 10 chauffeurs")
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de l'offre à modifiée",
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
            'label' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'entreprise' => 'nullable|string|max:255',
            'type_contrat' => 'nullable|string|max:255',
            'niveau_etude' => 'nullable|string|max:255',
            'niveau_experience' => 'nullable|string|max:255',
            'salaire' => 'nullable|string|max:255',
            'nombre_de_poste' => 'nullable|string|max:255',
            'date_debut' => 'nullable|string|max:255',
            'date_limite' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'pieces_jointes' => 'nullable|string|max:255',
            'etat' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = Offre::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }

        $data->update([
            'label' => $request->input('label'),
            'type' => $request->input('type'),
            'entreprise' => $request->input('entreprise'),
            'type_contrat' => $request->input('type_contrat'),
            'niveau_etude' => $request->input('niveau_etude'),
            'niveau_experience' => $request->input('niveau_experience'),
            'nombre_de_poste' => $request->input('nombre_de_poste'),
            'salaire' => $request->input('salaire'),
            'date_debut' => $request->input('date_debut'),
            'date_limite' => $request->input('date_limite'),
            'region' => $request->input('region'),
            'ville' => $request->input('ville'),
            'longitude' => $request->input('longitude'),
            'latitude' => $request->input('latitude'),
            'etat' => $request->input('etat'),
            'pieces_jointes' => $request->input('pieces_jointes'),
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'Offre modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Offres"},
     *      summary="Supprime une offre par son slug",
     *      description="Retourne l'offre supprimée",
     *      path="/api/offres/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="'Offre supprimée avec succès"),
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
     *          description="slug de l'offre à supprimer",
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

        $data = Offre::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Offre supprimée avec succès',"data" => $data]);
    }

    /**
     * @OA\Get(
     *      tags={"Offres"},
     *      summary="Récupère la liste des offres en fonction d'un type",
     *      description="Retourne la liste des offres d'un type",
     *      path="/api/offres/type/{type}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="type",
     *          in="path",
     *          description="type des offres à récupérer",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     * )
     */
    public function offresByType($type)
    {
        $data = Offre::where(["type"=> $type, "is_deleted" => false])->paginate(10);

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune offre trouvée'], 404);
        }

        return response()->json(['message' => 'Offres trouvées', 'data' => $data], 200);
    }
}
