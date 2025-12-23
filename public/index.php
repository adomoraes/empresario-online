<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

header('Content-Type: application/json');

try {
    // Tentamos obter a conexão usando a nossa classe
    $pdo = Database::getConnection();

    echo json_encode([
        'status' => 'success',
        'message' => 'Conexão PDO estabelecida com sucesso através da classe Database!',
        'database_driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
    ]);
} catch (Exception $e) {
    // Se falhar, devolvemos erro 500 e a mensagem JSON
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
