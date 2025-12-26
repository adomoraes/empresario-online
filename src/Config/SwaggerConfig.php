<?php

namespace App\Config;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Empresário Online API",
    description: "Documentação da API RESTful desenvolvida em PHP Puro.",
    contact: new OA\Contact(email: "admin@teste.com")
)]
#[OA\Server(
    url: "http://localhost:8080",
    description: "Servidor Local Docker"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class SwaggerConfig {}
