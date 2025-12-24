<?php

namespace App\Controllers;

use App\Models\Interview;

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
            echo json_encode(['message' => 'Listagem de entrevistas (Implementar Interview::all no Model)']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao buscar dados.']);
        }
    }

    /**
     * POST /interviews
     * Cria uma entrevista manualmente (Simples, sem o JSON complexo).
     */
    public function store()
    {
        $user = $_REQUEST['user'];
        $input = json_decode(file_get_contents('php://input'), true);

        // Validação básica
        if (empty($input['title']) || empty($input['interviewee'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Título e Entrevistado são obrigatórios.']);
            return;
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

            http_response_code(201);
            echo json_encode([
                'message' => 'Entrevista criada com sucesso!',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao criar entrevista: ' . $e->getMessage()]);
        }
    }
}
