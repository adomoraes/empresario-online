<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use PDO;

class CategoryController
{
    public function index()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM categories");
        AppHelper::sendResponse(200, ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

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
