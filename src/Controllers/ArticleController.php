<?php

namespace App\Controllers;

use App\Models\Article;

class ArticleController
{

    // GET /articles (Admin lista tudo)
    public function index()
    {
        $articles = Article::all();
        echo json_encode(['data' => $articles]);
    }

    // POST /articles (Admin cria)
    public function store()
    {
        $user = $_REQUEST['user']; // O Admin logado (Autor)
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['content']) || empty($data['category_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Título, conteúdo e categoria são obrigatórios.']);
            return;
        }

        try {
            $id = Article::create(
                $user['id'],
                $data['category_id'],
                $data['title'],
                $data['content']
            );

            http_response_code(201);
            echo json_encode(['message' => 'Artigo publicado!', 'id' => $id]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao criar artigo.']);
        }
    }
}
