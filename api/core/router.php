<?php
// api/core/router.php

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, callable $callback): void
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/') ?: '/';

        $this->routes[$method][$path] = $callback;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        if (strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }

        $path = '/' . trim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        header('Content-Type: application/json');

        if (!isset($this->routes[$method])) {
            http_response_code(405);
            echo json_encode(['message' => 'Method Not Allowed']);
            return;
        }

        foreach ($this->routes[$method] as $routePath => $callback) {
            if ($routePath === $path) {
                call_user_func($callback);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
    }
}