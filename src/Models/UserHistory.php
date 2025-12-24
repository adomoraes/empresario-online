<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class UserHistory
{
    public static function record(int $userId, int $categoryId, string $contentType, int $contentId)
    {
        $pdo = Database::getConnection();

        // Evitar duplicados recentes (opcional, mas boa prática)
        // Aqui vamos simplificar e gravar sempre
        $stmt = $pdo->prepare("
            INSERT INTO user_history (user_id, category_id, content_type, content_id, accessed_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$userId, $categoryId, $contentType, $contentId]);
    }
}
