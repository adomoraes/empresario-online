<?php

namespace App\Controllers;

use App\Models\ContentFeed;

class DashboardController
{

    public function index()
    {
        $user = $_REQUEST['user'];

        // 1. Capturar paginação da URL (ex: ?page=2&limit=5)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        // 2. Tentar obter Feed Personalizado
        $feed = ContentFeed::getPersonalizedFeed($user['id'], $page, $limit);
        $isPersonalized = true;

        // 3. Lógica de Fallback (Se a lista vier vazia E for a primeira página)
        // Isso significa que o user não segue nada ou as categorias que segue não têm conteúdo.
        if (empty($feed) && $page === 1) {
            $feed = ContentFeed::getGeneralFeed(1, $limit); // Traz as últimas do sistema
            $isPersonalized = false;
        }

        // 4. Montar Resposta Refinada
        echo json_encode([
            'meta' => [
                'user' => $user['name'],
                'page' => $page,
                'limit' => $limit,
                'is_personalized' => $isPersonalized, // Frontend pode usar isto para mostrar aviso
            ],
            'message' => $isPersonalized
                ? 'Aqui está o seu feed baseado nos seus interesses.'
                : 'Ainda não segue interesses suficientes. Aqui estão as últimas novidades para si!',
            'data' => $feed
        ]);
    }
}
