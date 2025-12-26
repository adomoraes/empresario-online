<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Models\User;
use App\Models\Category;
use App\Models\Article;
use App\Models\Interview;

echo "🚀 Iniciando Saneamento e Seeding...\n";

$pdo = Database::getConnection();

// 1. SANEAMENTO (Limpar Base de Dados)
echo "🧹 Limpando tabelas...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$tables = ['users', 'categories', 'articles', 'interviews', 'interview_categories', 'access_logs', 'personal_access_tokens', 'user_interests'];
foreach ($tables as $table) {
    $pdo->exec("TRUNCATE TABLE $table");
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// 2. CRIAR CATEGORIAS (10)
echo "📂 Criando 10 Categorias...\n";
$categoryIds = [];
for ($i = 1; $i <= 10; $i++) {
    $name = "Categoria $i";
    $slug = "categoria-$i";
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
    $stmt->execute([$name, $slug]);
    $categoryIds[] = $pdo->lastInsertId();
}

// 3. CRIAR USUÁRIOS
// 10 Users Comuns
echo "👤 Criando 10 Usuários Comuns...\n";
$userIds = [];
for ($i = 1; $i <= 10; $i++) {
    $userIds[] = User::create("User $i", "user$i@teste.com", password_hash('123', PASSWORD_DEFAULT));
}

// 2 Admins
echo "🛡️ Criando 2 Admins...\n";
// Admin 1 (Fixo para facilitar testes)
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['Admin Principal', 'admin@teste.com', password_hash('123', PASSWORD_DEFAULT), 'admin']);
// Admin 2
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['Admin Secundário', 'admin2@teste.com', password_hash('123', PASSWORD_DEFAULT), 'admin']);


// 4. CRIAR ARTIGOS (20) - Categorizados
echo "📝 Criando 20 Artigos...\n";
for ($i = 1; $i <= 20; $i++) {
    $title = "Artigo Interessante $i";
    $content = "Este é o conteúdo do artigo número $i. Lorem ipsum dolor sit amet.";
    $authorId = $userIds[array_rand($userIds)]; // User aleatório
    $catId = $categoryIds[array_rand($categoryIds)]; // Categoria aleatória

    $pdo->prepare("INSERT INTO articles (user_id, category_id, title, content) VALUES (?, ?, ?, ?)")
        ->execute([$authorId, $catId, $title, $content]);
}

// 5. CRIAR ENTREVISTAS (30) - Categorizadas
echo "🎤 Criando 30 Entrevistas...\n";
for ($i = 1; $i <= 30; $i++) {
    $title = "Entrevista Exclusiva $i";
    $interviewee = "Entrevistado $i";
    // Seleciona 2 categorias aleatórias para cada entrevista
    $cats = [
        $categoryIds[array_rand($categoryIds)],
        $categoryIds[array_rand($categoryIds)]
    ];

    // Usa o método create do Model que já lida com a tabela pivot e transações
    Interview::create([
        'title' => $title,
        'interviewee' => $interviewee,
        'content' => "Conteúdo da entrevista $i...",
        'published_at' => date('Y-m-d'),
        'category_ids' => array_unique($cats) // IDs das categorias
    ]);
}

echo "✅ Concluído! Base de dados limpa e populada.\n";
