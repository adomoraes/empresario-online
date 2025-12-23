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
}
