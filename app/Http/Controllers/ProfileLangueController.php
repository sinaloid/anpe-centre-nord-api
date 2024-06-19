<?php

namespace App\Http\Controllers;

use App\Models\ProfileLangue;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileLangueController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Profile Langue"},
     *      summary="Liste des langues",
     *      description="Retourne la liste des langues",
     *      path="/api/profile-langues",
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
        $data = ProfileLangue::where("is_deleted",false)->with("profile.user")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun langue trouvée'], 404);
        }

        return response()->json(['message' => 'Langues récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Profile Langue"},
     *     description="Crée une nouvelle langue et retourne la langue créée",
     *     path="/api/profile-langues",
     *     summary="Création d'une langue",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="langue", type="string", example="Anglais"),
     *             @OA\Property(property="niveau", type="string", example="DEBUTANT, INTERMEDIAIRE, AVANCE"),
     *             @OA\Property(property="profile", type="string", example="Slug du profile"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="langue créée avec succès"),
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
            'langue' => 'required|string|max:255',
            'niveau' => 'required|string|max:255',
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

        $data = ProfileLangue::create([
            'langue' => $request->input('langue'),
            'niveau' => $request->input('niveau'),
            'profile_id' => $profile->id,
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Langue créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Profile Langue"},
     *      summary="Récupère une langue par son slug",
     *      description="Retourne une langue",
     *      path="/api/profile-langues/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la langue à récupérer",
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
        $data = ProfileLangue::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->with("profile.user")->first();

        if (!$data) {
            return response()->json(['message' => 'Langue non trouvée'], 404);
        }

        return response()->json(['message' => 'Langue trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Profile Langue"},
     *     description="Modifie une langue et retourne la langue modifiée",
     *     path="/api/profile-langues/{slug}",
     *     summary="Modification d'une langue",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="langue", type="string", example="Anglais"),
     *             @OA\Property(property="niveau", type="string", example="DEBUTANT, INTERMEDIAIRE, AVANCE"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la langue à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Langue modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Langue non trouvée"),
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
            'langue' => 'required|string|max:255',
            'niveau' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = ProfileLangue::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Langue non trouvée'], 404);
        }

        $data->update([
            'langue' => $request->input('langue'),
            'niveau' => $request->input('niveau'),
        ]);



        return response()->json(['message' => 'Langue modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Profile Langue"},
     *      summary="Supprime une langue par son slug",
     *      description="Retourne la langue supprimée",
     *      path="/api/profile-langues/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Langue supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Langue non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la langue à supprimer",
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

        $data = ProfileLangue::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Langue non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Langue supprimée avec succès',"data" => $data]);
    }
}
