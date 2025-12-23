<?php

namespace App\Controllers;

use App\Models\Interview;

class ImportController
{
    /**
     * POST /admin/import/interview
     * Recebe o JSON completo da entrevista e salva no banco.
     */
    public function import()
    {
        // 1. Obter o JSON cru do corpo da requisição
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        // 2. Verificar se o JSON é válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON inválido.']);
            return;
        }

        // 3. Validação mínima (Opcional, mas recomendada)
        if (empty($data['title']) || empty($data['interviewee'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Campos obrigatórios (title, interviewee) em falta.']);
            return;
        }

        try {
            // 4. Chamar o método create() que criámos no Model Interview
            // Ele já sabe lidar com 'image', 'team' e 'categories'
            $id = Interview::create($data);

            http_response_code(201);
            echo json_encode([
                'message' => 'Entrevista importada com sucesso!',
                'interview_id' => $id,
                'slug_generated' => $data['slug'] ?? 'gerado-automaticamente'
            ]);
        } catch (\PDOException $e) {
            // Tratamento de erro (ex: slug duplicado)
            if ($e->getCode() == 23000) {
                http_response_code(409);
                echo json_encode(['error' => 'Esta entrevista (slug) já existe no banco.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }
}
