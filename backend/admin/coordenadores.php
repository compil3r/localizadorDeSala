<?php

require_once __DIR__ . '/../auth.php';

auth_require_admin();

$pdo = get_pdo();

// Criação de coordenador + usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name !== '' && $email !== '' && $password !== '') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO coordinators (name) VALUES (:name)');
            $stmt->execute(['name' => $name]);
            $coordId = (int) $pdo->lastInsertId();

            $stmtUser = $pdo->prepare('
                INSERT INTO users (name, email, password_hash, role, coordinator_id)
                VALUES (:name, :email, :hash, "COORDINATOR", :coord_id)
            ');
            $stmtUser->execute([
                'name'     => $name,
                'email'    => $email,
                'hash'     => password_hash($password, PASSWORD_DEFAULT),
                'coord_id' => $coordId,
            ]);

            $pdo->commit();
            header('Location: coordenadores.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
        }
    }
}

// Reset de senha de um coordenador existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    if ($userId > 0 && $newPassword !== '') {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id AND role = "COORDINATOR"');
        $stmt->execute([
            'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id'   => $userId,
        ]);
        header('Location: coordenadores.php');
        exit;
    }
}

// Listagem de coordenadores + usuário
$stmt = $pdo->query('
    SELECT c.id AS coordinator_id,
           c.name AS coordinator_name,
           u.id AS user_id,
           u.email,
           u.active
    FROM coordinators c
    LEFT JOIN users u ON u.coordinator_id = c.id
    ORDER BY c.name
');
$rows = $stmt->fetchAll();

$navCurrent = 'coordenadores';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Coordenadores – Painel Salas UniSenac</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    >
    <link rel="stylesheet" href="/salas/backend/admin/admin.css">
</head>
<body>
    <div class="container py-4">
    <?php require __DIR__ . '/header.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Coordenadores</h1>
            <p class="mb-0 text-muted">Crie e gerencie contas de coordenadores, incluindo reset de senha.</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-open-create">Novo coordenador</button>
        </div>
    </div>

    <div class="page-content">
    <table class="table table-hover align-middle bg-white">
        <thead>
        <tr>
            <th>Nome</th>
            <th>E-mail (login)</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['coordinator_name']) ?></td>
                <td><?= htmlspecialchars($row['email'] ?? '—') ?></td>
                <td>
                    <?php if ($row['user_id']): ?>
                        <span class="pill"><?= !empty($row['active']) ? 'Ativo' : 'Inativo' ?></span>
                    <?php else: ?>
                        <span class="text-muted">Sem usuário</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($row['user_id']): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-reset-pw"
                                data-user-id="<?= (int) $row['user_id'] ?>"
                                data-name="<?= htmlspecialchars($row['coordinator_name'], ENT_QUOTES) ?>">
                            Resetar senha
                        </button>
                    <?php else: ?>
                        <em class="text-muted">Sem usuário vinculado</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr>
                <td colspan="4">Nenhum coordenador cadastrado.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Modal novo coordenador -->
    <div class="modal fade" id="modal-create" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0">Novo coordenador</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post" class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input class="form-control" type="text" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input class="form-control" type="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha inicial</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Criar coordenador</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <!-- Modal resetar senha -->
    <div class="modal fade" id="modal-reset-pw" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0">Resetar senha</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post" id="form-reset-pw" class="modal-body">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset-user-id">
                <p class="mb-3 text-muted" id="reset-pw-name"></p>
                <div class="mb-3">
                    <label class="form-label">Nova senha</label>
                    <input class="form-control" type="password" name="new_password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Alterar senha</button>
                </div>
            </form>
        </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script>
        (function () {
            const modalCreate = new bootstrap.Modal(document.getElementById('modal-create'));
            const modalResetPw = new bootstrap.Modal(document.getElementById('modal-reset-pw'));
            const formResetPw = document.getElementById('form-reset-pw');
            const inputUserId = document.getElementById('reset-user-id');
            const resetPwName = document.getElementById('reset-pw-name');

            document.getElementById('btn-open-create')?.addEventListener('click', function () { modalCreate.show(); });

            document.querySelectorAll('.btn-reset-pw').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    inputUserId.value = this.dataset.userId;
                    resetPwName.textContent = 'Alterar senha de: ' + (this.dataset.name || '');
                    modalResetPw.show();
                });
            });
        })();
    </script>
</body>
</html>

