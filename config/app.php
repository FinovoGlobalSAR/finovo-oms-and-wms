<?php

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

loadEnv(__DIR__ . '/../.env');

return [
    'name' => env('APP_NAME', 'OMS + WMS'),
    'env' => env('APP_ENV', 'local'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
];