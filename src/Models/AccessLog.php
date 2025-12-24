<?php

namespace App\Models;

use App\Config\Database;

class AccessLog
{
    public static function create(?int $userId, string $method, string $route, string $ip)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO access_logs (user_id, method, route, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $method, $route, $ip]);
    }

    // Para o Admin listar
    public static function all(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM access_logs ORDER BY created_at DESC LIMIT 100")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Apaga todos os logs do sistema (Limpeza total).
     */
    public static function clearAll(): void
    {
        $pdo = \App\Config\Database::getConnection();
        $pdo->exec("TRUNCATE TABLE access_logs");
    }
}
