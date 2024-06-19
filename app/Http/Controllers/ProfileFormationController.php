<?php

namespace App\Http\Controllers;

use App\Models\ProfileFormation;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileFormationController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Profile Formation"},
     *      summary="Liste des formations",
     *      description="Retourne la liste des formations",
     *      path="/api/profile-formations",
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
        $data = ProfileFormation::where("is_deleted",false)->with("profile.user")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun formations trouvée'], 404);
        }

        return response()->json(['message' => 'Formations récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Profile Formation"},
     *     description="Crée une nouvelle formation et retourne la formation créée",
     *     path="/api/profile-formations",
     *     summary="Création d'une formation",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="diplome", type="string", example="Licence en informatique"),
     *             @OA\Property(property="etablissement", type="string", example="Université Nazi Boni"),
     *             @OA\Property(property="date_debut", type="string", example="01/01/2024"),
     *             @OA\Property(property="date_fin", type="string", example="01/04/2024"),
     *             @OA\Property(property="profile", type="string", example="Slug du profile"),
     *             @OA\Property(property="mention", type="string", example="PASSABLE,BIEN,TRES_BIEN,EXCELLENT"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Formation créée avec succès"),
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
            'diplome' => 'required|string|max:255',
            'etablissement' => 'required|string|max:255',
            'date_debut' => 'required|string|max:255',
            'date_fin' => 'required|string|max:255',
            'profile' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'mention' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $profile = Profile::where("slug", $request->profile)->where("is_deleted",false)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile non trouvé'], 404);
        }

        $data = ProfileFormation::create([
            'diplome' => $request->input('diplome'),
            'etablissement' => $request->input('etablissement'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'profile_id' => $profile->id,
            'mention' => $request->input('mention'),
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Formation créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Profile Formation"},
     *      summary="Récupère une formation par son slug",
     *      description="Retourne une formation",
     *      path="/api/profile-formations/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la formation à récupérer",
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
        $data = ProfileFormation::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->with("profile.user")->first();

        if (!$data) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        }

        return response()->json(['message' => 'Formation trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Profile Formation"},
     *     description="Modifie une formation et retourne la formation modifiée",
     *     path="/api/profile-formations/{slug}",
     *     summary="Modification d'une formation",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="diplome", type="string", example="Licence en informatique"),
     *             @OA\Property(property="etablissement", type="string", example="Université Nazi Boni"),
     *             @OA\Property(property="date_debut", type="string", example="01/01/2024"),
     *             @OA\Property(property="date_fin", type="string", example="01/04/2024"),
     *             @OA\Property(property="mention", type="string", example="PASSABLE,BIEN,TRES_BIEN,EXCELLENT"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la formation à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Formation modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Formation non trouvée"),
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
            'diplome' => 'required|string|max:255',
            'etablissement' => 'required|string|max:255',
            'date_debut' => 'required|string|max:255',
            'date_fin' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'mention' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = ProfileFormation::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        }

        $data->update([
            'diplome' => $request->input('diplome'),
            'etablissement' => $request->input('etablissement'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'mention' => $request->input('mention'),
        ]);



        return response()->json(['message' => 'Formation modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Profile Formation"},
     *      summary="Supprime une formation par son slug",
     *      description="Retourne la formation supprimée",
     *      path="/api/profile-formations/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Formation supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Formation non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la formation à supprimer",
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

        $data = ProfileFormation::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Formation supprimée avec succès',"data" => $data]);
    }
}
