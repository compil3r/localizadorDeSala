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

// KIOSK_BASE_PATH: subpasta do projeto (ex: /salas se em exemplo.com/salas/). Vazio = raiz do site.
$basePath = rtrim(getenv('KIOSK_BASE_PATH') ?: '', '/');
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', $basePath);
}

// KIOSK_DOC_ROOT: 'backend' = doc root é a pasta backend (dev: php -S -t .)
//                 'project' = doc root é a raiz do projeto (VPS: apontado pra salas/)
$docRoot = getenv('KIOSK_DOC_ROOT') ?: 'project';

// Caminhos web
if ($basePath) {
    // Projeto em subpasta: /salas/backend/admin, /salas/backend/kiosk
    $adminBase = $basePath . '/backend/admin';
    $kioskBase = $basePath . '/backend/kiosk';
} elseif ($docRoot === 'backend') {
    // Dev local: doc root = backend → /admin, /kiosk
    $adminBase = '/admin';
    $kioskBase = '/kiosk';
} else {
    // VPS: doc root = projeto (salas/) → /backend/admin, /backend/kiosk
    $adminBase = '/backend/admin';
    $kioskBase = '/backend/kiosk';
}
if (!defined('APP_ADMIN_BASE')) define('APP_ADMIN_BASE', $adminBase);
if (!defined('APP_KIOSK_BASE')) define('APP_KIOSK_BASE', $kioskBase);

return [
    'base_path' => $basePath,
    'db' => [
        'dsn' => getenv('KIOSK_DB_DSN') ?: 'mysql:host=localhost;dbname=start_unisenac;charset=utf8mb4',
        'user' => getenv('KIOSK_DB_USER') ?: 'root',
        'password' => getenv('KIOSK_DB_PASSWORD') ?: 'comidinha15',
    ],
];

