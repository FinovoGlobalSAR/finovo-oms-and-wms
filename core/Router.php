<?php
// class Router
// {
//     private array $routes = [];

//     public function get(string $path, array $handler): void
//     {
//         $this->routes['GET'][$path] = $handler;
//     }

//     public function post(string $path, array $handler): void
//     {
//         $this->routes['POST'][$path] = $handler;
//     }

//     public function dispatch(string $uri, string $method): void
//     {
//         $uri = parse_url($uri, PHP_URL_PATH);

//         if (!isset($this->routes[$method][$uri])) {
//             http_response_code(404);
//             echo "404 - Page not found";
//             return;
//         }

//         [$controllerClass, $methodName] = $this->routes[$method][$uri];

//         $controllerFile = __DIR__ . "/../app/Controllers/{$controllerClass}.php";
//         require_once $controllerFile;

//         $controller = new $controllerClass();
//         $controller->$methodName();
//     }
// }


class Router
{
    private array $routes = [];

    public function get(
        string $path,
        array $handler,
        array $middleware = []
    ): void {
        $this->routes['GET'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function post(
        string $path,
        array $handler,
        array $middleware = []
    ): void {
        $this->routes['POST'][$path] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        if (!isset($this->routes[$method][$uri])) {
            http_response_code(404);
            echo "404 - Page not found";
            return;
        }

        $route = $this->routes[$method][$uri];

        // Run middleware
        foreach ($route['middleware'] as $middleware) {

            $middlewareFile =
                __DIR__ . "/../app/Middleware/{$middleware}.php";

            if (!file_exists($middlewareFile)) {
                http_response_code(500);
                echo "Middleware file not found.";
                exit;
            }

            require_once $middlewareFile;

            $middlewareClass = $middleware;

            if (!class_exists($middlewareClass)) {
                http_response_code(500);
                echo "Middleware class not found.";
                exit;
            }

            $middlewareClass::handle();
        }

        // Run controller
        [$controllerClass, $methodName] = $route['handler'];

        $controllerFile =
            __DIR__ . "/../app/Controllers/{$controllerClass}.php";

        require_once $controllerFile;

        $controller = new $controllerClass();

        $controller->$methodName();
    }
}