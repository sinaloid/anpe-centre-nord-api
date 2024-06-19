<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Profile"},
     *      summary="Liste des profiles",
     *      description="Retourne la liste des profiles",
     *      path="/api/profiles",
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
        $data = Profile::where("is_deleted",false)->with("user")->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun profile trouvé'], 404);
        }

        return response()->json(['message' => 'Profiles récupérés', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Profile"},
     *     description="Crée un nouveau profile et retourne le profile créé",
     *     path="/api/profiles",
     *     summary="Création d'un profile",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="apropos", type="string", example="Passioné par le digital et les nouvelles technologies, je recherche des missions challengantes"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="telephone", type="string", example="75000000"),
     *             @OA\Property(property="ville", type="string", example="Kaya"),
     *             @OA\Property(property="adresse", type="string", example="Secteur 05"),
     *             @OA\Property(property="annee_experience", type="string", example="2 ans"),
     *             @OA\Property(property="niveau_etude", type="string", example="BAC + 3"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="profile créé avec succès"),
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
        $request['user_id'] = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'apropos' => 'required|string|max:10000',
            'telephone' => 'required|integer|digits:8|starts_with:5,6,7,01,02,03,05,06,07|unique:profiles',
            'user_id' => 'required|integer|unique:profiles',
            'email' => 'required|string|email|max:255|unique:profiles',
            'ville' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'annee_experience' => 'required|string|max:255',
            'niveau_etude' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',


        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = Profile::create([
            'user_id' => $request->input('user_id'),
            'apropos' => $request->input('apropos'),
            'email' => $request->input('email'),
            'telephone' => $request->input('telephone'),
            'ville' => $request->input('ville'),
            'adresse' => $request->input('adresse'),
            'annee_experience' => $request->input('annee_experience'),
            'niveau_etude' => $request->input('niveau_etude'),
            'slug' => Str::random(10),
        ]);

        /*Publication::create([
            'user_id' => Auth::user()->id,
            'profile_id' => $data->id,
            'type' => "profile",
            'slug' => Str::random(10),
        ]);*/

        return response()->json(['message' => 'Profile créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Profile"},
     *      summary="Récupère un profile par son slug",
     *      description="Retourne un profile",
     *      path="/api/profiles/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du profile à récupérer",
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
        $data = Profile::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->with("user")->first();

        if (!$data) {
            return response()->json(['message' => 'Profile non trouvé'], 404);
        }

        return response()->json(['message' => 'Profile trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Profile"},
     *     description="Modifie un profile et retourne le profile modifié",
     *     path="/api/profiles/{slug}",
     *     summary="Modification d'un profile",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="apropos", type="string", example="Passioné par le digital et les nouvelles technologies, je recherche des missions challengantes"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="telephone", type="string", example="75000000"),
     *             @OA\Property(property="ville", type="string", example="Kaya"),
     *             @OA\Property(property="adresse", type="string", example="Secteur 05"),
     *             @OA\Property(property="annee_experience", type="string", example="2 ans"),
     *             @OA\Property(property="niveau_etude", type="string", example="BAC + 3"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du profile à modifié",
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
     *             @OA\Property(property="message", type="string", example="profile modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="profile non trouvé"),
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
            'apropos' => 'required|string|max:10000',
            'telephone' => 'required|integer|digits:8|starts_with:5,6,7,01,02,03,05,06,07',
            'email' => 'required|string|email|max:255',
            'ville' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'annee_experience' => 'required|string|max:255',
            'niveau_etude' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = Profile::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Profile non trouvé'], 404);
        }

        $data->update([
            'apropos' => $request->input('apropos'),
            'email' => $request->input('email'),
            'telephone' => $request->input('telephone'),
            'ville' => $request->input('ville'),
            'adresse' => $request->input('adresse'),
            'annee_experience' => $request->input('annee_experience'),
            'niveau_etude' => $request->input('niveau_etude'),
        ]);


        return response()->json(['message' => 'Profile modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Profile"},
     *      summary="Supprime un profile par son slug",
     *      description="Retourne le profile supprimé",
     *      path="/api/profiles/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="'profile supprimé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du profile à supprimer",
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

        $data = Profile::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Profile non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Profile supprimé avec succès',"data" => $data]);
    }
}
