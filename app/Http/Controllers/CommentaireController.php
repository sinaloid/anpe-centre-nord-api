<?php

namespace App\Http\Controllers;

use App\Models\Commentaire;
use App\Models\Publication;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CommentaireController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Commentaires"},
     *      summary="Liste des commentaires",
     *      description="Retourne la liste des commentaires",
     *      path="/api/commentaires",
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
        $data = Commentaire::where("is_deleted",false)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucun commentaire trouvé'], 404);
        }

        return response()->json(['message' => 'commentaires récupérés', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Commentaires"},
     *     description="Crée un nouveau commentaire et retourne le commentaire créé",
     *     path="/api/commentaires",
     *     summary="Création d'un commentaire",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="post", type="string", example="Slug du post"),
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem ipsum")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commentaire créé avec succès"),
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
            'post' => 'required|string|max:10',
            'description' => 'required|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $post = Post::where(["slug" => $request->post,"is_deleted" => false])->first();

        if(!$post){
            return response()->json(['message' => 'Post non trouvé'], 404);
        }

        $data = Commentaire::create([
            'description' => $request->input('description'),
            'slug' => Str::random(10),
        ]);

        Publication::create([
            'user_id' => Auth::user()->id,
            'post_id' => $post->id,
            'commentaire_id' => $data->id,
            'type' => "COMMENTAIRE",
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Commentaire créé avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Commentaires"},
     *      summary="Récupère un commentaire par son slug",
     *      description="Retourne un commentaire",
     *      path="/api/commentaires/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du commentaire à récupérer",
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
        $data = Commentaire::where(["slug"=> $slug, "is_deleted" => false])->first();

        if (!$data) {
            return response()->json(['message' => 'Commentaire non trouvé'], 404);
        }

        return response()->json(['message' => 'Commentaire trouvé', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Commentaires"},
     *     description="Modifie un commentaire et retourne le commentaire modifié",
     *     path="/api/commentaires/{slug}",
     *     summary="Modification d'un commentaire",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="description", type="string", example="Lorem ipsum lorem ipsum")
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du commentaire à modifié",
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
     *             @OA\Property(property="message", type="string", example="Commentaire modifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commentaire non trouvé"),
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

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = Commentaire::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Commentaire non trouvé'], 404);
        }

        $data->update([
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'Commentaire modifié avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Commentaires"},
     *      summary="Supprime un commentaire par son slug",
     *      description="Retourne le commentaire supprimé",
     *      path="/api/commentaires/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commentaire supprimé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commentaire non trouvé"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug du commentaire à supprimer",
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

        $data = Commentaire::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Commentaire non trouvé'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Commentaire supprimé avec succès',"data" => $data]);
    }
}
