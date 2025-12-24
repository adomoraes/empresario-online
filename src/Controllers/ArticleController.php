<?php

namespace App\Controllers;

use App\Models\Article;
use App\Config\AppHelper;

class ArticleController
{

    // GET /articles (Admin lista tudo)
    public function index()
    {
        $articles = Article::all();
        AppHelper::sendResponse(200, ['data' => $articles]);
    }

    // POST /articles (Admin cria)
    public function store()
    {
        $user = $_REQUEST['user']; // O Admin logado (Autor)
        $data = AppHelper::getJsonInput();

        if (empty($data['title']) || empty($data['content']) || empty($data['category_id'])) {
            AppHelper::sendResponse(400, ['error' => 'Título, conteúdo e categoria são obrigatórios.']);
        }

        try {
            $id = Article::create(
                $user['id'],
                $data['category_id'],
                $data['title'],
                $data['content']
            );

            AppHelper::sendResponse(201, ['message' => 'Artigo publicado!', 'id' => $id]);
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro ao criar artigo.']);
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
            AppHelper::sendResponse(400, ['error' => 'ID necessário']);
        }

        // 1. Buscar Artigo
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$article) {
            AppHelper::sendResponse(404, ['error' => 'Artigo não encontrado']);
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

        AppHelper::sendResponse(200, ['data' => $article]);
    }
}
