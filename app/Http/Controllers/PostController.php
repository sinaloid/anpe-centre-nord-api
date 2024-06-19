<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Posts"},
     *      summary="Liste des posts",
     *      description="Retourne la liste des posts",
     *      path="/api/posts",
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
        $data = Post::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun post trouvé'], 404);
        }

        return response()->json(['message' => 'posts récupérés', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Posts"},
     *     description="Crée un nouveau post et retourne l'post créé",
     *     path="/api/posts",
     *     summary="Création d'un post",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="titre", type="string", example="Comment se préparer à un entretien d'embauche"),
     *             @OA\Property(property="type", type="string", example="EMPLOI,STAGE,FORMATION,PROJET"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem ipsum")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="post créé avec succès"),
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
            'type' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        $data = Post::create([
            'titre' => $request->input('titre'),
            'type' => $request->input('type'),
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);

        Publication::create([
            'user_id' => Auth::user()->id,
            'post_id' => $data->id,
            'type' => "POST",
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'post créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Posts"},
     *      summary="Récupère un post par son slug",
     *      description="Retourne un post",
     *      path="/api/posts/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du post à récupérer",
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
        $data = Post::where([
            "slug"=> $slug,
            "is_deleted" => false
        ])->with(
            [
                'publications' => function ($query) {
                    // Ajoutez des conditions sur la relation 'publications' ici si nécessaire
                    $query->where([
                        'is_deleted' => false,
                        'type' => "COMMENTAIRE"
                    ])->whereNotNull('commentaire_id');
                },
                'publications.commentaire' => function ($query) {
                    // Ajoutez des conditions sur la relation 'commentaire' ici si nécessaire
                    $query->where('is_deleted', false);
                },
                'publications.commentaire.publications' => function ($query) {
                    // Ajoutez des conditions sur la relation 'commentaire' ici si nécessaire
                    $query->where([
                        'is_deleted' => false,
                        'type' => "REPONSE"
                    ]);
                },
                'publications.commentaire.publications.reponse' => function ($query) {
                    // Ajoutez des conditions sur la relation 'commentaire' ici si nécessaire
                    $query->where([
                        'is_deleted' => false,
                    ]);
                }
            ]
        )->first();

        if (!$data) {
            return response()->json(['message' => 'post non trouvé'], 404);
        }

        return response()->json(['message' => 'post trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Posts"},
     *     description="Modifie un post et retourne le post modifié",
     *     path="/api/posts/{slug}",
     *     summary="Modification d'un post",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="titre", type="string", example="Comment se préparer à un entretien d'embauche"),
     *             @OA\Property(property="type", type="string", example="EMPLOI,STAGE,FORMATION,PROJET"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem ipsum")
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du post à modifié",
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
     *             @OA\Property(property="message", type="string", example="post modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="post non trouvé"),
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
            'type' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = Post::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'post non trouvé'], 404);
        }

        $data->update([
            'titre' => $request->input('titre'),
            'type' => $request->input('type'),
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'post modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Posts"},
     *      summary="Supprime un post par son slug",
     *      description="Retourne le post supprimé",
     *      path="/api/posts/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="'post supprimé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="post non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du post à supprimer",
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

        $data = Post::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'post non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'post supprimé avec succès',"data" => $data]);
    }
}
