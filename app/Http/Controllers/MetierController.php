<?php

namespace App\Http\Controllers;

use App\Models\Metier;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MetierController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Metiers"},
     *      summary="Liste des metiers",
     *      description="Retourne la liste des metiers",
     *      path="/api/metiers",
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
        $data = Metier::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun metier trouvé'], 404);
        }

        return response()->json(['message' => 'Metiers récupérés', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Metiers"},
     *     description="Crée un nouveau metier et retourne le metier créé",
     *     path="/api/metiers",
     *     summary="Création d'un metier",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Dévellopeur FullStack"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metier créé avec succès"),
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
            'label' => 'required|string|unique:metiers',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = Metier::create([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);


        return response()->json(['message' => 'Metier créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Metiers"},
     *      summary="Récupère un metier par son slug",
     *      description="Retourne un metier",
     *      path="/api/metiers/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du metier à récupérer",
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
        $data = Metier::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->first();

        if (!$data) {
            return response()->json(['message' => 'Metier non trouvé'], 404);
        }

        return response()->json(['message' => 'Metier trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Metiers"},
     *     description="Modifie un metier et retourne le metier modifié",
     *     path="/api/metiers/{slug}",
     *     summary="Modification d'un metier",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Dévellopeur FullStack"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du metier à modifié",
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
     *             @OA\Property(property="message", type="string", example="Metier modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metier non trouvé"),
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

        $data = Metier::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Metier non trouvé'], 404);
        }

        $data->update([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
        ]);


        return response()->json(['message' => 'Metier modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Metiers"},
     *      summary="Supprime un metier par son slug",
     *      description="Retourne le metier supprimé",
     *      path="/api/metiers/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metier supprimé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Metier non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du metier à supprimer",
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

        $data = Metier::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Metier non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Metier supprimé avec succès',"data" => $data]);
    }
}
