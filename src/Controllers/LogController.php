<?php

namespace App\Controllers;

use App\Models\AccessLog;
use App\Config\AppHelper;
use OpenApi\Attributes as OA;

class LogController
{
    #[OA\Get(
        path: '/admin/logs',
        tags: ['Admin'],
        summary: 'Ver histórico de acessos',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de logs',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items())
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $logs = AccessLog::all();
        AppHelper::sendResponse(200, ['data' => $logs]);
    }

    /**
     * DELETE /admin/logs
     * Limpa todo o histórico de logs.
     */
    #[OA\Delete(
        path: '/admin/logs',
        tags: ['Admin'],
        summary: 'Limpar histórico de logs',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logs apagados')
        ]
    )]
    public function clear()
    {
        try {
            \App\Models\AccessLog::clearAll();
            AppHelper::sendResponse(200, ['message' => 'Todos os logs de acesso foram apagados.']);
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro ao limpar logs.']);
        }
    }
}
