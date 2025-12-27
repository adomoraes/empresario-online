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
// Adicionado 'user_history' à lista para limpar tudo corretamente
$tables = ['users', 'categories', 'articles', 'interviews', 'interview_categories', 'access_logs', 'personal_access_tokens', 'user_interests', 'user_history'];
foreach ($tables as $table) {
    // Verifica se a tabela existe antes de truncar (para evitar erro em ambientes novos)
    $exists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
    if ($exists) {
        $pdo->exec("TRUNCATE TABLE $table");
    }
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
// Admin 1
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['Admin Principal', 'admin@teste.com', password_hash('123', PASSWORD_DEFAULT), 'admin']);
$userIds[] = $pdo->lastInsertId(); // Adiciona admin à lista para ter histórico também

// Admin 2
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['Admin Secundário', 'admin2@teste.com', password_hash('123', PASSWORD_DEFAULT), 'admin']);


// 4. CRIAR ARTIGOS (20)
echo "📝 Criando 20 Artigos...\n";
$articleIds = [];
for ($i = 1; $i <= 20; $i++) {
    $title = "Artigo Interessante $i";
    $content = "Este é o conteúdo do artigo número $i. Lorem ipsum dolor sit amet.";
    $authorId = $userIds[array_rand($userIds)];
    $catId = $categoryIds[array_rand($categoryIds)];

    $pdo->prepare("INSERT INTO articles (user_id, category_id, title, content, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$authorId, $catId, $title, $content]);
    $articleIds[] = ['id' => $pdo->lastInsertId(), 'category_id' => $catId];
}

// 5. CRIAR ENTREVISTAS (30)
echo "🎤 Criando 30 Entrevistas...\n";
$interviewIds = [];
for ($i = 1; $i <= 30; $i++) {
    $title = "Entrevista Exclusiva $i";
    $interviewee = "Entrevistado $i";
    $cats = [
        $categoryIds[array_rand($categoryIds)],
        $categoryIds[array_rand($categoryIds)]
    ];
    $cats = array_unique($cats);

    $id = Interview::create([
        'title' => $title,
        'interviewee' => $interviewee,
        'content' => "Conteúdo da entrevista $i...",
        'published_at' => date('Y-m-d'),
        'category_ids' => $cats
    ]);

    // Guardar para gerar histórico depois (assumimos a primeira categoria como principal para o teste simples)
    $interviewIds[] = ['id' => $id, 'category_id' => $cats[0]];
}

// 6. POPULAR INTERESSES (Feature Dashboard)
echo "❤️ Criando Interesses dos Usuários...\n";
foreach ($userIds as $uid) {
    // Cada user segue entre 1 a 3 categorias aleatórias
    $numInterests = rand(1, 3);
    $randomCats = array_rand(array_flip($categoryIds), $numInterests);
    if (!is_array($randomCats)) $randomCats = [$randomCats];

    foreach ($randomCats as $catId) {
        $pdo->prepare("INSERT IGNORE INTO user_interests (user_id, category_id) VALUES (?, ?)")
            ->execute([$uid, $catId]);
    }
}

// 7. POPULAR HISTÓRICO (Feature Dashboard)
echo "🕒 Criando Histórico de Navegação...\n";
foreach ($userIds as $uid) {
    // Cada user leu entre 5 a 10 itens
    $numViews = rand(5, 10);

    for ($k = 0; $k < $numViews; $k++) {
        // 50% chance de ser artigo, 50% entrevista
        if (rand(0, 1) === 0 && !empty($articleIds)) {
            $item = $articleIds[array_rand($articleIds)];
            $type = 'article';
        } else if (!empty($interviewIds)) {
            $item = $interviewIds[array_rand($interviewIds)];
            $type = 'interview';
        } else {
            continue;
        }

        $pdo->prepare("INSERT INTO user_history (user_id, category_id, content_type, content_id, accessed_at) VALUES (?, ?, ?, ?, NOW() - INTERVAL ? HOUR)")
            ->execute([$uid, $item['category_id'], $type, $item['id'], rand(1, 500)]);
    }
}

echo "✅ Concluído! Base de dados limpa e populada com dados de teste (incluindo histórico e interesses).\n";
