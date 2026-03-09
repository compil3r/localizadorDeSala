<?php

// Configurações básicas do backend PHP.
// Ajuste estes valores conforme o ambiente (dev / produção).
//
// KIOSK_BASE_PATH: pasta do projeto no servidor (ex: /salas se em exemplo.com/salas/)
// Use variável de ambiente KIOSK_BASE_PATH, ou descomente a linha abaixo:
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

