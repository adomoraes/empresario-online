# 🚀 Empresário Online API

API RESTful desenvolvida para o portal **Empresário Online**, utilizando uma arquitetura MVC personalizada em PHP 8.2, focada em performance, organização e facilidade de manutenção.

O sistema implementa um modelo de acesso **Premium**, onde o conteúdo (Artigos e Entrevistas) é exclusivo para usuários autenticados, além de incluir uma área administrativa completa.

---

## 🛠️ Stack Tecnológica

- **Linguagem:** PHP 8.2
- **Web Server:** Apache (com `mod_rewrite` ativo)
- **Base de Dados:** MySQL 5.7
- **Infraestrutura:** Docker & Docker Compose
- **Documentação:** OpenAPI 3.0 (Swagger PHP)
- **Testes:** PHPUnit 10

---

## 🏗️ Arquitetura do Projeto

O projeto não utiliza framework, implementando a sua própria estrutura:

### 1. Padrão MVC (Model-View-Controller)

- **Router Personalizado:** Suporta métodos HTTP, agrupamento de rotas (`mount`) e middlewares.
- **Controllers:** Gerem a lógica de requisição/resposta.
- **Models:** Utilizam PDO para comunicação direta e segura com o MySQL.

### 2. Segurança e Middlewares

- **`AuthMiddleware`:** Verifica o Token Bearer (JWT Simples) e injeta o usuário na requisição.
- **`AdminMiddleware`:** Garante que o usuário autenticado tem a role `admin`.
- **`LogMiddleware`:** Regista acessos e métricas de uso para auditoria.

### 3. Modelo de Acesso "Premium"

- **Público:** Rotas de Login, Registo e Documentação.
- **Premium:** Leitura de Artigos, Entrevistas e Dashboard.
- **Admin:** Gestão completa de conteúdo e usuárioes.

### 4. ⭐ Feature de Destaque: Dashboard Híbrido (Novo)

O endpoint `/dashboard` implementa um **Sistema de Recomendação Híbrido** que personaliza o feed do usuário combinando duas fontes de inteligência:

- **Histórico de Navegação:** Analisa as categorias mais visitadas pelo usuário.
- **Interesses Explícitos:** Considera as categorias que o usuário escolheu seguir (`/interests`).
- **Fallback Inteligente:** Para novos usuários (sem dados), o sistema entrega automaticamente os conteúdos mais recentes.

---

## 🐳 Como Rodar o Projeto

### Pré-requisitos

- Docker e Docker Compose instalados.

### Passo a Passo

1.  **Subir o Ambiente:**
    Execute o comando na raiz do projeto:

    ```bash
    docker-compose up --build
    ```

2.  **Automação de Início:**
    O script `entrypoint.sh` executa automaticamente a cada inicialização do Docker:

    - Aguarda a disponibilidade do MySQL.
    - **Saneamento:** Limpa e recria a estrutura da base de dados.
    - **Seeding Avançado:** O script `seed_runner.php` popula o banco com:
      - 10 usuárioes e 2 Admins.
      - 20 Artigos e 30 Entrevistas categorizadas.
      - **Simulação de Uso:** Gera aleatoriamente **Histórico de Leitura** e **Interesses** para testar o algoritmo do Dashboard.
    - Inicia o servidor Apache.

3.  **Acessar a Aplicação:**
    - **API Base:** `http://localhost:8080`
    - **Documentação Swagger:** `http://localhost:8081/` ou `http://localhost:8080/api-docs`

---

## 📚 Documentação da API (Swagger)

A documentação interativa é gerada automaticamente via anotações (Attributes) nos Controllers.

**Como testar rotas protegidas:**

1.  Acesse `http://localhost:8081`.
2.  Use a rota `POST /login` com credenciais de teste (ex: `admin@teste.com` / `123`).
3.  Copie o `token` da resposta.
4.  Clique em **Authorize** (cadeado) e cole o token.
5.  Teste endpoints como `GET /dashboard` para ver a recomendação híbrida em ação.

---

## 🧪 Testes Automatizados

O projeto possui uma suíte de testes robusta cobrindo autenticação, CRUDs e regras de negócio complexas.

Para rodar os testes dentro do contentor:

```bash
docker-compose exec app vendor/bin/phpunit
```
