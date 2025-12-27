# 🚀 Empresário Online API

API RESTful desenvolvida para o portal **Empresário Online**, utilizando uma arquitetura MVC personalizada em PHP 8.2 puro (sem frameworks pesados), focada em performance, organização e facilidade de manutenção.

O sistema implementa um modelo de acesso **Premium**, onde o conteúdo (Artigos e Entrevistas) é exclusivo para usuários autenticados, além de incluir uma área administrativa completa.

---

## 🛠️ Stack Tecnológica

- **Linguagem:** PHP 8.2
- **Web Server:** Apache (com `mod_rewrite` ativo)
- **Banco de Dados:** MySQL 5.7
- **Infraestrutura:** Docker & Docker Compose
- **Documentação:** OpenAPI 3.0 (Swagger PHP)
- **Testes:** PHPUnit 10

---

## 🏗️ Arquitetura do Projeto

O projeto não utiliza frameworks de terceiros para o núcleo, implementando a sua própria estrutura leve e eficiente:

### 1. Padrão MVC (Model-View-Controller)

- **Router Personalizado:** Suporta verbos HTTP, agrupamento de rotas (`mount`) e middlewares.
- **Controllers:** Gerenciam a lógica de requisição/resposta.
- **Models:** Utilizam PDO para comunicação direta e segura com o MySQL.

### 2. Segurança e Middlewares

- **`AuthMiddleware`:** Verifica o Token Bearer (JWT Simples) e injeta o usuário na requisição.
- **`AdminMiddleware`:** Garante que o usuário autenticado tem a role `admin`.
- **`LogMiddleware`:** Registra acessos e métricas de uso para auditoria.

### 3. Modelo de Acesso "Premium"

- **Público:** Rotas de Login, Registro e Documentação.
- **Premium:** Leitura de Artigos, Entrevistas e Dashboard.
- **Admin:** Gestão completa de conteúdo e usuários.

### 4. ⭐ Feature de Destaque: Dashboard Híbrido

O endpoint `/dashboard` implementa um **Sistema de Recomendação Híbrido** que personaliza o feed do usuário combinando duas fontes de inteligência:

- **Histórico de Navegação:** Analisa as categorias mais visitadas pelo usuário.
- **Interesses Explícitos:** Considera as categorias que o usuário escolheu seguir (`/interests`).
- **Fallback Inteligente:** Para novos usuários (sem dados), o sistema entrega automaticamente os conteúdos mais recentes.

### 5. ⭐ Feature: Sistema de Favoritos

Implementação de funcionalidade para "guardar para ler depois":

- **Estrutura Polimórfica:** O sistema utiliza uma tabela unificada (`user_favorites`) capaz de armazenar referências tanto para **Artigos** quanto para **Entrevistas**.
- **Gestão de Lista:** O usuário pode adicionar (`POST`), remover (`DELETE`) e visualizar (`GET`) sua lista de favoritos através do endpoint `/favorites`.

### 6. Estrutura de pastas

```bash
.
├── docker/ # Configurações de infra (Dockerfile, vhost, entrypoint)
├── docs/ # Documentação adicional (Postman Collection)
├── public/ # Ponto de entrada (index.php), assets e swagger
├── sql/ # Scripts SQL (Schema, Seeds, Dumps)
├── src/
│ ├── Config/ # Configurações (Database, Router, SwaggerConfig)
│ ├── Controllers/ # Lógica dos endpoints da API
│ ├── Middlewares/ # Regras de proteção e log
│ ├── Models/ # Camada de acesso a dados e regras de negócio
│ ├── Utils/ # Classes utilitárias
│ └── routes.php # Definição das rotas da API
├── tests/ # Testes automatizados (PHPUnit)
├── composer.json # Dependências do projeto
├── docker-compose.yml # Orquestração de containers
├── phpunit.xml # Configuração da suíte de testes
├── seed_runner.php # Script de população de dados e simulação
└── README.md # Este arquivo
```

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

2.  **Automação de Inicialização:**
    O script `entrypoint.sh` executa automaticamente a cada inicialização:

    - Aguarda a disponibilidade do MySQL.
    - **Saneamento:** Limpa e recria a estrutura do banco de dados.
    - **Seeding Avançado:** O script `seed_runner.php` popula o banco com:
      - 10 Usuários e 2 Admins.
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

## 📚 Documentação Postman Collection

Para facilitar os testes e o desenvolvimento, uma coleção completa de requisições está disponível.

- **Arquivo:** `docs/eol_api.postman_collection.json`
- **Instruções:** Importe este arquivo diretamente no seu aplicativo Postman para ter acesso a todas as rotas pré-configuradas.
