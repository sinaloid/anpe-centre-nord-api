<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtpController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use App\Notifications\OtpCode;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Création d'un compte utilisateur",
     *     description="Création d'un nouveau compte utilisateur",
     *     operationId="receiveJson",
     *     tags={"Autentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","date_de_naissance","genre","telephone","email","password"},
     *             @OA\Property(property="nom", type="string", example="Doe"),
     *             @OA\Property(property="prenom", type="string", example="John"),
     *             @OA\Property(property="date_de_naissance", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="genre", type="string", example="M"),
     *             @OA\Property(property="profile", type="string", example="POSTULANT"),
     *             @OA\Property(property="telephone", type="string", example="75000000"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", example="#test@password*2024")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Votre compte a été crée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="nom", type="string", example="Doe"),
     *                 @OA\Property(property="prenom", type="string", example="John"),
     *                 @OA\Property(property="date_de_naissance", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="genre", type="string", example="M"),
     *                 @OA\Property(property="profile", type="string", example="POSTULANT"),
     *                 @OA\Property(property="telephone", type="string", example="75000000"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="password", type="string", example="#test@password*2024")
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_de_naissance' => 'required|string|max:255',
            'genre' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'telephone' => 'nullable|integer|digits:8|starts_with:5,6,7,01,02,03,05,06,07|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

            $otpController = app(OtpController::class);
            $response = $otpController->generateOTP($request);
            $response = $response->getData();

            $matricule = $this->matriculeGenerator("ANPE-CN");
            $user = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_de_naissance' => $request->date_de_naissance,
                'genre' => $request->genre,
                'profile' => $request->profile,
                'genre' => $request->genre,
                'telephone' => $request->telephone,
                'matricule' => $matricule,
                'email' => $request->email,
                'slug' => Str::random(10),
                'isActive' => false,
                'password' => bcrypt($request->password),
            ]);

            if ($request->hasFile('image')) {
                // Générer un nom aléatoire pour l'image
                $imageName = Str::random(10) . '.' . $request->image->getClientOriginalExtension();

                // Enregistrer l'image dans le dossier public/images
                $imagePath = $request->image->move(public_path('users'), $imageName);

                if ($imagePath) {
                    $user->update([
                        'image' => 'users/' . $imageName,
                    ]);
                }
            }

            $token = null;//$user->createToken('my-app-token')->accessToken;
            $user->notify(new OtpCode($response->code));

            return response()->json(['message' => "Votre compte a été créé. Un code de vérification a été envoyé à votre adresse email.",'data' => $user, 'access_token' => $token],200);

    }

    public function matriculeGenerator($prefixe){

        $last_user = User::orderBy("id",'desc')->first();
        $id = isset($last_user) ? $last_user->id : 0;
        $order = str_pad('', 4 - strlen($id), '0', STR_PAD_LEFT);

        return $prefixe."-".date('y')."-".$order."".$id;
    }

    /**
     * @OA\Post(
     *     path="/api/verify-otp",
     *     summary="Verification du code otp",
     *     description="Validation de l'adresse mail à travers le code OTP",
     *     tags={"Autentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user","password"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com ou 75000000"),
     *             @OA\Property(property="otp", type="string", example="0000")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Code OTP vérifié avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function verifyOtp(Request $request){

        $otpController = app(OtpController::class);
        $response = $otpController->verifyEmailOTP($request);

        if($response->getStatusCode() === 200){
            $user = User::where("email",$request->email)->first();
            $user->update([
                //"isActive" => true,
                "email_verified_at" => Carbon::now()
            ]);

        }

        return $response;
    }
    /**
     * @OA\Post(
     *     path="/api/get-otp",
     *     summary="Envoi d'un nouveau code otp",
     *     description="Envoi un nouveau code otp à l'adresse mail d'inscription",
     *     tags={"Autentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com ou 75000000"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Le code OTP a été généré et envoyé avec succès à votre adresse e-mail"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
    */

     public function getOtp(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = User::where([
            "email" => $request->email,
            "is_deleted" => false
        ])->first();

        if(!$user){
            return response()->json(['message' => "Vous n'avez pas de compte sur notre plateforme"]);
        }
        $otpController = app(OtpController::class);
        $response = $otpController->generateOTP($request);
        $response = $response->getData();

        $user->notify(new OtpCode($response->code));
        return $response;
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Connexion d'un utilisateur",
     *     description="Connexion d'un utilisateur à son compte",
     *     tags={"Autentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user","password"},
     *             @OA\Property(property="user", type="string", example="john.doe@example.com ou 75000000"),
     *             @OA\Property(property="password", type="string", example="#test@password*2024")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Connexion réussi avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        // Validation des données de la requête
        $validator = Validator::make($request->all(), [
            'user' => [
                'required',
                'string',
                function ($attribute, $value, $fail){
                    $this->mail_number_matricule_validate($attribute, $value, $fail);
                },
            ],
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Préparer les identifiants pour l'authentification
        $credentialsEmail = ['email' => $request->user, 'password' => $request->password];
        $credentialsTelephone = ['telephone' => $request->user, 'password' => $request->password];
        $credentialsMatricule = ['matricule' => $request->user, 'password' => $request->password];

        // Essayer de se connecter avec l'email ou le téléphone
        if (Auth::attempt($credentialsEmail) || Auth::attempt($credentialsTelephone) || Auth::attempt($credentialsMatricule)) {
            $user = Auth::user();

            // Vérifier si le compte est actif
            /*if (!$user->isActive) {
                return response()->json(['errors' => "Votre compte n'est pas actif, veuillez vérifier votre adresse mail"], 401);
            }*/

            // Vérifier si l'email est vérifié
            if (!$user->email_verified_at) {
                return response()->json(['errors' => "Votre email n'a pas été validé, veuillez valider votre email avant de pouvoir continuer"], 401);
            }

            if ($user->is_blocked) {
                return response()->json(['errors' => "Votre compte est bloqué. Veuillez nous contacter pour obtenir de l'aide"], 401);
            }

            if ($user->two_factor_is_active) {
                $user->generateTwoFactorCode();
                $user->notify(new TwoFactorCode());

                return response()->json(['message' => 'Le code 2FA a été envoyé à votre adresse email']);
                //return response()->json(['errors' => "Votre email n'a pas été validé, veuillez valider votre email avant de pouvoir continuer"], 401);
            }

            // Générer un token d'accès
            $token = $user->createToken('my-app-token')->accessToken;
            return response()->json(["message" => "Connexion réussi avec succès", 'user' => $user, 'access_token' => $token],200);
        }

        // Retourner une réponse en cas d'identifiants invalides
        return response()->json(['error' => 'Identifiants de connexion invalides'], 401);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-2fa",
     *     summary="Authentification à deux facteurs",
     *     description="authentification à deux facteurs : vérification du code otp",
     *     tags={"Autentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","code"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="code", type="string", example="00000000")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Connexion réussi avec succès"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'email' => [
                'required',
                'string',
                function ($attribute, $value, $fail){
                    $this->mail_number_matricule_validate($attribute, $value, $fail);
                },
            ],
            'code' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)
                    ->where('two_factor_code', $request->code)
                    ->where('two_factor_expires_at', '>', now())
                    ->first();

        if ($user) {
            $user->resetTwoFactorCode();

            $token = $token = $user->createToken('my-app-token')->accessToken;

            return response()->json(["message" => "Connexion réussi avec succès", 'user' => $user, 'access_token' => $token],200);
        }

        return response()->json(['message' => 'Code 2FA invalide ou expiré'], 401);
    }


    /**
     * @OA\Post(
     *     path="/api/edit-password",
     *     summary="Modification du mot de passe",
     *     description="Modifier le mot de passe en utilisant votre email et un code otp généré",
     *     tags={"Autentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user","password"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com ou 75000000"),
     *             @OA\Property(property="password", type="string", example="#test@password*2024"),
     *             @OA\Property(property="otp", type="string", example="0000")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Votre mot de passe à bien été modifier, vous pouvez vous connecter maintenant"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function editPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $otpController = app(OtpController::class);
        $response = $otpController->verifyEmailOTP($request);

        if($response->getStatusCode() === 200){
            $user = User::where('email', $request->email)->first();

            if ($user) {

                $user->update([
                    'password' => bcrypt($request->password),
                ]);

                return response()->json(['message' => 'Votre mot de passe à bien été modifier, vous pouvez vous connecter maintenant'], 200);

            } else {
                // Réponse d'erreur
                return response()->json(['error' => "Votre adresse e-mail ou votre numéro de téléphone est incorrect"], 422);
            }

        }

        return $response;

    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Informations de l'utilisateur connecté",
     *     description="Retourne les informations de l'utilisateur connecté",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Données retrouvées"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function user(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            return response()->json(['message' => "Données retrouvées", "data" => $user], 200);
        } else {
            // Réponse d'erreur
            return response()->json(['error' => "Vous n'êtes pas authentifié"], 422);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/users/change-auth-2fa-statut",
     *     summary="Active ou desactive l'authentification à deux facteurs",
     *     description="Modifier l'authentification à deux facteurs du compte en l'activant ou en le désactivant",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Authentification à deux facteurs activé ou désactivé"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function changeAuth2FaStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where('email', $request->email)->first();

            if ($user) {

                $user->update([
                    'two_factor_is_active' => !$user->two_factor_is_active,
                ]);
                $msg = $user->two_factor_is_active ? "Authentification à deux facteurs activé" : "Authentification à deux facteurs désactivé";
                return response()->json(['message' => $msg, "data" => $user], 200);

            } else {
                // Réponse d'erreur
                return response()->json(['error' => "Les données fournies ne sont pas valides."], 422);
            }

        return $response;

    }

    /**
     * @OA\Post(
     *     path="/api/users/change-block-statut",
     *     summary="Bloque ou debloque le compte",
     *     description="Modifier le statut du compte en le bloquant ou en le débloquant",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="john.doe@example.com ou 75000000"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compte utilisateur bloqué ou débloqué"),
     *             @OA\Property(property="data", type="object")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function changeBlockStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        $user = User::where('email', $request->email)->first();

            if ($user) {

                $user->update([
                    'is_blocked' => !$user->is_blocked,
                ]);
                $msg = $user->is_blocked ? "Compte utilisateur bloqué" : "Compte utilisateur débloqué";
                return response()->json(['message' => $msg, "data" => $user], 200);

            } else {
                // Réponse d'erreur
                return response()->json(['error' => "Les données fournies ne sont pas valides."], 422);
            }

        return $response;

    }

    public function mail_number_matricule_validate ($attribute, $value, $fail) {
        // Vérification pour email
        if (!filter_var($value, FILTER_VALIDATE_EMAIL) &&
            // Vérification pour numéro de téléphone à 8 chiffres
            !preg_match('/^[0-9]{8}$/', $value) &&
            // Vérification pour chaîne de 8 caractères maximum (sans le séparateur '-')
            !preg_match('/^[A-Z]{3}-[0-9]{2}-[0-9]{4}$/', $value)) {
            $fail('Le ' . $attribute . ' doit être un email valide, un numéro de téléphone à 8 chiffres, ou un numéro matricule de type XXX-XX-XXXX.');
        }
    }

}
