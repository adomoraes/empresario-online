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

        // 1. Transliterar (converte 'Inteligência' para 'Inteligencia')
        $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT', $data['name']);

        // 2. Gerar Slug (converte para minusculas e remove caracteres estranhos)
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cleanName), '-'));
        // ---------------------

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");

        try {
            $stmt->execute([$data['name'], $slug]);
            AppHelper::sendResponse(201, ['message' => 'Categoria criada', 'id' => $pdo->lastInsertId()]);
        } catch (\App\Config\HttpResponseException $e) {
            // Se for a nossa exceção de "Teste Terminado com Sucesso", deixamo-la passar!
            throw $e;
        } catch (\Exception $e) {
            // Qualquer outra exceção (ex: duplicado no banco) vira erro 400
            AppHelper::sendResponse(400, ['error' => 'Erro ao criar categoria (possível duplicado)']);
        }
    }

    #[OA\Put(
        path: '/categories',
        tags: ['Admin'],
        summary: 'Atualiza uma categoria',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id', 'name'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string')
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Atualizado')]
    )]
    public function update()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['id']) || empty($data['name'])) {
            AppHelper::sendResponse(400, ['error' => 'ID e Nome obrigatórios']);
            return;
        }

        if (\App\Models\Category::update($data['id'], $data['name'])) {
            AppHelper::sendResponse(200, ['message' => 'Categoria atualizada']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao atualizar']);
        }
    }

    #[OA\Delete(
        path: '/categories',
        tags: ['Admin'],
        summary: 'Remove uma categoria',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'integer')])),
        responses: [new OA\Response(response: 200, description: 'Removido')]
    )]
    public function destroy()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        // Podes adicionar verificação se existem artigos nesta categoria antes de apagar, 
        // mas o banco está com SET NULL, então é seguro.
        if (\App\Models\Category::delete($data['id'])) {
            AppHelper::sendResponse(200, ['message' => 'Categoria removida']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao remover']);
        }
    }
}
