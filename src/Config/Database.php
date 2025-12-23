<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    // Variável estática para guardar a conexão
    private static ?PDO $pdo = null;

    /**
     * Retorna a instância da conexão PDO.
     * Se não existir, cria uma nova.
     */
    public static function getConnection(): PDO
    {
        // Se já existe uma conexão ativa, devolve-a (Singleton)
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            // Lemos as configurações do ambiente (Docker)
            $host = getenv('MYSQL_HOST') ?: 'db'; // 'db' é o nome do serviço no docker-compose
            $db   = getenv('MYSQL_DATABASE') ?: 'meu_banco';
            $user = getenv('MYSQL_USER') ?: 'user';
            $pass = getenv('MYSQL_PASSWORD') ?: 'password';
            $port = getenv('MYSQL_PORT') ?: '3306';

            // DSN (Data Source Name) é a string de conexão
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

            // Opções para o PDO
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança erros se o SQL falhar
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolve arrays associativos ['nome' => 'Joao']
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa prepared statements reais (Segurança)
            ];

            // Criação da conexão e armazenamento na variável estática
            self::$pdo = new PDO($dsn, $user, $pass, $options);

            return self::$pdo;
        } catch (PDOException $e) {
            // Em produção não devemos mostrar o erro detalhado ao utilizador, mas para dev ajuda
            throw new PDOException("Erro na conexão com a base de dados: " . $e->getMessage());
        }
    }
}
