<?php

namespace App\Http\Controllers;

use App\Models\SecteurActivite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SecteurActiviteController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Secteur d'activite"},
     *      summary="Liste des secteurs d'activité",
     *      description="Retourne la liste des secteurs d'activité",
     *      path="/api/secteurs-activite",
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
        $data = SecteurActivite::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun secteur d\'activité trouvé'], 404);
        }

        return response()->json(['message' => "Secteurs d'activité récupérés", 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Secteur d'activite"},
     *     description="Crée un nouveau secteur d\'activité et retourne le secteur d\'activité créé",
     *     path="/api/secteurs-activite",
     *     summary="Création d'un secteur d\'activité",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Technologie de l'information (IT)"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="secteur d\'activité créé avec succès"),
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
            'description' => 'required|string|max:10000',
            'label' => 'required|string|unique:secteur_activites',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = SecteurActivite::create([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);


        return response()->json(['message' => 'Secteur d\'activité créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Secteur d'activite"},
     *      summary="Récupère un secteur d\'activité par son slug",
     *      description="Retourne un secteur d\'activité",
     *      path="/api/secteurs-activite/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du secteur d\'activité à récupérer",
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
        $data = SecteurActivite::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->first();

        if (!$data) {
            return response()->json(['message' => 'secteur d\'activité non trouvé'], 404);
        }

        return response()->json(['message' => 'secteur d\'activité trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Secteur d'activite"},
     *     description="Modifie un secteur d\'activité et retourne le secteur d\'activité modifié",
     *     path="/api/secteurs-activite/{slug}",
     *     summary="Modification d'un secteur d\'activité",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Technologie de l'information (IT)"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du secteur d\'activité à modifié",
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
     *             @OA\Property(property="message", type="string", example="secteur d\'activité modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="secteur d\'activité non trouvé"),
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
            'label' => 'required|string|',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = SecteurActivite::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'secteur d\'activité non trouvé'], 404);
        }

        $data->update([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
        ]);


        return response()->json(['message' => 'secteur d\'activité modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Secteur d'activite"},
     *      summary="Supprime un secteur d\'activité par son slug",
     *      description="Retourne le secteur d\'activité supprimé",
     *      path="/api/secteurs-activite/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="secteur d\'activité supprimé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="secteur d\'activité non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du secteur d\'activité à supprimer",
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

        $data = SecteurActivite::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'secteur d\'activité non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'secteur d\'activité supprimé avec succès',"data" => $data]);
    }
}
