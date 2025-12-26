<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use PDO;

use OpenApi\Attributes as OA;

class CategoryController
{
    public function index()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM categories");
        AppHelper::sendResponse(200, ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    #[OA\Post(
        path: '/categories',
        tags: ['Admin'],
        summary: 'Cria uma nova categoria',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nova Categoria')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Categoria criada'),
            new OA\Response(response: 400, description: 'Nome é obrigatório ou categoria já existe'),
            new OA\Response(response: 401, description: 'Não autorizado')
        ]
    )]
    public function store()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['name'])) {
            AppHelper::sendResponse(400, ['error' => 'Nome obrigatório']);
            return;
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");

        try {
            $stmt->execute([$data['name'], $slug]);
            AppHelper::sendResponse(201, ['message' => 'Categoria criada', 'id' => $pdo->lastInsertId()]);
        } catch (\Exception $e) {
            AppHelper::sendResponse(400, ['error' => 'Erro ao criar categoria (possível duplicado)']);
        }
    }
}
