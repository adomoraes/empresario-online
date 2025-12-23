<?php

namespace App\Controllers;

use App\Models\Category;

class CategoryController
{
    public function index()
    {
        echo json_encode(['data' => Category::all()]);
    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome da categoria obrigatório']);
            return;
        }

        $id = Category::create($data['name']);
        http_response_code(201);
        echo json_encode(['message' => 'Categoria criada', 'id' => $id]);
    }
}
