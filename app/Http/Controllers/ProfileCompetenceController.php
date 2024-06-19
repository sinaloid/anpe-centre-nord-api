<?php

namespace App\Http\Controllers;

use App\Models\ProfileCompetence;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileCompetenceController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Profile Compétence"},
     *      summary="Liste des compétences",
     *      description="Retourne la liste des compétences",
     *      path="/api/profile-competences",
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
        $data = ProfileCompetence::where("is_deleted",false)->with("profile.user")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun compétence trouvée'], 404);
        }

        return response()->json(['message' => 'Compétences récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Profile Compétence"},
     *     description="Crée une nouvelle compétence et retourne la compétence créée",
     *     path="/api/profile-competences",
     *     summary="Création d'une compétence",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="competence", type="string", example="Bureautique: Word, excel, powerpoint"),
     *             @OA\Property(property="niveau", type="string", example="DEBUTANT, INTERMEDIAIRE, AVANCE"),
     *             @OA\Property(property="profile", type="string", example="Slug du profile"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="compétence créée avec succès"),
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
            'competence' => 'required|string|max:255',
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

        $data = ProfileCompetence::create([
            'competence' => $request->input('competence'),
            'niveau' => $request->input('niveau'),
            'profile_id' => $profile->id,
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Compétence créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Profile Compétence"},
     *      summary="Récupère une compétence par son slug",
     *      description="Retourne une compétence",
     *      path="/api/profile-competences/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la compétence à récupérer",
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
        $data = ProfileCompetence::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->with("profile.user")->first();

        if (!$data) {
            return response()->json(['message' => 'Compétence non trouvée'], 404);
        }

        return response()->json(['message' => 'Compétence trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Profile Compétence"},
     *     description="Modifie une compétence et retourne la compétence modifiée",
     *     path="/api/profile-competences/{slug}",
     *     summary="Modification d'une compétence",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="competence", type="string", example="Bureautique: Word, excel, powerpoint"),
     *             @OA\Property(property="niveau", type="string", example="DEBUTANT, INTERMEDIAIRE, AVANCE"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la compétence à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Compétence modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compétence non trouvée"),
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
            'competence' => 'required|string|max:255',
            'niveau' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = ProfileCompetence::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Compétence non trouvée'], 404);
        }

        $data->update([
            'competence' => $request->input('competence'),
            'niveau' => $request->input('niveau'),
        ]);



        return response()->json(['message' => 'Compétence modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Profile Compétence"},
     *      summary="Supprime une compétence par son slug",
     *      description="Retourne la compétence supprimée",
     *      path="/api/profile-competences/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compétence supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compétence non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la compétence à supprimer",
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

        $data = ProfileCompetence::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Compétence non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Compétence supprimée avec succès',"data" => $data]);
    }
}
