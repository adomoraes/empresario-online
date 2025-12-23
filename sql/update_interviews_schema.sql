-- 1. Tabela de Entrevistas
CREATE TABLE IF NOT EXISTS interviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE, -- Para URL amigável
    interviewee VARCHAR(255) NOT NULL, -- O nome do entrevistado
    excerpt TEXT, -- O resumo
    content LONGTEXT, -- O texto completo
    published_at DATE,
    
    -- Dados complexos do JSON guardados como JSON nativo no MySQL
    image_data JSON, -- Guarda url, alt_text, etc
    team_data JSON,  -- Guarda journalist, director, etc
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabela Pivot Entrevistas <-> Categorias
-- Como o JSON mostra várias categorias, precisamos desta relação N:N
CREATE TABLE IF NOT EXISTS interview_categories (
    interview_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (interview_id, category_id),
    FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);