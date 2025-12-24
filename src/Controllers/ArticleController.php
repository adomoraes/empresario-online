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

    /**
     * GET /article?id=1
     * Lê um artigo e grava no histórico.
     */
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID necessário']);
            return;
        }

        // 1. Buscar Artigo
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$article) {
            http_response_code(404);
            echo json_encode(['error' => 'Artigo não encontrado']);
            return;
        }

        // 2. Gravar Histórico (Se estiver logado)
        if (isset($_REQUEST['user'])) {
            \App\Models\UserHistory::record(
                $_REQUEST['user']['id'],
                $article['category_id'],
                'article',
                $id
            );
        }

        echo json_encode(['data' => $article]);
    }
}
