<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    // Propriedades do Utilizador (igual às colunas do banco)
    public int $id;
    public string $name;
    public string $email;
    public string $password;
    public string $role;
    public ?string $created_at;

    /**
     * Cria um novo utilizador no banco de dados.
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

        // Retorna o ID do utilizador criado
        return (int) $pdo->lastInsertId();
    }

    /**
     * Procura um utilizador pelo Email.
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
}
