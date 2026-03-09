<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

session_start();

$adminBase = defined('APP_ADMIN_BASE') ? APP_ADMIN_BASE : '/admin';

if (!empty($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $adminBase . '/login.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Informe e-mail e senha.';
    } else {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email AND active = 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Credenciais inválidas.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            header('Location: ' . $adminBase . '/index.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login – Painel Salas UniSenac</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    >
    <link rel="stylesheet" href="<?= $adminBase ?>/admin.css">
</head>
<body class="auth-body d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow auth-card">
        <div class="card-body">
        <h1 class="h4 mb-3">Login – Painel Salas UniSenac</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input id="email" class="form-control" type="email" name="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Senha</label>
                <input id="password" class="form-control" type="password" name="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Entrar</button>
        </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
</body>
</html>

