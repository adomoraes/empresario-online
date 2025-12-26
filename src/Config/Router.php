<?php

namespace App\Config;

class Router
{
    private array $routes = [];
    private string $groupPrefix = '';
    private array $groupMiddlewares = [];

    /**
     * Adiciona um grupo de rotas com um prefixo comum.
     * Ex: $router->mount('/admin', function() { ... });
     */
    public function mount(string $prefix, callable $callback): void
    {
        // 1. Guardar estado anterior (para permitir aninhamento: /api -> /v1)
        $previousPrefix = $this->groupPrefix;
        $previousMiddlewares = $this->groupMiddlewares;

        // 2. Atualizar o prefixo atual
        $this->groupPrefix .= rtrim($prefix, '/');

        // 3. Executar o callback onde as rotas internas serão definidas
        call_user_func($callback);

        // 4. Restaurar estado anterior (sair do grupo)
        $this->groupPrefix = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    /**
     * Adiciona um middleware ao grupo atual.
     * Ex: $router->use(new AuthMiddleware());
     */
    public function use(object $middleware): void
    {
        $this->groupMiddlewares[] = $middleware;
    }

    public function get(string $path, string|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, string|callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, string|callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, string|callable $handler): void
    {
        // Combina o prefixo do grupo com o caminho da rota
        $fullPath = $this->groupPrefix . $path;

        // Remove barras duplicadas (ex: //login -> /login), mas mantém a raiz /
        if ($fullPath !== '/') {
            $fullPath = rtrim($fullPath, '/');
        }

        // Converte para regex para suportar parâmetros (opcional, mantido simples aqui)
        // Se já tiveres lógica de regex na tua versão anterior, mantém-na aqui.

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => $this->groupMiddlewares // Guarda os middlewares ativos neste momento
        ];
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['path'] === $uri && $route['method'] === $method) {

                // 1. Executar Middlewares
                foreach ($route['middlewares'] as $middleware) {
                    // Assume que o middleware tem um método handle()
                    if (method_exists($middleware, 'handle')) {
                        $middleware->handle();
                    }
                }

                // 2. Executar Controlador
                $this->executeHandler($route['handler']);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo json_encode(['error' => 'Rota não encontrada']);
    }

    private function executeHandler(string|callable $handler): void
    {
        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        // Formato "Controller@method"
        [$controllerName, $methodName] = explode('@', $handler);

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, $methodName)) {
                $controller->$methodName();
            } else {
                throw new \Exception("Método $methodName não encontrado em $controllerName");
            }
        } else {
            throw new \Exception("Controller $controllerName não encontrado");
        }
    }
}
