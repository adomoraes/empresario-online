# Empresário Online

API em PHP Puro com Docker.

## Descrição

Este projeto é uma API RESTful desenvolvida em PHP puro, utilizando Docker para criar um ambiente de desenvolvimento padronizado e isolado.

## Requisitos

- Docker
- Docker Compose
- Composer

## Como Iniciar o Projeto

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/seu-usuario/empresario-online.git
   cd empresario-online
   ```

2. **Instale as dependências com o Composer:**
   ```bash
   composer install
   ```

3. **Inicie os containers com o Docker Compose:**
   ```bash
   docker-compose up -d --build
   ```

   A aplicação estará disponível em [http://localhost:8080](http://localhost:8080).

## Como Executar os Testes

Para executar a suíte de testes automatizados, siga os passos abaixo:

1. **Crie o banco de dados de teste:**
   O ambiente de teste requer um banco de dados separado. Execute o comando abaixo para criá-lo dentro do container MySQL.
   ```bash
   docker exec -i mysql_nativo mysql -uroot -proot < sql/create_test_db.sql
   ```

2. **Execute o PHPUnit:**
   ```bash
   vendor/bin/phpunit
   ```

## Banco de Dados

A aplicação utiliza um container MySQL. As credenciais de acesso padrão estão configuradas no arquivo `docker-compose.yml`:

- **Host (para a aplicação no Docker):** `db`
- **Host (para acesso externo/testes):** `127.0.0.1`
- **Porta:** `3306`
- **Banco de dados principal:** `meu_banco`
- **Usuário:** `user`
- **Senha:** `password`
- **Usuário Root:** `root`
- **Senha Root:** `root`

O banco de dados de teste é o `meu_banco_teste`.

## Documentação da API

A documentação dos endpoints da API está disponível em dois formatos:

### 1. Swagger UI (Recomendado)

Após iniciar o projeto, você pode acessar uma interface interativa do Swagger para visualizar e testar todos os endpoints.

- **URL:** [http://localhost:8080/swagger.html](http://localhost:8080/swagger.html)

### 2. Coleção Postman

Uma coleção do Postman também está disponível. Você pode importar o seguinte arquivo no seu Postman:

- `docs/eol_api.postman_collection.json`
