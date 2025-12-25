-- Limpa tabelas antigas (ordem importa por causa das chaves estrangeiras)
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE user_history;
TRUNCATE TABLE interview_categories;
TRUNCATE TABLE articles;
TRUNCATE TABLE interviews;
TRUNCATE TABLE categories;
TRUNCATE TABLE users;
TRUNCATE TABLE personal_access_tokens;
SET FOREIGN_KEY_CHECKS=1;

-- 1. Criar Categorias
INSERT INTO categories (id, name, slug) VALUES 
(1, 'Tecnologia', 'tech'),
(2, 'Saúde', 'health'),
(3, 'Negócios', 'business');

-- 2. Criar Utilizadores (Senha é '123' hashada)
INSERT INTO users (id, name, email, password, role) VALUES 
(1, 'Admin Chefe', 'admin@teste.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin'),
(2, 'Leitor Curioso', 'user@teste.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user');

-- 3. Criar Artigos
INSERT INTO articles (title, content, category_id, user_id) VALUES 
('O Futuro do PHP 8.2', 'O PHP está mais rápido que nunca...', 1, 1),
('Docker para Iniciantes', 'Containers são o futuro...', 1, 1),
('Benefícios da Maçã', 'Uma por dia dá saúde...', 2, 1),
('Como investir em 2025', 'Dicas de mercado...', 3, 1);

-- 4. Criar Entrevistas
INSERT INTO interviews (id, title, slug, interviewee, content, published_at) VALUES 
(1, 'Entrevista com Criador do Linux', 'linux-talk', 'Linus Torvalds', 'Falamos sobre Git e Kernel...', NOW());

-- 5. Ligar Entrevista a Categorias (Tech e Negócios)
INSERT INTO interview_categories (interview_id, category_id) VALUES (1, 1), (1, 3);