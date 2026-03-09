<?php

// Configurações básicas do backend PHP.
// Ajuste estes valores conforme o ambiente (dev / produção).
//
// Carrega variáveis do arquivo .env (raiz do projeto) se existir
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile) && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if ($key !== '') putenv("$key=$val");
        }
    }
}

// KIOSK_BASE_PATH: pasta do projeto no servidor (ex: /salas se em exemplo.com/salas/)
$basePath = rtrim(getenv('KIOSK_BASE_PATH') ?: '', '/');
// $basePath = '/salas';
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', $basePath);
}

return [
    'base_path' => $basePath,
    'db' => [
        'dsn' => getenv('KIOSK_DB_DSN') ?: 'mysql:host=localhost;dbname=start_unisenac;charset=utf8mb4',
        'user' => getenv('KIOSK_DB_USER') ?: 'root',
        'password' => getenv('KIOSK_DB_PASSWORD') ?: 'comidinha15',
    ],
];

