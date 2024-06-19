<?php

namespace App\Http\Controllers;

use App\Models\NiveauEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NiveauEtudeController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Niveaux études"},
     *      summary="Liste des niveaux d'études",
     *      description="Retourne la liste des niveaux d'études",
     *      path="/api/niveaux-etudes",
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
        $data = NiveauEtude::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun niveau d\'étude trouvé'], 404);
        }

        return response()->json(['message' => "niveaux d'études récupérés", 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Niveaux études"},
     *     description="Crée un nouveau niveau d\'étude et retourne le niveau d\'étude créé",
     *     path="/api/niveaux-etudes",
     *     summary="Création d'un niveau d\'étude",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Bac + 3"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'étude créé avec succès"),
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
            'label' => 'required|string|unique:niveau_etudes',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = NiveauEtude::create([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);


        return response()->json(['message' => 'niveau d\'étude créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Niveaux études"},
     *      summary="Récupère un niveau d\'étude par son slug",
     *      description="Retourne un niveau d\'étude",
     *      path="/api/niveaux-etudes/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du niveau d\'étude à récupérer",
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
        $data = NiveauEtude::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->first();

        if (!$data) {
            return response()->json(['message' => 'niveau d\'étude non trouvé'], 404);
        }

        return response()->json(['message' => 'niveau d\'étude trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Niveaux études"},
     *     description="Modifie un niveau d\'étude et retourne le niveau d\'étude modifié",
     *     path="/api/niveaux-etudes/{slug}",
     *     summary="Modification d'un niveau d\'étude",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Bac + 3"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du niveau d\'étude à modifié",
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
     *             @OA\Property(property="message", type="string", example="niveau d\'étude modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'étude non trouvé"),
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

        $data = NiveauEtude::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'niveau d\'étude non trouvé'], 404);
        }

        $data->update([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
        ]);


        return response()->json(['message' => 'niveau d\'étude modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Niveaux études"},
     *      summary="Supprime un niveau d\'étude par son slug",
     *      description="Retourne le niveau d\'étude supprimé",
     *      path="/api/niveaux-etudes/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'étude supprimé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="niveau d\'étude non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du niveau d\'étude à supprimer",
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

        $data = NiveauEtude::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'niveau d\'étude non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'niveau d\'étude supprimé avec succès',"data" => $data]);
    }
}
