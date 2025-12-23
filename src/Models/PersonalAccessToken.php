<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class PersonalAccessToken
{
    /**
     * Cria um token para o utilizador.
     * Gera uma string aleatória, guarda no banco e devolve-a.
     */
    public static function create(int $userId, string $name = 'default'): string
    {
        $pdo = Database::getConnection();

        // 1. Gerar um token seguro (64 caracteres hexadecimais)
        // random_bytes é criptograficamente seguro em PHP 7+
        $token = bin2hex(random_bytes(32));

        // 2. Guardar no banco
        $sql = "INSERT INTO personal_access_tokens (user_id, token, name) VALUES (:user_id, :token, :name)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':token'   => $token,
            ':name'    => $name
        ]);

        // 3. Devolver o token "limpo" para o utilizador guardar
        return $token;
    }

    /**
     * Valida se um token existe e devolve o dono dele.
     * Usaremos isto mais tarde no Middleware.
     */
    public static function findUserByToken(string $token): ?array
    {
        $pdo = Database::getConnection();

        // Fazemos um JOIN para já buscar os dados do utilizador dono do token
        $sql = "SELECT u.id, u.name, u.email, u.role 
                FROM personal_access_tokens t
                JOIN users u ON u.id = t.user_id
                WHERE t.token = :token";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':token' => $token]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
