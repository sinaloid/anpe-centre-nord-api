<?php

namespace App\Http\Controllers;

use App\Models\ProfileExperience;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileExperienceController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Profile Expérience"},
     *      summary="Liste des expériences",
     *      description="Retourne la liste des expériences",
     *      path="/api/profile-experiences",
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
        $data = ProfileExperience::where("is_deleted",false)->with("profile.user")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun expériences trouvée'], 404);
        }

        return response()->json(['message' => 'Expériences récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Profile Expérience"},
     *     description="Crée une nouvelle expérience et retourne l'expérience créée",
     *     path="/api/profile-experiences",
     *     summary="Création d'une expérience",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="titre", type="string", example="Mise en place d'un plugin moodle"),
     *             @OA\Property(property="entreprise", type="string", example="SYMBOL-SARL"),
     *             @OA\Property(property="date_debut", type="string", example="01/01/2024"),
     *             @OA\Property(property="date_fin", type="string", example="01/04/2024"),
     *             @OA\Property(property="profile", type="string", example="Slug du profile"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Expérience créée avec succès"),
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
            'titre' => 'required|string|max:255',
            'entreprise' => 'required|string|max:255',
            'date_debut' => 'required|string|max:255',
            'date_fin' => 'required|string|max:255',
            'profile' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $profile = Profile::where("slug", $request->profile)->where("is_deleted",false)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile non trouvé'], 404);
        }

        $data = ProfileExperience::create([
            'titre' => $request->input('titre'),
            'entreprise' => $request->input('entreprise'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'profile_id' => $profile->id,
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Expérience créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Profile Expérience"},
     *      summary="Récupère une expérience par son slug",
     *      description="Retourne une expérience",
     *      path="/api/profile-experiences/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de l'expérience à récupérer",
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
        $data = ProfileExperience::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->with("profile.user")->first();

        if (!$data) {
            return response()->json(['message' => 'Expérience non trouvée'], 404);
        }

        return response()->json(['message' => 'Expérience trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Profile Expérience"},
     *     description="Modifie une expérience et retourne l'expérience modifiée",
     *     path="/api/profile-experiences/{slug}",
     *     summary="Modification d'une expérience",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="titre", type="string", example="Mise en place d'un plugin moodle"),
     *             @OA\Property(property="entreprise", type="string", example="SYMBOL-SARL"),
     *             @OA\Property(property="date_debut", type="string", example="01/01/2024"),
     *             @OA\Property(property="date_fin", type="string", example="01/04/2024"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de l'expérience à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Expérience modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Expérience non trouvée"),
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
            'titre' => 'required|string|max:255',
            'entreprise' => 'required|string|max:255',
            'date_debut' => 'required|string|max:255',
            'date_fin' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = ProfileExperience::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Expérience non trouvée'], 404);
        }

        $data->update([
            'titre' => $request->input('titre'),
            'entreprise' => $request->input('entreprise'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'description' => $request->input('description'),
        ]);



        return response()->json(['message' => 'Expérience modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Profile Expérience"},
     *      summary="Supprime une expérience par son slug",
     *      description="Retourne l'expérience supprimée",
     *      path="/api/profile-experiences/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Expérience supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Expérience non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de l'expérience à supprimer",
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

        $data = ProfileExperience::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Expérience non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Expérience supprimée avec succès',"data" => $data]);
    }
}
