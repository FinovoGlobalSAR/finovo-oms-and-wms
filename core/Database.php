<?php

require_once __DIR__ . '/../config/app.php';

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $dbname = env('DB_DATABASE');
            $username = env('DB_USERNAME', 'root');
            $password = env('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$connection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Asli error server log mein likho; screen pe sirf development mein dikhao,
                // warna live pe database host/username users ko nazar aa jata.
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die(appDebug()
                    ? 'Database connection failed: ' . $e->getMessage()
                    : 'Database connection failed. Please try again later.');
            }
        }

        return self::$connection;
    }
}