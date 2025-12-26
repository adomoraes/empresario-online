<?php

namespace App\Config;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Empresário Online API (Premium)',
    description: "API do portal Empresário Online.\n\n**Modelo de Acesso:**\n* **Público:** Apenas Login, Registo e Documentação.\n* **Premium:** Artigos, Entrevistas e Dashboard exigem autenticação (`Bearer Token`).\n* **Admin:** Gestão de conteúdo e utilizadores requer role `admin`.",
    contact: new OA\Contact(name: 'Suporte', email: 'suporte@empresario.online')
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Servidor Local (Docker)'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Insira o token JWT recebido no login.'
)]
class SwaggerConfig
{
    // Classe vazia para alojar anotações globais
}
