<?php

namespace App\Controllers;

use App\Models\AccessLog;
use App\Config\AppHelper;

class LogController
{
    public function index()
    {
        $logs = AccessLog::all();
        AppHelper::sendResponse(200, ['data' => $logs]);
    }

    /**
     * DELETE /admin/logs
     * Limpa todo o histórico de logs.
     */
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
