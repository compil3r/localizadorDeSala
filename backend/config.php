<?php

// Configurações básicas do backend PHP.
// Ajuste estes valores conforme o ambiente (dev / produção).

return [
    'db' => [
        'dsn' => getenv('KIOSK_DB_DSN') ?: 'mysql:host=localhost;dbname=start_unisenac;charset=utf8mb4',
        'user' => getenv('KIOSK_DB_USER') ?: 'root',
        'password' => getenv('KIOSK_DB_PASSWORD') ?: 'comidinha15',
    ],
];

