<?php

namespace App\Controllers;

use App\Models\AccessLog;

class LogController
{
    public function index()
    {
        $logs = AccessLog::all();
        echo json_encode(['data' => $logs]);
    }

    /**
     * DELETE /admin/logs
     * Limpa todo o histórico de logs.
     */
    public function clear()
    {
        try {
            \App\Models\AccessLog::clearAll();
            echo json_encode(['message' => 'Todos os logs de acesso foram apagados.']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao limpar logs.']);
        }
    }
}
