<?php

namespace App\Http\Controllers;

use App\Models\NiveauExperience;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NiveauExperienceController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Niveaux d'expérience"},
     *      summary="Liste des niveaux d'expérience",
     *      description="Retourne la liste des niveaux d'expérience",
     *      path="/api/niveaux-experience",
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
        $data = NiveauExperience::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun niveau d\'expérience trouvé'], 404);
        }

        return response()->json(['message' => "niveaux d'expérience récupérés", 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Niveaux d'expérience"},
     *     description="Crée un nouveau niveau d\'expérience et retourne le niveau d\'expérience créé",
     *     path="/api/niveaux-experience",
     *     summary="Création d'un niveau d\'expérience",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="1 ans"),
     *             @OA\Property(property="description", type="string", example="1 ans d'expérience")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'expérience créé avec succès"),
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
            'label' => 'required|string|unique:niveau_experiences',
            'description' => 'nullable|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = NiveauExperience::create([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'niveau d\'expérience créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Niveaux d'expérience"},
     *      summary="Récupère un niveau d\'expérience par son slug",
     *      description="Retourne un niveau d\'expérience",
     *      path="/api/niveaux-experience/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du niveau d\'expérience à récupérer",
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
        $data = NiveauExperience::where(["slug"=> $slug, "is_deleted" => false])->first();

        if (!$data) {
            return response()->json(['message' => 'niveau d\'expérience non trouvé'], 404);
        }

        return response()->json(['message' => 'niveau d\'expérience trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Niveaux d'expérience"},
     *     description="Modifie un niveau d\'expérience et retourne le niveau d\'expérience",
     *     path="/api/niveaux-experience/{slug}",
     *     summary="Modification d'un niveau d\'expérience",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="1 ans"),
     *             @OA\Property(property="description", type="string", example="1 ans expérience")
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du niveau d\'expérience à modifié",
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
     *             @OA\Property(property="message", type="string", example="niveau d\'expérience modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'expérience non trouvé"),
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
            'label' => 'required|string|',
            'description' => 'nullable|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = NiveauExperience::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'niveau d\'expérience non trouvé'], 404);
        }

        $data->update([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'niveau d\'expérience modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Niveaux d'expérience"},
     *      summary="Supprime un niveau d\'expérience par son slug",
     *      description="Retourne le niveau d\'expérience supprimé",
     *      path="/api/niveaux-experience/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'expérience supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'expérience non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du niveau d\'expérience à supprimer",
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

        $data = NiveauExperience::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'niveau d\'expérience non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'niveau d\'expérience supprimé avec succès',"data" => $data]);
    }
}
