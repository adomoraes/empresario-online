<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper; // <--- Importante
use PDO;

class ArticleController
{
    public function index()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, title, content FROM articles ORDER BY created_at DESC");
        AppHelper::sendResponse(200, ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function show()
    {
        // 1. Validação básica do ID
        $id = $_GET['id'] ?? null;
        if (!$id) {
            AppHelper::sendResponse(400, ['error' => 'ID necessário']);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article) {
            AppHelper::sendResponse(404, ['error' => 'Artigo não encontrado']);
            return;
        }

        // 2. Auth Opcional (Silent Auth)
        // Se o utilizador não veio de um middleware, tentamos identificá-lo agora
        if (!isset($_REQUEST['user'])) {
            $this->tryIdentifyUser();
        }

        // 3. Gravar Histórico (Agora o UserHistory existe!)
        if (isset($_REQUEST['user'])) {
            \App\Models\UserHistory::record(
                $_REQUEST['user']['id'],
                $article['category_id'] ?? 0,
                'article',
                $id
            );
        }

        AppHelper::sendResponse(200, ['data' => $article]);
    }

    // Método auxiliar privado para tentar ler o token sem bloquear
    private function tryIdentifyUser()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.role 
                FROM personal_access_tokens t
                JOIN users u ON t.user_id = u.id
                WHERE t.token = ?
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_REQUEST['user'] = $user;
            }
        }
    }

    public function store()
    {
        $data = AppHelper::getJsonInput(); // <--- Para funcionar no PHPUnit

        if (empty($data['title']) || empty($data['content'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos']);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO articles (title, content, category_id, user_id) VALUES (?, ?, ?, ?)");

        // Assume que o middleware de Admin já validou o user e ele está em $_REQUEST['user']
        // Se for um teste sem middleware, podemos precisar de um fallback, mas o teste cuidará disso.
        $userId = $_REQUEST['user']['id'] ?? 1;

        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['category_id'] ?? null,
            $userId
        ]);

        AppHelper::sendResponse(201, ['message' => 'Artigo criado', 'id' => $pdo->lastInsertId()]);
    }
}
