<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use PDO;

use OpenApi\Attributes as OA;

class InterviewController
{
    #[OA\Get(
        path: '/interviews',
        tags: ['Entrevistas'],
        summary: 'Lista todas as entrevistas',
        responses: [
            new OA\Response(response: 200, description: 'Lista de entrevistas')
        ]
    )]
    public function index()
    {
        $pdo = Database::getConnection();
        // Busca entrevistas e concatena os nomes das categorias numa string (ex: "Tech, Business")
        $sql = "
            SELECT i.id, i.title, i.slug, i.published_at,
                   GROUP_CONCAT(c.name) as categories
            FROM interviews i
            LEFT JOIN interview_categories ic ON i.id = ic.interview_id
            LEFT JOIN categories c ON ic.category_id = c.id
            GROUP BY i.id
            ORDER BY i.published_at DESC
        ";
        $stmt = $pdo->query($sql);
        AppHelper::sendResponse(200, ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    #[OA\Get(
        path: '/interview',
        tags: ['Entrevistas'],
        summary: 'Busca uma entrevista por ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Entrevista encontrada'),
            new OA\Response(response: 400, description: 'ID da entrevista é necessário'),
            new OA\Response(response: 404, description: 'Entrevista não encontrada')
        ]
    )]
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            AppHelper::sendResponse(400, ['error' => 'ID necessário']);
            return;
        }

        $pdo = Database::getConnection();

        // 1. Buscar dados da entrevista
        $stmt = $pdo->prepare("SELECT * FROM interviews WHERE id = ?");
        $stmt->execute([$id]);
        $interview = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$interview) {
            AppHelper::sendResponse(404, ['error' => 'Entrevista não encontrada']);
            return;
        }

        // 2. Buscar categorias dessa entrevista
        $stmtCat = $pdo->prepare("
            SELECT c.id, c.name 
            FROM categories c
            JOIN interview_categories ic ON c.id = ic.category_id
            WHERE ic.interview_id = ?
        ");
        $stmtCat->execute([$id]);
        $interview['categories'] = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        // 3. Auth Opcional e Histórico
        if (!isset($_REQUEST['user'])) {
            $this->tryIdentifyUser();
        }

        if (isset($_REQUEST['user'])) {
            // Grava histórico para CADA categoria da entrevista
            foreach ($interview['categories'] as $cat) {
                \App\Models\UserHistory::record(
                    $_REQUEST['user']['id'],
                    $cat['id'],
                    'interview',
                    $id
                );
            }
        }

        AppHelper::sendResponse(200, ['data' => $interview]);
    }

    #[OA\Post(
        path: '/interviews',
        tags: ['Admin'],
        summary: 'Cria uma nova entrevista',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'interviewee', 'category_ids'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Título da Entrevista'),
                    new OA\Property(property: 'interviewee', type: 'string', example: 'Nome do Entrevistado'),
                    new OA\Property(property: 'content', type: 'string', example: 'Conteúdo da entrevista...'),
                    new OA\Property(property: 'category_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Entrevista criada'),
            new OA\Response(response: 400, description: 'Dados incompletos'),
            new OA\Response(response: 401, description: 'Não autorizado'),
            new OA\Response(response: 500, description: 'Erro interno')
        ]
    )]
    public function store()
    {
        $data = AppHelper::getJsonInput();

        // Validação básica
        if (empty($data['title']) || empty($data['interviewee']) || empty($data['category_ids'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos']);
            return;
        }

        $pdo = Database::getConnection();
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title'])));

        $transactionStartedByMe = false;

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $transactionStartedByMe = true;
            }

            // 1. Inserir Entrevista
            $stmt = $pdo->prepare("INSERT INTO interviews (title, slug, interviewee, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['title'], $slug, $data['interviewee'], $data['content'] ?? '']);
            $interviewId = $pdo->lastInsertId();

            // 2. Inserir Categorias
            $stmtCat = $pdo->prepare("INSERT INTO interview_categories (interview_id, category_id) VALUES (?, ?)");

            // Validação extra: garante que é array antes do loop
            if (!is_array($data['category_ids'])) {
                throw new \Exception("category_ids deve ser um array");
            }

            foreach ($data['category_ids'] as $catId) {
                $stmtCat->execute([$interviewId, $catId]);
            }

            if ($transactionStartedByMe) {
                $pdo->commit();
            }

            AppHelper::sendResponse(201, ['message' => 'Entrevista criada', 'id' => $interviewId]);
        } catch (\Throwable $e) { // <--- MUDANÇA CRÍTICA: Throwable apanha erros fatais

            // --- A CURA ---
            // Se for a nossa exceção de "teste finalizado", relança-a para o TestCase apanhar.
            // Não a trates como erro!
            if ($e instanceof \App\Config\HttpResponseException) {
                throw $e;
            }

            if ($transactionStartedByMe && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // ESCREVE O ERRO DIRETAMENTE NO TERMINAL DO PHPUNIT
            $erroMsg = "\n\n!!! ERRO CRÍTICO !!!\n" .
                "Mensagem: " . $e->getMessage() . "\n" .
                "Arquivo: " . $e->getFile() . " na linha " . $e->getLine() . "\n" .
                "Stack Trace: " . $e->getTraceAsString() . "\n\n";

            AppHelper::sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    private function tryIdentifyUser()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT u.id, u.role FROM personal_access_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) $_REQUEST['user'] = $user;
        }
    }

    #[OA\Put(
        path: '/interviews',
        tags: ['Admin'],
        summary: 'Atualiza uma entrevista',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id', 'title', 'interviewee'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'interviewee', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'category_ids', type: 'array', items: new OA\Items(type: 'integer'))
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Atualizado')]
    )]
    public function update()
    {
        $data = AppHelper::getJsonInput();

        // Validação
        if (empty($data['id']) || empty($data['title'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos (id, title são obrigatórios)']);
            return;
        }

        try {
            // Chama o método que acabámos de criar no Model
            \App\Models\Interview::update($data['id'], $data);
            AppHelper::sendResponse(200, ['message' => 'Entrevista atualizada']);
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro ao atualizar: ' . $e->getMessage()]);
        }
    }

    #[OA\Delete(
        path: '/interviews',
        tags: ['Admin'],
        summary: 'Remove uma entrevista',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'integer')])),
        responses: [new OA\Response(response: 200, description: 'Removido')]
    )]
    public function destroy()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        if (\App\Models\Interview::delete($data['id'])) {
            AppHelper::sendResponse(200, ['message' => 'Entrevista removida']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao remover']);
        }
    }
}
