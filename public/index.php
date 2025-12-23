<?php

echo "<h1>Ambiente Docker Configurado!</h1>";
echo "<p>PHP Puro a correr na versão: " . phpversion() . "</p>";

// Teste rápido de conexão (apenas para validar o Docker)
try {
    $pdo = new PDO('mysql:host=db;dbname=meu_banco', 'user', 'password');
    echo "<p>Conexão ao MySQL: <strong>Sucesso!</strong></p>";
} catch (PDOException $e) {
    echo "<p>Erro ao conectar ao MySQL: " . $e->getMessage() . "</p>";
}
