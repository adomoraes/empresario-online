<?php

namespace App\Controllers;

use App\Models\UserInterest;

class InterestController
{

    // GET /interests -> Ver meus interesses
    public function index()
    {
        $user = $_REQUEST['user'];
        $interests = UserInterest::listByUserId($user['id']);
        echo json_encode(['data' => $interests]);
    }

    // POST /interests -> Adicionar interesse
    public function store()
    {
        $user = $_REQUEST['user'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['category_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da categoria obrigatório']);
            return;
        }

        UserInterest::add($user['id'], $data['category_id']);
        http_response_code(201);
        echo json_encode(['message' => 'Interesse adicionado com sucesso!']);
    }

    // DELETE /interests -> Remover interesse
    public function delete()
    {
        $user = $_REQUEST['user'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['category_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da categoria obrigatório']);
            return;
        }

        UserInterest::remove($user['id'], $data['category_id']);
        echo json_encode(['message' => 'Interesse removido.']);
    }
}
