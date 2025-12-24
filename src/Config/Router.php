<?php

namespace App\Config;

class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $uri, string $action)
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, string $action)
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function group(array $attributes, callable $callback)
    {
        $this->groupStack[] = $attributes;
        call_user_func($callback, $this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $uri, string $action)
    {
        // CORREÇÃO: Usamos um array para acumular TODOS os middlewares da pilha
        $middlewares = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['before'])) {
                $middlewares[] = $group['before'];
            }
        }

        $this->routes[] = [
            'method' => $method,
            'uri'    => $uri,
            'action' => $action,
            'middlewares' => $middlewares // Guardamos a lista completa (ex: [Auth, Admin])
        ];
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['uri'] === $uri) {

                // 1. Executar Middlewares
                if (!empty($route['middlewares'])) {
                    foreach ($route['middlewares'] as $middlewareClass) {
                        if (class_exists($middlewareClass)) {
                            (new $middlewareClass)->handle();
                        } else {
                            $msg = "DEBUG: Middleware não encontrado: $middlewareClass";
                            fwrite(STDERR, "\n$msg\n"); // <--- IMPRIME NO TERMINAL
                            AppHelper::sendResponse(500, ['error' => $msg]);
                            return;
                        }
                    }
                }

                // 2. Executar Controller
                $action = $route['action'];

                if (strpos($action, '@') !== false) {
                    [$controller, $method] = explode('@', $action);
                } else {
                    $controller = $action;
                    $method = '__invoke';
                }

                if (class_exists($controller)) {
                    $controllerInstance = new $controller();

                    if (method_exists($controllerInstance, $method)) {
                        $controllerInstance->$method();
                        return;
                    } else {
                        $msg = "DEBUG: Método '$method' não encontrado em '$controller'";
                        fwrite(STDERR, "\n$msg\n"); // <--- IMPRIME NO TERMINAL
                        AppHelper::sendResponse(500, ['error' => $msg]);
                        return;
                    }
                } else {
                    $msg = "DEBUG: Controller não encontrado: '$controller'";
                    fwrite(STDERR, "\n$msg\n"); // <--- IMPRIME NO TERMINAL
                    AppHelper::sendResponse(500, ['error' => $msg]);
                    return;
                }
            }
        }

        AppHelper::sendResponse(404, ['error' => 'Rota não encontrada: ' . $uri]);
    }
}
