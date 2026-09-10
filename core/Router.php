<?php

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

    public function dispatch(
        string $uri,
        string $method
    ): void {
        $uri = parse_url($uri, PHP_URL_PATH);

        if (!isset($this->routes[$method][$uri])) {
            http_response_code(404);
            echo "404 - Page not found";
            return;
        }

        $route = $this->routes[$method][$uri];


        /*
        |--------------------------------------------------------------------------
        | Run Middleware
        |--------------------------------------------------------------------------
        */

        foreach ($route['middleware'] as $middleware) {

            $middlewareFile =
                __DIR__
                . "/../app/Middlewares/{$middleware}.php";

            if (!file_exists($middlewareFile)) {

                http_response_code(500);

                echo "Middleware file not found: "
                    . htmlspecialchars($middleware);

                exit;
            }

            require_once $middlewareFile;


            if (!class_exists($middleware)) {

                http_response_code(500);

                echo "Middleware class not found: "
                    . htmlspecialchars($middleware);

                exit;
            }


            if (!method_exists($middleware, 'handle')) {

                http_response_code(500);

                echo "Middleware handle method not found: "
                    . htmlspecialchars($middleware);

                exit;
            }


            $middleware::handle();
        }


        /*
        |--------------------------------------------------------------------------
        | Run Controller
        |--------------------------------------------------------------------------
        */

        [$controllerClass, $methodName] =
            $route['handler'];


        $controllerFile =
            __DIR__
            . "/../app/Controllers/{$controllerClass}.php";


        if (!file_exists($controllerFile)) {

            http_response_code(500);

            echo "Controller file not found: "
                . htmlspecialchars($controllerClass);

            return;
        }


        require_once $controllerFile;


        if (!class_exists($controllerClass)) {

            http_response_code(500);

            echo "Controller class not found: "
                . htmlspecialchars($controllerClass);

            return;
        }


        $controller = new $controllerClass();


        if (!method_exists($controller, $methodName)) {

            http_response_code(500);

            echo "Controller method not found: "
                . htmlspecialchars($methodName);

            return;
        }


        $controller->$methodName();
    }
}