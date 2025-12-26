<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    // Propriedades do Usuário (igual às colunas do banco)
    public int $id;
    public string $name;
    public string $email;
    public string $password;
    public string $role;
    public ?string $created_at;

    /**
     * Cria um novo usuário no banco de dados.
     * Substitui o: User::create([...])
     */
    public static function create(string $name, string $email, string $password, string $role = 'user'): int
    {
        $pdo = Database::getConnection();

        // SQL Seguro com Prepared Statements
        $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':password' => $password, // Lembrete: A senha já deve vir encriptada (Hash)
            ':role'     => $role
        ]);

        // Retorna o ID do usuário criado
        return (int) $pdo->lastInsertId();
    }

    /**
     * Procura um usuário pelo Email.
     * Útil para o Login.
     * Substitui o: User::where('email', $email)->first()
     */
    public static function findByEmail(string $email): ?self
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        // Mapeia o resultado diretamente para esta classe (User)
        // Se não encontrar, retorna false (que tratamos como null)
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Procura por ID
     * Substitui o: User::find($id)
     */
    public static function find(int $id): ?self
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function all(): array
    {
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza dados de QUALQUER usuário (Admin mode).
     * Permite mudar inclusive a ROLE.
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = \App\Config\Database::getConnection();

        // Construção dinâmica da query para atualizar apenas o que foi enviado
        $fields = [];
        $params = [];

        if (!empty($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (!empty($data['role'])) { // <--- O poder de promover users
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }

        // Se não houver campos para atualizar, retorna
        if (empty($fields)) {
            return false;
        }

        $params[] = $id; // ID vai no final para o WHERE
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Remove um usuário pelo ID.
     */
    public static function delete(int $id): bool
    {
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
