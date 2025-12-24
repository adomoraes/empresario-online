<?php

namespace App\Controllers;

use App\Config\AppHelper;
use App\Models\ContentFeed;
use App\Models\UserInterest;
use App\Models\UserHistory;

class DashboardController
{

    public function index()
    {
        $user = $_REQUEST['user'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        // 1. Interesses Explícitos (O que ele SEGUE)
        // Precisamos ajustar o UserInterest para devolver só IDs
        $explicitInterests = array_column(UserInterest::listByUserId($user['id']), 'id');

        // 2. Interesses Implícitos (O que ele VÊ no histórico)
        $implicitInterests = UserHistory::getImplicitInterestCategories($user['id']);

        // 3. Fusão Inteligente
        // Juntamos os dois arrays e removemos duplicados
        $allCategoryIds = array_unique(array_merge($explicitInterests, $implicitInterests));

        // 4. Buscar Feed
        $feed = [];
        $isPersonalized = false;
        $source = 'General';

        if (!empty($allCategoryIds)) {
            $feed = ContentFeed::getFeedByCategories($allCategoryIds, $page, $limit);
            $isPersonalized = true;
            $source = 'Interest + History';
        }

        // 5. Fallback (Se a fusão não der resultados, ex: user novo sem histórico nem follow)
        if (empty($feed) && $page === 1) {
            $feed = ContentFeed::getGeneralFeed(1, $limit);
            $isPersonalized = false;
        }

        AppHelper::sendResponse(200, [
            'meta' => [
                'user' => $user['name'],
                'personalized' => $isPersonalized,
                'algorithm_source' => $source, // Debug: diz de onde veio a recomendação
                'categories_used' => array_values($allCategoryIds) // Debug: IDs usados
            ],
            'data' => $feed
        ]);
    }
}
