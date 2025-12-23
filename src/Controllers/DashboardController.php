<?php

namespace App\Controllers;

use App\Models\ContentFeed;

class DashboardController
{

    public function index()
    {
        $user = $_REQUEST['user'];

        // Agora usamos a classe especializada em misturar conteúdos
        $feed = ContentFeed::getPersonalizedFeed($user['id']);

        echo json_encode([
            'user' => $user['name'],
            'dashboard_type' => 'Feed Personalizado (Artigos + Entrevistas)',
            'data' => $feed
        ]);
    }
}
