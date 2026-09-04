<?php
// core/Controller.php
abstract class Controller
{
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);
        require __DIR__ . "/../app/Views/{$viewPath}.php";
    }

    protected function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }
}