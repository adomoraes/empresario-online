<?php

namespace App\Controllers;

use App\Models\Category;
use App\Config\AppHelper;
use App\Config\HttpResponseException;
use App\Config\Database;
use OpenApi\Attributes as OA;

class CategoryController
{
    #[OA\Get(
        path: '/categories',
        tags: ['Conteúdo (Premium)'],
        summary: 'Lista todas as categorias',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de categorias')
        ]
    )]
    public function index()
    {
        $categories = Category::all();
        AppHelper::sendResponse(200, ['data' => $categories]);
    }

    #[OA\Post(
        path: '/categories',
        tags: ['Admin'],
        summary: 'Criar Categoria',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(required: ['name'], properties: [new OA\Property(property: 'name', type: 'string')])),
        responses: [new OA\Response(response: 201, description: 'Criado')]
    )]
    public function store()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['name'])) {
            AppHelper::sendResponse(400, ['error' => 'Nome obrigatório']);
            return;
        }

        // Translitera para remover acentos (ex: Inteligência -> Inteligencia)
        $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT', $data['name']);
        // Gera slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cleanName), '-'));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");

        try {
            $stmt->execute([$data['name'], $slug]);
            AppHelper::sendResponse(201, ['message' => 'Categoria criada', 'id' => $pdo->lastInsertId()]);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            AppHelper::sendResponse(400, ['error' => 'Erro ao criar categoria']);
        }
    }

    #[OA\Put(
        path: '/categories',
        tags: ['Admin'],
        summary: 'Atualizar Categoria',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(required: ['id', 'name'], properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'name', type: 'string')])),
        responses: [new OA\Response(response: 200, description: 'Atualizado')]
    )]
    public function update()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['id']) || empty($data['name'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos']);
            return;
        }

        // Update simples (slug fixo para simplificar ou regenerar se quiser)
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['id']]);

        AppHelper::sendResponse(200, ['message' => 'Categoria atualizada']);
    }

    #[OA\Delete(
        path: '/categories',
        tags: ['Admin'],
        summary: 'Apagar Categoria',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'integer')])),
        responses: [new OA\Response(response: 200, description: 'Apagado')]
    )]
    public function destroy()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$data['id']]);

        AppHelper::sendResponse(200, ['message' => 'Categoria removida']);
    }
}
