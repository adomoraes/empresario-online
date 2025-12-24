<?php

namespace App\Controllers;

use App\Models\Category;
use App\Config\AppHelper;

class CategoryController
{
    public function index()
    {
        AppHelper::sendResponse(200, ['data' => Category::all()]);
    }

    public function store()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['name'])) {
            AppHelper::sendResponse(400, ['error' => 'Nome da categoria obrigatório']);
        }

        $id = Category::create($data['name']);
        AppHelper::sendResponse(201, ['message' => 'Categoria criada', 'id' => $id]);
    }
}
