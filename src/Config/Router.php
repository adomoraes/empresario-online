<?php

namespace App\Config;

class Router
{
    private array $routes = [];

    /**
     * Regista uma rota GET
     */
    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    /**
     * Regista uma rota POST
     */
    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    /**
     * Função interna para guardar a rota no array
     * A chave será algo como: "GET|/users"
     */
    private function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes["$method|$path"] = [
            'controller' => $controller,
            'action' => $action
        ];
    }

    /**
     * Ouve o pedido e executa o controlador correto
     */
    public function dispatch(): void
    {
        // 1. Obter o URL atual (ex: /register)
        // O parse_url garante que ignoramos coisas como ?id=1
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // 2. Obter o Método HTTP (GET, POST, etc.)
        $method = $_SERVER['REQUEST_METHOD'];

        // 3. Criar a chave de busca
        $key = "$method|$uri";

        // 4. Verificar se a rota existe
        if (array_key_exists($key, $this->routes)) {
            $route = $this->routes[$key];

            $controllerName = $route['controller'];
            $action = $route['action'];

            // Instancia o Controlador e chama o método
            // Ex: (new UserController())->register()
            $controller = new $controllerName();
            $controller->$action();
        } else {
            // Rota não encontrada (404)
            http_response_code(404);
            echo json_encode(['error' => 'Rota não encontrada (404)']);
        }
    }
}
