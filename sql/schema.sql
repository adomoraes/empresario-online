-- Tabela de Utilizadores
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Senha encriptada
    role VARCHAR(50) DEFAULT 'user', -- Para o middleware de permissões
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Tokens (Substituto Manual do Sanctum)
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE, -- O token em si
    name VARCHAR(255) DEFAULT 'default', -- Nome do dispositivo/token
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inserir um utilizador de teste (se a tabela estiver vazia)
-- Senha '123456' (Hash gerado pelo PHP password_hash)
INSERT IGNORE INTO users (id, name, email, password, role) 
VALUES (1, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');