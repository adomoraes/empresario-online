<?php

namespace App\Controllers;

use App\Models\Interview;
use App\Config\AppHelper;
use OpenApi\Attributes as OA; // <--- Importante adicionar isto

class ImportController
{
    /**
     * POST /admin/import/interview
     * Recebe o JSON completo da entrevista e salva no banco.
     */
    #[OA\Post(
        path: '/admin/import/interview',
        tags: ['Admin'],
        summary: 'Importar Entrevista (JSON)',
        description: 'Cria uma entrevista, categorias e equipa a partir de um JSON estruturado.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'interviewee'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'interviewee', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string')),
                    // Podes adicionar mais exemplos aqui conforme a estrutura do teu JSON
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Importado com sucesso'),
            new OA\Response(response: 409, description: 'Entrevista já existe')
        ]
    )]
    public function import()
    {
        $data = AppHelper::getJsonInput();
        // ... (O resto do código mantém-se igual)
        if (empty($data)) {
            AppHelper::sendResponse(400, ['error' => 'JSON inválido.']);
        }

        // 3. Validação mínima (Opcional, mas recomendada)
        if (empty($data['title']) || empty($data['interviewee'])) {
            AppHelper::sendResponse(400, ['error' => 'Campos obrigatórios (title, interviewee) em falta.']);
        }

        try {
            // 4. Chamar o método create() que criámos no Model Interview
            $id = Interview::create($data);

            AppHelper::sendResponse(201, [
                'message' => 'Entrevista importada com sucesso!',
                'interview_id' => $id,
                'slug_generated' => $data['slug'] ?? 'gerado-automaticamente'
            ]);
        } catch (\PDOException $e) {
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
