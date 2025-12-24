<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use PDO;

class DashboardController
{
    public function index()
    {
        // O AuthMiddleware já garantiu que o user existe
        $user = $_REQUEST['user'];
        $pdo = Database::getConnection();

        // 1. Descobrir a Categoria Favorita
        $stmt = $pdo->prepare("
            SELECT category_id, COUNT(*) as total
            FROM user_history
            WHERE user_id = ?
            GROUP BY category_id
            ORDER BY total DESC
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $favorite = $stmt->fetch(PDO::FETCH_ASSOC);

        $data = [];
        $strategy = '';

        if ($favorite) {
            // --- ESTRATÉGIA A: RECOMENDAÇÃO ---
            $strategy = 'recommendation';
            $catId = $favorite['category_id'];

            // Busca Artigos dessa categoria
            $stmtArt = $pdo->prepare("
                SELECT id, title, 'article' as type, created_at 
                FROM articles 
                WHERE category_id = ? 
                ORDER BY created_at DESC LIMIT 5
            ");
            $stmtArt->execute([$catId]);
            $articles = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

            // Busca Entrevistas dessa categoria (via tabela pivot)
            $stmtInt = $pdo->prepare("
                SELECT i.id, i.title, 'interview' as type, i.published_at as created_at
                FROM interviews i
                JOIN interview_categories ic ON i.id = ic.interview_id
                WHERE ic.category_id = ?
                ORDER BY i.published_at DESC LIMIT 5
            ");
            $stmtInt->execute([$catId]);
            $interviews = $stmtInt->fetchAll(PDO::FETCH_ASSOC);

            // Junta tudo
            $data = array_merge($articles, $interviews);
        } else {
            // --- ESTRATÉGIA B: MAIS RECENTES (FALLBACK) ---
            $strategy = 'latest';

            // Últimos 5 artigos gerais
            $stmt = $pdo->query("SELECT id, title, 'article' as type, created_at FROM articles ORDER BY created_at DESC LIMIT 5");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        AppHelper::sendResponse(200, [
            'strategy' => $strategy,
            'top_category' => $favorite['category_id'] ?? null,
            'data' => $data
        ]);
    }
}
