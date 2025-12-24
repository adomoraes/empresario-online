-- Tabela para registar visualizações de conteúdo
CREATE TABLE IF NOT EXISTS user_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL, -- Fundamental para o algoritmo
    content_type ENUM('article', 'interview') NOT NULL,
    content_id INT NOT NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Index para deixar o algoritmo rápido
CREATE INDEX idx_history_user_category ON user_history(user_id, category_id);