<?php
function load_env_file($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load default environment variables from .env first.
$envFile = __DIR__ . '/../.env';
load_env_file($envFile);

// In production (or when running on a non-local domain), allow .env.production to override defaults.
$appEnv = strtolower(trim($_ENV['APP_ENV'] ?? 'development'));
$serverName = strtolower(trim($_SERVER['SERVER_NAME'] ?? ''));
$isLocalHost = in_array($serverName, ['', 'localhost', '127.0.0.1'], true);
$useProductionEnv = ($appEnv === 'production') || !$isLocalHost;
if ($useProductionEnv) {
    load_env_file(__DIR__ . '/../.env.production');
}

// Cấu hình DB MySQL từ environment variables
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'fixedweb');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
