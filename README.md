# 🚀 Empresário Online API

API RESTful desenvolvida para o portal **Empresário Online**, utilizando uma arquitetura MVC personalizada em PHP 8.2 puro (sem frameworks pesados), focada em performance, organização e facilidade de manutenção.

O sistema implementa um modelo de acesso **Premium**, onde o conteúdo (Artigos e Entrevistas) é exclusivo para utilizadores autenticados, além de incluir uma área administrativa completa.

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

O projeto não utiliza frameworks de terceiros (como Laravel ou Symfony) para o núcleo, implementando a sua própria estrutura leve e eficiente:

### 1. Padrão MVC (Model-View-Controller)

- **Router Personalizado (`src/Config/Router.php`):** Suporta verbos HTTP (GET, POST, PUT, DELETE), agrupamento de rotas (`mount`) e aplicação de Middlewares (`use`).
- **Controllers:** Gerem a lógica de requisição e resposta JSON. Exemplos: `ArticleController`, `InterviewController`.
- **Models:** Utilizam PDO para comunicação direta e segura com o MySQL. Exemplos: `Article::all()`, `User::create()`.

### 2. Segurança e Middlewares

O sistema utiliza cadeias de responsabilidade via Middlewares:

- **`AuthMiddleware`:** Verifica o Token Bearer (JWT Simples) e injeta o utilizador na requisição.
- **`AdminMiddleware`:** Garante que o utilizador autenticado tem a role `admin`.
- **`LogMiddleware`:** Regista acessos e métricas de uso para auditoria.

### 3. Modelo de Acesso "Premium"

- **Público:** Rotas de Login, Registo e Documentação Swagger.
- **Premium (Autenticado):** Leitura de Artigos, Entrevistas e acesso ao Dashboard.
- **Admin:** Criação, Edição e Remoção de conteúdo, além da gestão de utilizadores e logs.

---

## 🐳 Como Rodar o Projeto

O ambiente é totalmente "Dockerizado" e inclui scripts de automação para facilitar o início.

### Pré-requisitos

- Docker e Docker Compose instalados.

### Passo a Passo

1.  **Subir o Ambiente:**
    Execute o comando na raiz do projeto para construir e iniciar os contentores:

    ```bash
    docker-compose up --build
    ```

2.  **Automação de Arranque:**
    O script `entrypoint.sh` executa as seguintes ações automaticamente a cada arranque:

    - Aguardar a disponibilidade do MySQL.
    - **Saneamento:** Limpa e recria a estrutura da base de dados.
    - **Seeding:** Executa `seed_runner.php` para popular o banco com dados de teste (10 users, 2 admins, 20 artigos, 30 entrevistas).
    - Iniciar o servidor Apache.

3.  **Aceder à Aplicação:**
    - **API Base:** `http://localhost:8080`
    - **Documentação Swagger:** `http://localhost:8080/` ou `http://localhost:8080/api-docs`

---

## 📚 Documentação da API (Swagger)

A documentação interativa é gerada automaticamente via anotações (Attributes) nos Controllers.

**Como testar rotas protegidas no Swagger:**

1.  Aceda a `http://localhost:8080`.
2.  Use a rota `POST /login` com as credenciais de teste geradas pelo seed:
    - **Email:** `admin@teste.com`
    - **Password:** `123`
3.  Copie o `token` devolvido na resposta JSON.
4.  Clique no botão **Authorize** (cadeado) no topo da página e cole o token.
5.  Agora pode testar as rotas protegidas (ex: `GET /articles`, `POST /interviews`).

---

## 🧪 Testes Automatizados

O projeto possui uma suíte de testes robusta cobrindo autenticação, CRUDs e regras de negócio.

Para rodar os testes dentro do contentor:

```bash
docker-compose exec app vendor/bin/phpunit
```
