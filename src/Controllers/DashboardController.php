<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use App\Models\ContentFeed;
use App\Models\UserInterest;
use PDO;
use OpenApi\Attributes as OA;

class DashboardController
{
    #[OA\Get(
        path: '/dashboard',
        tags: ['Usuário'],
        summary: 'Retorna o feed do dashboard',
        description: 'Feed personalizado. Usa estratégia **"hybrid_recommendation"** (Histórico + Interesses) ou **"latest_fallback"** (Recentes) se não houver dados.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Feed gerado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'strategy', type: 'string', example: 'hybrid_recommendation'),
                        new OA\Property(property: 'sources', type: 'object', properties: [
                            new OA\Property(property: 'history_category', type: 'integer', description: 'ID da categoria mais visitada', nullable: true),
                            new OA\Property(property: 'interest_count', type: 'integer', description: 'Qtd de categorias seguidas')
                        ]),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'title', type: 'string'),
                                    new OA\Property(property: 'excerpt', type: 'string'),
                                    new OA\Property(property: 'date', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'content_type', type: 'string', enum: ['article', 'interview']),
                                    new OA\Property(property: 'category_name', type: 'string'),
                                    new OA\Property(property: 'category_slug', type: 'string')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autorizado')
        ]
    )]
    public function index()
    {
        $user = $_REQUEST['user'];
        $pdo = Database::getConnection();

        // 1. Obter a Categoria mais visitada (Histórico)
        $stmt = $pdo->prepare("
            SELECT category_id, COUNT(*) as total
            FROM user_history
            WHERE user_id = ?
            GROUP BY category_id
            ORDER BY total DESC
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $topHistory = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Obter Categorias de Interesse (Interesses Explícitos)
        $userInterests = UserInterest::listByUserId($user['id']);
        $interestIds = array_column($userInterests, 'id');

        // 3. Fundir as duas fontes
        $mergedIds = $interestIds;
        if ($topHistory) {
            $mergedIds[] = $topHistory['category_id'];
        }

        $allCategoryIds = array_values(array_unique($mergedIds));

        $data = [];
        $strategy = '';

        // 4. Decidir a Estratégia
        if (!empty($allCategoryIds)) {
            $strategy = 'hybrid_recommendation';
            $data = ContentFeed::getFeedByCategories($allCategoryIds);
        } else {
            $strategy = 'latest_fallback';
            $data = ContentFeed::getGeneralFeed();
        }

        // 5. Retornar Resposta
        AppHelper::sendResponse(200, [
            'strategy' => $strategy,
            'sources' => [
                'history_category' => $topHistory['category_id'] ?? null,
                'interest_count' => count($interestIds)
            ],
            'data' => $data
        ]);
    }
}
