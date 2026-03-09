<?php

require_once __DIR__ . '/db.php';

session_start();

function auth_current_user(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    if (empty($_SESSION['user_id'])) {
        $cached = null;
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, name, email, role, coordinator_id FROM users WHERE id = :id AND active = 1');
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    $cached = $user ?: null;
    return $cached;
}

function auth_require_login(): void
{
    if (!auth_current_user()) {
        header('Location: /salas/backend/admin/login.php');
        exit;
    }
}

function auth_require_admin(): void
{
    $user = auth_current_user();
    if (!$user || $user['role'] !== 'ADMIN') {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
}

