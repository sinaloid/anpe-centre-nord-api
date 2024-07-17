<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Ressource;

class FileController extends Controller
{
     /**
     * Afficher la liste des fichiers.
     */
    public function index()
    {
        $ressources = Ressource::all();

        return response()->json(['message' => 'Cours récupérés', 'data' => $ressources], 200);
    }

    /**
     * Stocker un nouveau fichier.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'id' => 'required|string|max:255', 
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $filePaths = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                //$path = $file->store('uploads');
                $filePaths[] = $path;
                $newFileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads', $newFileName);
                // Enregistrer le fichier dans la base de données
                Ressource::create([
                    'original_name' => $file->getClientOriginalName(),
                    'name' => $newFileName,
                    'url' => Storage::url($path),
                ]);


                // Enregistrer le fichier avec le nouveau nom

                $filePaths[] = $path;
                Ressource::create([
                    'name' => $newFileName,
                    'url' => Storage::url($path),
                ]);

            }
        }

        return response()->json(['filePaths' => $filePaths, 'message' => "Fichiers enregistrés"], 201);
    }

    /**
     * Afficher un fichier spécifique.
     */
    public function show($file)
    {
        $ressource = Ressource::where('name', $file)->first();

        if ($ressource && Storage::exists("uploads/$file")) {
            return Storage::download("uploads/$file");
        }

        return response()->json(['error' => 'File not found'], 404);
    }
}
