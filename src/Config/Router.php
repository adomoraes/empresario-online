<?php

namespace App\Config;

class Router
{
    private array $routes = [];

    /**
     * Regista rota GET com middlewares opcionais
     */
    public function get(string $path, string $controller, string $action, array $middlewares = []): void
    {
        $this->add('GET', $path, $controller, $action, $middlewares);
    }

    /**
     * Regista rota POST com middlewares opcionais
     */
    public function post(string $path, string $controller, string $action, array $middlewares = []): void
    {
        $this->add('POST', $path, $controller, $action, $middlewares);
    }

    /**
     * Função interna para guardar a rota
     * O ERRO ESTAVA PROVAVELMENTE AQUI: Faltava guardar o 'middlewares'
     */
    private function add(string $method, string $path, string $controller, string $action, array $middlewares): void
    {
        $this->routes["$method|$path"] = [
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares // <--- CRUCIAL: Temos de guardar a lista aqui!
        ];
    }

    /**
     * Executa a rota
     */
    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $key = "$method|$uri";

        if (array_key_exists($key, $this->routes)) {
            $route = $this->routes[$key];

            // Proteção: Se por acaso a chave não existir, usa um array vazio
            $middlewares = $route['middlewares'] ?? [];

            // Executar Middlewares
            foreach ($middlewares as $middlewareClass) {
                $middleware = new $middlewareClass();
                $middleware->handle();
            }

            // Executar Controller
            $controllerName = $route['controller'];
            $action = $route['action'];

            $controller = new $controllerName();
            $controller->$action();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Rota não encontrada (404)']);
        }
    }
}
