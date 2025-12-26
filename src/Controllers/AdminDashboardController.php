<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use PDO;
use OpenApi\Attributes as OA;

class AdminDashboardController
{
    /**
     * Retorna estatísticas gerais do sistema para o Admin.
     */
    #[OA\Get(
        path: '/admin/dashboard',
        tags: ['Admin'],
        summary: 'Estatísticas do sistema',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estatísticas recuperadas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'stats', type: 'object', properties: [
                            new OA\Property(property: 'total_users', type: 'integer'),
                            new OA\Property(property: 'total_articles', type: 'integer'),
                            new OA\Property(property: 'total_interviews', type: 'integer'),
                        ]),
                        new OA\Property(property: 'recent_logs', type: 'array', items: new OA\Items())
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Acesso negado')
        ]
    )]
    public function index()
    {
        $pdo = Database::getConnection();

        // 1. Contagens (Stats)
        // Podemos fazer queries individuais ou uma união. Individuais são mais legíveis.

        $stats = [];

        // Total de Utilizadores
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Total de Artigos
        $stats['total_articles'] = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();

        // Total de Entrevistas
        $stats['total_interviews'] = $pdo->query("SELECT COUNT(*) FROM interviews")->fetchColumn();

        // 2. Logs Recentes (Últimos 5 acessos ao sistema)
        // Trazemos também o nome do user se existir
        $sqlLogs = "
            SELECT l.method, l.route, l.ip_address, l.created_at, u.name as user_name
            FROM access_logs l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC
            LIMIT 5
        ";
        $recentLogs = $pdo->query($sqlLogs)->fetchAll(PDO::FETCH_ASSOC);

        AppHelper::sendResponse(200, [
            'message' => 'Dashboard Administrativo',
            'stats' => $stats,
            'recent_logs' => $recentLogs
        ]);
    }
}
