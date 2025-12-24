<?php

namespace App\Controllers;

use App\Models\UserInterest;
use App\Config\AppHelper;

class InterestController
{

    // GET /interests -> Ver meus interesses
    public function index()
    {
        $user = $_REQUEST['user'];
        $interests = UserInterest::listByUserId($user['id']);
        AppHelper::sendResponse(200, ['data' => $interests]);
    }

    // POST /interests -> Adicionar interesse
    public function store()
    {
        $user = $_REQUEST['user'];
        $data = AppHelper::getJsonInput();

        if (empty($data['category_id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID da categoria obrigatório']);
        }

        UserInterest::add($user['id'], $data['category_id']);
        AppHelper::sendResponse(201, ['message' => 'Interesse adicionado com sucesso!']);
    }

    // DELETE /interests -> Remover interesse
    public function delete()
    {
        $user = $_REQUEST['user'];
        $data = AppHelper::getJsonInput();

        if (empty($data['category_id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID da categoria obrigatório']);
        }

        UserInterest::remove($user['id'], $data['category_id']);
        AppHelper::sendResponse(200, ['message' => 'Interesse removido.']);
    }
}
