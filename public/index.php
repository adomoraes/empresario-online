<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;

header('Content-Type: application/json');

try {
    // 1. Simular dados de um novo utilizador
    $email = 'teste_' . time() . '@exemplo.com'; // Email único para não dar erro
    $senhaSegura = password_hash('minha_senha_secreta', PASSWORD_DEFAULT); // Hash do PHP

    // 2. Tentar criar usando o nosso novo Model
    $novoId = User::create('Utilizador Teste', $email, $senhaSegura);

    // 3. Buscar o utilizador que acabámos de criar
    $usuario = User::find($novoId);

    echo json_encode([
        'status' => 'success',
        'message' => 'Utilizador criado com PHP Nativo!',
        'data' => $usuario
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
