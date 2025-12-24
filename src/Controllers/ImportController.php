<?php

namespace App\Controllers;

use App\Models\Interview;
use App\Config\AppHelper;

class ImportController
{
    /**
     * POST /admin/import/interview
     * Recebe o JSON completo da entrevista e salva no banco.
     */
    public function import()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data)) {
            AppHelper::sendResponse(400, ['error' => 'JSON inválido.']);
        }

        // 3. Validação mínima (Opcional, mas recomendada)
        if (empty($data['title']) || empty($data['interviewee'])) {
            AppHelper::sendResponse(400, ['error' => 'Campos obrigatórios (title, interviewee) em falta.']);
        }

        try {
            // 4. Chamar o método create() que criámos no Model Interview
            // Ele já sabe lidar com 'image', 'team' e 'categories'
            $id = Interview::create($data);

            AppHelper::sendResponse(201, [
                'message' => 'Entrevista importada com sucesso!',
                'interview_id' => $id,
                'slug_generated' => $data['slug'] ?? 'gerado-automaticamente'
            ]);
        } catch (\PDOException $e) {
            // Tratamento de erro (ex: slug duplicado)
            if ($e->getCode() == 23000) {
                AppHelper::sendResponse(409, ['error' => 'Esta entrevista (slug) já existe no banco.']);
            } else {
                AppHelper::sendResponse(500, ['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
            }
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }
}
