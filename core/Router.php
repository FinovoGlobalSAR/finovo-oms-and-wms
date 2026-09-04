<?php
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        if (!isset($this->routes[$method][$uri])) {
            http_response_code(404);
            echo "404 - Page not found";
            return;
        }

        [$controllerClass, $methodName] = $this->routes[$method][$uri];

        $controllerFile = __DIR__ . "/../app/Controllers/{$controllerClass}.php";
        require_once $controllerFile;

        $controller = new $controllerClass();
        $controller->$methodName();
    }
}