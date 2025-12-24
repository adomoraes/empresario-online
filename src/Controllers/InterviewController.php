<?php

namespace App\Controllers;

use App\Models\Interview;
use App\Config\AppHelper;

class InterviewController
{
    /**
     * GET /interviews
     * Lista as entrevistas.
     */
    public function index()
    {
        // Se quisermos filtrar por user, usamos $_REQUEST['user']
        // Aqui vamos listar todas para simplificar o teste
        // Precisaríamos criar um método Interview::all() no Model, 
        // mas por enquanto vamos devolver um array vazio ou erro se o método não existir.

        try {
            // Nota: Se ainda não criaste o método all() no Model Interview, 
            // podes usar o getAllByUserId se quiseres filtrar.
            // Exemplo: $interviews = Interview::getAllByUserId($_REQUEST['user']['id']);

            // Para evitar erros agora, vamos devolver uma mensagem simples
            AppHelper::sendResponse(200, ['message' => 'Listagem de entrevistas (Implementar Interview::all no Model)']);
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro ao buscar dados.']);
        }
    }

    /**
     * POST /interviews
     * Cria uma entrevista manualmente (Simples, sem o JSON complexo).
     */
    public function store()
    {
        $user = $_REQUEST['user'];
        $input = AppHelper::getJsonInput();

        // Validação básica
        if (empty($input['title']) || empty($input['interviewee'])) {
            AppHelper::sendResponse(400, ['error' => 'Título e Entrevistado são obrigatórios.']);
        }

        try {
            // Adaptamos para usar o método create() robusto que fizemos para a importação
            // O create espera um array com a estrutura completa, então montamos o básico aqui:

            $data = [
                'title' => $input['title'],
                'interviewee' => $input['interviewee'],
                'slug' => $input['slug'] ?? null,
                'excerpt' => $input['excerpt'] ?? '',
                'content' => $input['content'] ?? '',
                'published_at' => $input['published_at'] ?? date('Y-m-d'),
                'image' => [], // Vazio por enquanto
                'team' => [],  // Vazio por enquanto
                'categories' => [] // Vazio por enquanto
            ];

            $id = Interview::create($data);

            AppHelper::sendResponse(201, [
                'message' => 'Entrevista criada com sucesso!',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro ao criar entrevista: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /interview?id=1
     */
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            AppHelper::sendResponse(400, ['error' => 'ID necessário']);
        }

        // 1. Buscar Entrevista (Usa o método find do Model que criámos antes)
        $interview = \App\Models\Interview::find($id);

        if (!$interview) {
            AppHelper::sendResponse(404, ['error' => 'Entrevista não encontrada']);
        }

        // 2. Descobrir ID de uma categoria desta entrevista para o histórico
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("SELECT category_id FROM interview_categories WHERE interview_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $catId = $stmt->fetchColumn();

        // 3. Gravar Histórico
        if ($catId && isset($_REQUEST['user'])) {
            \App\Models\UserHistory::record(
                $_REQUEST['user']['id'],
                $catId,
                'interview',
                $id
            );
        }

        AppHelper::sendResponse(200, ['data' => $interview]);
    }
}
