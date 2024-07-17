<?php

namespace App\Http\Controllers;

use App\Models\Candidature;
use App\Models\Offre;
use App\Models\User;
use App\Models\RessourceCandidature;
use App\Models\UserOffreCandidature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CandidatureController extends Controller
{
    /**
     * @OA\Get(
     *      tags={"Candidatures"},
     *      summary="Liste des candidatures",
     *      description="Retourne la liste des candidatures",
     *      path="/api/candidatures",
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
        //$data = Candidature::where("is_deleted",false)->paginate(10);

        $data = Candidature::where([
            "is_deleted" => false //UserOffreCandidature
        ])->with(
            [
                'user_offre_candidature' => function ($query) {
                    // Ajoutez des conditions sur la relation 'publications' ici si nécessaire
                    $query->where([
                        'is_deleted' => false,
                        'type' => "CANDIDATURE",
                    ]);//->whereNotNull('offre_id');
                },
                'user_offre_candidature.user' => function ($query) {
                    // Ajoutez des conditions sur la relation 'commentaire' ici si nécessaire
                    $query->where('is_deleted', false);
                },
                'user_offre_candidature.offre' => function ($query) {
                    // Ajoutez des conditions sur la relation 'commentaire' ici si nécessaire
                    $query->where([
                        'is_deleted' => false,
                    ]);
                },
                "ressource_candidatures" => function($query){
                        $query->where([
                            'is_deleted' => false,
                        ]);
                    }
            ]
        )->paginate(10);

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Aucune candidature trouvée'], 404);
        }

        return response()->json(['message' => 'Candidatures récupérées', 'data' => $data], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Candidatures"},
     *     description="Crée une nouvelle candidature et retourne la candidature créée",
     *     path="/api/candidatures",
     *     summary="Création d'une candidature",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Candidature au recrutement au recrutement de 10 chauffeurs"),
     *             @OA\Property(property="etat", type="string", example="EN_COURS,NON_VALIDE,ACCEPTE"),
     *             @OA\Property(property="offre", type="string", example="Slug de l'offre"),
     *             @OA\Property(property="description", type="string", example="Candidature au recrutement au recrutement de 10 chauffeurs")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Candidature créée avec succès"),
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
            //'label' => 'required|string|max:255',
            //'etat' => 'required|string|max:255',
            'offre' => 'required|string|max:10',
            //'description' => 'nullable|string|max:10000',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $offre = Offre::where(["slug" => $request->offre,"is_deleted" => false])->first();

        if(!$offre){
            return response()->json(['message' => 'Offre non trouvé'], 404);
        }

        $data = Candidature::create([
            'label' => $offre->label,
            'etat' => "EN_COURS",
            'description' => $offre->label,
            'slug' => Str::random(10),
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                //$path = $file->store('uploads');
                //$filePaths[] = $path;
                $newFileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('candidatures', $newFileName);
                // Enregistrer le fichier dans la base de données
                RessourceCandidature::create([
                    'original_name' => $file->getClientOriginalName(),
                    'name' => $newFileName,
                    'candidature_id' => $data->id,
                    'url' => Storage::url($path),
                    'slug' => Str::random(10),
                ]);

            }
        }

        UserOffreCandidature::create([
            'user_id' => Auth::user()->id,
            'offre_id' => $offre->id,
            'candidature_id' => $data->id,
            'type' => "CANDIDATURE",
            'slug' => Str::random(10),
        ]);

        return response()->json(['message' => 'Candidature créée avec succès', 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *      tags={"Candidatures"},
     *      summary="Récupère une candidature par son slug",
     *      description="Retourne une candidature",
     *      path="/api/candidatures/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la candidature à récupérer",
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
        $data = Candidature::where(["slug"=> $slug, "is_deleted" => false])->first();

        if (!$data) {
            return response()->json(['message' => 'Candidature non trouvée'], 404);
        }

        return response()->json(['message' => 'Candidature trouvée', 'data' => $data], 200);
    }

    /**
     * @OA\Put(
     *     tags={"Candidatures"},
     *     description="Modifie une candidature et retourne la offre candidature",
     *     path="/api/candidatures/{slug}",
     *     summary="Modification d'une candidature",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label"},
     *             @OA\Property(property="label", type="string", example="Candidature au recrutement au recrutement de 10 chauffeurs"),
     *             @OA\Property(property="etat", type="string", example="EN_COURS,NON_VALIDE,ACCEPTE"),
     *             @OA\Property(property="description", type="string", example="Candidature au recrutement au recrutement de 10 chauffeurs")
     *         ),
     *     ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la candidature à modifiée",
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
     *             @OA\Property(property="message", type="string", example="Candidature modifiée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slug validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Candidature non trouvée"),
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
            'label' => 'required|string|max:255',
            'etat' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',

        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $data = Candidature::where("slug", $slug)->where("is_deleted",false)->first();

        if (!$data) {
            return response()->json(['message' => 'Candidature non trouvée'], 404);
        }

        $data->update([
            'label' => $request->input('label'),
            'etat' => $request->input('etat'),
            'description' => $request->input('description'),
        ]);

        return response()->json(['message' => 'Candidature modifiée avec succès', 'data' => $data], 200);

    }

    /**
     * @OA\Delete(
     *      tags={"Candidatures"},
     *      summary="Supprime une candidature par son slug",
     *      description="Retourne l'candidature supprimée",
     *      path="/api/candidatures/{slug}",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Candidature supprimée avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Slug validation error",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Candidature non trouvée"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="slug de la candidature à supprimer",
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

        $data = Candidature::where("slug",$slug)->where("is_deleted",false)->first();
        if (!$data) {
            return response()->json(['message' => 'Candidature non trouvée'], 404);
        }


        $data->update(["is_deleted" => true]);

        return response()->json(['message' => 'Candidature supprimée avec succès',"data" => $data]);
    }


    public function getFile($file)
    {
        $ressource = RessourceCandidature::where('name', $file)->first();

        $path = storage_path('app/candidatureS/' . $file);

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        //$response->header("Content-Type", $type);
        return response()->header("Content-Type", $type)->json(['error' => 'fichier trouvé'], 200);

        return $response;


        if ($ressource && Storage::exists("candidatures/$file")) {
            return Storage::download("candidatures/$file");
        }
        return response()->json(['error' => 'Aucun fichier trouvé'], 404);
    }
}
