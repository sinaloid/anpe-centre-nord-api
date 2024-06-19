<?php

namespace App\Http\Controllers;

use App\Models\TypeContrat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TypeContratController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Types de contrats"},
     *      summary="Liste des types de contrats",
     *      description="Retourne la liste des types de contrats",
     *      path="/api/types-contrats",
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
        $data = TypeContrat::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun type de contrat trouvé'], 404);
        }

        return response()->json(['message' => 'types de contrats récupérés', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Types de contrats"},
     *     description="Crée un nouveau type de contrat et retourne le type de contrat créé",
     *     path="/api/types-contrats",
     *     summary="Création d'un type de contrat",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="CDI"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="type de contrat créé avec succès"),
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
            'label' => 'required|string|unique:type_contrats',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = TypeContrat::create([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);


        return response()->json(['message' => 'type de contrat créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Types de contrats"},
     *      summary="Récupère un type de contrat par son slug",
     *      description="Retourne un type de contrat",
     *      path="/api/types-contrats/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du type de contrat à récupérer",
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
        $data = TypeContrat::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->first();

        if (!$data) {
            return response()->json(['message' => 'type de contrat non trouvé'], 404);
        }

        return response()->json(['message' => 'type de contrat trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Types de contrats"},
     *     description="Modifie un type de contrat et retourne le type de contrat modifié",
     *     path="/api/types-contrats/{slug}",
     *     summary="Modification d'un type de contrat",
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="CDI"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum"),
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du type de contrat à modifié",
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
     *             @OA\Property(property="message", type="string", example="type de contrat modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="type de contrat non trouvé"),
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

        $data = TypeContrat::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'type de contrat non trouvé'], 404);
        }

        $data->update([
            'label' => $request->input('label'),
            'description' => $request->input('description'),
        ]);


        return response()->json(['message' => 'type de contrat modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Types de contrats"},
     *      summary="Supprime un type de contrat par son slug",
     *      description="Retourne le type de contrat supprimé",
     *      path="/api/types-contrats/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="type de contrat supprimé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="type de contrat non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du type de contrat à supprimer",
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

        $data = TypeContrat::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'type de contrat non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'type de contrat supprimé avec succès',"data" => $data]);
    }
}
