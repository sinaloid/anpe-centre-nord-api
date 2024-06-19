<?php

namespace App\Http\Controllers;

use App\Models\ProfileMobiliteGeographique;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileMobiliteGeographiqueController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Profile Mobilité Géographique"},
     *      summary="Liste des mobilités géographiques",
     *      description="Retourne la liste des mobilités géographiques",
     *      path="/api/profile-mobilites",
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
        $data = ProfileMobiliteGeographique::where("is_deleted",false)->with("profile.user")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun mobilité géographique trouvée'], 404);
        }

        return response()->json(['message' => 'Mobilités géographiques récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Profile Mobilité Géographique"},
     *     description="Crée une nouvelle mobilité géographique et retourne la mobilité géographique créée",
     *     path="/api/profile-mobilites",
     *     summary="Création d'une mobilité géographique",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="ville", type="string", example="TOUTES, KAYA, OUAGADOUGOU,..."),
     *             @OA\Property(property="profile", type="string", example="Slug du profile"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mobilité géographique créée avec succès"),
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
            'ville' => 'required|string|max:255',
            'profile' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $profile = Profile::where("slug", $request->profile)->where("is_deleted",false)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile non trouvé'], 404);
        }

        $data = ProfileMobiliteGeographique::create([
            'ville' => $request->input('ville'),
            'profile_id' => $profile->id,
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Mobilité géographique créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Profile Mobilité Géographique"},
     *      summary="Récupère une mobilité géographique par son slug",
     *      description="Retourne une mobilité géographique",
     *      path="/api/profile-mobilites/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la mobilité géographique à récupérer",
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
        $data = ProfileMobiliteGeographique::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->with("profile.user")->first();

        if (!$data) {
            return response()->json(['message' => 'Mobilité géographique non trouvée'], 404);
        }

        return response()->json(['message' => 'Mobilité géographique trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Profile Mobilité Géographique"},
     *     description="Modifie une mobilité géographique et retourne la mobilité géographique modifiée",
     *     path="/api/profile-mobilites/{slug}",
     *     summary="Modification d'une mobilité géographique",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="ville", type="string", example="TOUTES, KAYA, OUAGADOUGOU,..."),
     *             @OA\Property(property="niveau", type="string", example="DEBUTANT, INTERMEDIAIRE, AVANCE"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la mobilité géographique à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Mobilité géographique modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mobilité géographique non trouvée"),
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
            'ville' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = ProfileMobiliteGeographique::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'mobilité géographique non trouvée'], 404);
        }

        $data->update([
            'ville' => $request->input('ville'),
            'niveau' => $request->input('niveau'),
        ]);



        return response()->json(['message' => 'Mobilité géographique modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Profile Mobilité Géographique"},
     *      summary="Supprime une mobilité géographique par son slug",
     *      description="Retourne la mobilité géographique supprimée",
     *      path="/api/profile-mobilites/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mobilité géographique supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mobilité géographique non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la mobilité géographique à supprimer",
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

        $data = ProfileMobiliteGeographique::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Mobilité géographique non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Mobilité géographique supprimée avec succès',"data" => $data]);
    }
}
