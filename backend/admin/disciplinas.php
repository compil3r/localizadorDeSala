<?php

require_once __DIR__ . '/../auth.php';

auth_require_login();

$pdo  = get_pdo();
$user = auth_current_user();
$isAdmin = $user['role'] === 'ADMIN';

// Busca lista de cursos permitidos para o usuário
if ($isAdmin) {
    $courseStmt = $pdo->query('SELECT id, name, code FROM courses ORDER BY name');
} else {
    $courseStmt = $pdo->prepare('SELECT id, name, code FROM courses WHERE coordinator_id = :coord_id ORDER BY name');
    $courseStmt->execute(['coord_id' => (int) $user['coordinator_id']]);
}
$courses = $courseStmt->fetchAll();
$allowedCourseIds = array_map(fn ($c) => (int) $c['id'], $courses);

// Atualização de disciplina (nome + curso pertencente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id       = (int) ($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $courseId = (int) ($_POST['owning_course_id'] ?? 0);

    $canUseCourse = $isAdmin || in_array($courseId, $allowedCourseIds, true);

    if ($id > 0 && $name !== '' && $courseId > 0 && $canUseCourse) {
        // Coordenador só pode alterar disciplinas de cursos que coordena
        if ($isAdmin) {
            $owningFilter = '1=1';
            $params = [];
        } else {
            $owningFilter = 'owning_course_id IN (' . implode(',', $allowedCourseIds ?: [0]) . ')';
            $params = [];
        }

        $sql = "
            UPDATE disciplines
            SET name = :name,
                owning_course_id = :course_id
            WHERE id = :id
              AND {$owningFilter}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params + [
            'name'      => $name,
            'course_id' => $courseId,
            'id'        => $id,
        ]);
    }
}

// Lista de disciplinas com curso de origem
if ($isAdmin) {
    $stmt = $pdo->query('
        SELECT d.id, d.name, c.id AS course_id, c.name AS course_name, c.code AS course_code
        FROM disciplines d
        LEFT JOIN courses c ON c.id = d.owning_course_id
        ORDER BY d.name
    ');
} else {
    if ($allowedCourseIds) {
        $placeholders = implode(',', array_fill(0, count($allowedCourseIds), '?'));
        $sql = "
            SELECT d.id, d.name, c.id AS course_id, c.name AS course_name, c.code AS course_code
            FROM disciplines d
            LEFT JOIN courses c ON c.id = d.owning_course_id
            WHERE d.owning_course_id IN ({$placeholders})
            ORDER BY d.name
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($allowedCourseIds);
    } else {
        $disciplines = [];
        $stmt = null;
    }
}
if (isset($stmt)) {
    $disciplines = $stmt->fetchAll();
}

$navCurrent = 'ucs';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Unidades curriculares – Painel Salas UniSenac</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    >
    <link rel="stylesheet" href="/backend/admin/admin.css">
</head>
<body>
    <div class="container py-4">
    <?php require __DIR__ . '/header.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Unidades curriculares</h1>
            <p class="mb-0 text-muted">
                <?= $isAdmin
                    ? 'Você é administrador: pode alterar qualquer disciplina e curso de origem.'
                    : 'Você é coordenador: pode alterar disciplinas dos cursos sob sua coordenação.' ?>
            </p>
        </div>
    </div>

    <div class="page-content">
    <table class="table table-hover align-middle bg-white">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nome da disciplina</th>
            <th>Curso pertencente</th>
            <th>Ação</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($disciplines as $disc): ?>
            <tr>
                <td><?= (int) $disc['id'] ?></td>
                <td>
                    <?= htmlspecialchars($disc['name']) ?>
                </td>
                <td>
                    <?php if ($disc['course_id']): ?>
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars(($disc['course_code'] ?? '') . ' - ' . ($disc['course_name'] ?? '')) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-dark">Sem curso definido</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary btn-edit"
                        data-id="<?= (int) $disc['id'] ?>"
                        data-name="<?= htmlspecialchars($disc['name'], ENT_QUOTES) ?>"
                        data-course-id="<?= (int) $disc['course_id'] ?>"
                    >
                        Editar
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Modal para editar disciplina -->
    <div class="modal fade" id="modal-backdrop" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="modal-title">Editar disciplina</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post" id="modal-form" class="modal-body">
                <input type="hidden" name="action" id="modal-action" value="update">
                <input type="hidden" name="id" id="modal-id" value="">

                <div class="mb-3">
                    <label class="form-label" for="modal-name">Nome da disciplina</label>
                    <input class="form-control" type="text" name="name" id="modal-name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="modal-course">Curso pertencente</label>
                    <select class="form-select" name="owning_course_id" id="modal-course" required>
                        <option value="">Selecione um curso</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int) $c['id'] ?>">
                                <?= htmlspecialchars($c['code'] . ' - ' . $c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-outline-secondary" id="modal-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Salvar</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script>
        (function () {
            const backdropEl = document.getElementById('modal-backdrop');
            const bootstrapModal = new bootstrap.Modal(backdropEl);
            const title = document.getElementById('modal-title');
            const actionInput = document.getElementById('modal-action');
            const idInput = document.getElementById('modal-id');
            const nameInput = document.getElementById('modal-name');
            const courseSelect = document.getElementById('modal-course');

            document.querySelectorAll('.btn-edit').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const courseId = this.getAttribute('data-course-id');
                    title.textContent = 'Editar disciplina';
                    actionInput.value = 'update';
                    idInput.value = id || '';
                    nameInput.value = name || '';
                    courseSelect.value = courseId || '';
                    bootstrapModal.show();
                });
            });
        })();
    </script>
</body>
</html>

