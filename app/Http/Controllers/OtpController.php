<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\SendMail;
use App\Models\User;

class OtpController extends Controller
{
    public function generateOTP(Request $request){

        //$key = array_key_first($request->all());
        return $this->generateEmailOTP($request);
        /*if($key === "email"){
            return $this->generateEmailOTP($request);
        }elseif($key === "number"){
            return $this->generateNumberOTP($request);
        }else{
            return response(['errors' => "Un email ou un numéro de téléphone est obligatire"], 422);
        }*/
    }

    public function generateOTPForPasswordEdit(Request $request){

        $key = array_key_first($request->all());
        $user = User::where($key,$request[$key])->first();

        if($key === "email" && $user){
            return $this->generateEmailOTP($request);
        }elseif($key === "number" && $user){
            return $this->generateNumberOTP($request);
        }else{
            $message = ( $key === "number") ? "Votre numéro de téléphone est incorrect" : "Votre email est incorrect";

            return response(['errors' => $message], 422);
        }
    }

    public function verifyOTP(Request $request){

        return $this->verifyEmailOTP($request);

        /*$key = array_key_first($request->all());
        if($key === "email"){
            return $this->verifyEmailOTP($request);
        }elseif($key === "number"){
            return $this->verifyNumberOTP($request);
        }else{
            return response(['errors' => "Un email ou un numéro de téléphone est obligatire"], 422);
        }*/
    }

    public function generateEmailOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // Génération du code OTP
        $otpCode = rand(100000, 999999);
        $otp = Otp::where('email', $request->email)->first();

        if ($otp) {
            $otp->update([
                'is_verified' => false,
                'code' => $otpCode,
                'expires_at' => now()->addMinutes(10)
            ]);
        } else {
            $otp = Otp::create([
                'email' => $request->email,
                'code' => $otpCode,
                'is_verified' => false,
                'expires_at' => now()->addMinutes(10), // Expiration du code après 10 minutes
            ]);
        }

        // Envoyer le code OTP à l'utilisateur (par e-mail, SMS, etc.)
        //$this->sendEmail($request->email,$otpCode);
        // Réponse de succès
        return response()->json(['message' => 'Le code OTP a été généré et envoyé avec succès à votre adresse e-mail','code' => $otp->code]);
    }

    /*public function generateNumberOTP(Request $request)
    {
        $otp = null;
        $validator = Validator::make($request->all(), [
            'number' => 'required|integer|digits:8|starts_with:5,6,7,01,02,03,05,06,07',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }


        // Génération du code OTP
        $otpCode = rand(1000, 9999);
        $otp = Otp::where('number', $request->number)->first();

        if ($otp) {
            $otp->update([
                'is_verified' => false,
                'code' => $otpCode,
                'expires_at' => now()->addMinutes(5)
            ]);
        } else {
            Otp::create([
                'number' => $request->number,
                'code' => $otpCode,
                'is_verified' => false,
                'expires_at' => now()->addMinutes(5), // Expiration du code après 10 minutes
            ]);
        }


        // Envoyer le code OTP à l'utilisateur (par e-mail, SMS, etc.)
        $this->sendEmail("sinaloid@gmail.com",$otpCode);
        // Réponse de succès
        return response()->json(['message' => 'Le code OTP a été généré et envoyé avec succès sur votre numéro de téléphone']);
    }*/


    public function verifyEmailOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'otp' => 'required|string|max:6',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        // Vérification du code OTP
        $otp = Otp::where('email', $request->email)
            ->where('code', $request->input('otp'))
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->first();
        if ($otp) {
            // Marquer le code OTP comme vérifié
            $otp->update(['is_verified' => true]);

            // Réponse de succès
            //return true;
            return response()->json(['message' => 'Code OTP vérifié avec succès',"data" => $otp],200);
        } else {
            // Réponse d'erreur
            return response()->json(['error' => 'Code OTP invalide ou expiré'], 422);
        }
    }

    /*public function verifyNumberOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|integer|digits:8|starts_with:5,6,7,01,02,03,05,06,07',
            'otp' => 'required|integer|digits:4',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        // Vérification du code OTP
        $otp = Otp::where('number', $request->number)
            ->where('code', $request->input('otp'))
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->first();
        if ($otp) {
            // Marquer le code OTP comme vérifié
            $otp->update(['is_verified' => true]);

            // Réponse de succès
            return response()->json(['message' => 'Code OTP vérifié avec succès']);
        } else {
            // Réponse d'erreur
            return response()->json(['error' => 'Code OTP invalide ou expiré'], 422);
        }
    }*/

    public function sendEmail($email,$otp)
    {
      $data = [
        'subject' => "Vérification de votre compte",
        'title' => "Bienvenue sur l'ANPE CENTRE NORD !",
        'content' => 'Pour poursuivre la création de votre compte, veuillez vérifier votre adresse e-mail à l’aide du code
        suivant: '.$otp
      ];

      Mail::to($email)->send(new SendMail($data, null));

      return "E-mail envoyé avec succès.";
    }

}
