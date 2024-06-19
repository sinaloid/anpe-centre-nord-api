<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

 /**
     * @OA\Info(
     *      version="1.0.0",
     *      title="ANPE CENTRE NORD API",
     *      description="Bienvenue dans la documentation de l'API de l'ANPE CENTRE NORD.<br>Cette API fournit un accès sécurisé aux fonctionnalités du backend, <br>permettant la gestion efficace des ressources liées à l'emploi, aux utilisateurs et aux données associées.",
     *      @OA\Contact(
     *          name="SYMBOL SARL",
     *          email="admin@admin.com",
     *      ),
     * )
     *    @OA\SecurityScheme(
    *     type="http",
    *     securityScheme="bearerAuth",
    *     scheme="bearer",
    *     bearerFormat="JWT"
    * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST,
     *      description="Serveur test de l'API"
     * )

     *
     */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
}
