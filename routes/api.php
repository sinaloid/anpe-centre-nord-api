<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OtpController;

use App\Http\Controllers\OffreController;
use App\Http\Controllers\CandidatureController;
use App\Http\Controllers\RecruteurController;
use App\Http\Controllers\PostulantController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\ReponseController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileExperienceController;
use App\Http\Controllers\ProfileFormationController;
use App\Http\Controllers\ProfileCompetenceController;
use App\Http\Controllers\ProfileLangueController;
use App\Http\Controllers\ProfileMobiliteGeographiqueController;
use App\Http\Controllers\MetierController;
use App\Http\Controllers\SecteurActiviteController;
use App\Http\Controllers\NiveauEtudeController;
use App\Http\Controllers\TypeContratController;
use App\Http\Controllers\NiveauExperienceController;
use App\Http\Controllers\VilleController;
use App\Http\Controllers\PreferenceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group(['middleware' => ['cors','json.response']], function () {
    Route::post('/register', [AuthController::class,'register']);
    Route::post('/verify-otp', [AuthController::class,'verifyOtp']);
    Route::post('/get-otp', [AuthController::class,'getOtp']);
    Route::post('/login', [AuthController::class,'login']);
    Route::post('/verify-2fa', [AuthController::class,'verify2fa']);
    Route::post('/edit-password', [AuthController::class,'editPassword']);

    Route::middleware(['auth:api'])->group(function () {
        Route::get('/users', [AuthController::class,'user']);
        Route::get('/users/auth', [AuthController::class,'userAuth']);
        Route::post('/users/update', [AuthController::class,'update']);
        Route::post('/users/changePassword', [AuthController::class,'changePassword']);
        Route::post('/users/get', [AuthController::class,'userBy']);
        Route::post('/users/disable', [AuthController::class,'disable']);

        Route::post('/users/change-auth-2fa-statut', [AuthController::class,'changeAuth2FaStatus']);
        Route::post('/users/change-block-statut', [AuthController::class,'changeBlockStatus']);


        Route::get('/recruteur-offres-candidatures', [RecruteurOffreController::class,'getAllCadidature']);

        Route::resources([
            'offres' => OffreController::class,
            'candidatures' => CandidatureController::class,
            'recruteur-offres' => RecruteurController::class,
            'postulant-candidatures' => PostulantController::class,
            'posts' => PostController::class,
            'commentaires' => CommentaireController::class,
            'reponses' => ReponseController::class,
            'publications' => PublicationController::class,
            'profiles' => ProfileController::class,
            'profile-experiences' => ProfileExperienceController::class,
            'profile-formations' => ProfileFormationController::class,
            'profile-competences' => ProfileCompetenceController::class,
            'profile-langues' => ProfileLangueController::class,
            'profile-mobilites' => ProfileMobiliteGeographiqueController::class,
            'metiers' => MetierController::class,
            'secteurs-activite' => SecteurActiviteController::class,
            'niveaux-etudes' => NiveauEtudeController::class,
            'types-contrats' => TypeContratController::class,
            'niveaux-experience' => NiveauExperienceController::class,
            'villes' => VilleController::class,
            'preferences' => PreferenceController::class,

        ]);
    });
});
