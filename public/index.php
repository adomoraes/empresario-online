<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\UserController;

// Configurar cabeçalhos para aceitar JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (CORS básico)

// --- SIMULAÇÃO DE ROTEAMENTO ---
// Vamos verificar se o método é POST. Se for, chamamos o controlador.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new UserController();
    $controller->register();
    exit; // Termina a execução aqui para não mostrar mais nada
}

// Se não for POST (ex: abrires no navegador), mostra mensagem padrão
echo json_encode(['message' => 'API pronta. Use o Postman com POST para testar o registo.']);
