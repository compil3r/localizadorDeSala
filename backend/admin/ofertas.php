<?php

require_once __DIR__ . '/../auth.php';

auth_require_login();

$pdo  = get_pdo();
$user = auth_current_user();

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($courseId <= 0) {
    http_response_code(400);
    echo 'course_id inválido';
    exit;
}

// Dados do curso
$courseStmt = $pdo->prepare("
    SELECT c.id, c.name, c.code, c.coordinator_id, co.name AS coordinator
    FROM courses c
    LEFT JOIN coordinators co ON co.id = c.coordinator_id
    WHERE c.id = :id
");
$courseStmt->execute(['id' => $courseId]);
$course = $courseStmt->fetch();

if (!$course) {
    http_response_code(404);
    echo 'Curso não encontrado';
    exit;
}

// Coordenador só pode acessar cursos sob sua gestão
if ($user['role'] === 'COORDINATOR' && (int) $course['coordinator_id'] !== (int) $user['coordinator_id']) {
    http_response_code(403);
    echo 'Você não tem permissão para gerenciar este curso.';
    exit;
}

// Criação de nova oferta (compartilhada ou optativa) – copia uma oferta existente de outro curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_offering') {
    $sourceOfferingId = (int) ($_POST['offering_id'] ?? 0);
    $originType      = $_POST['origin_type'] ?? 'COMPARTILHADA';

    $validOrigins = ['COMPARTILHADA', 'OPTATIVA'];

    if ($sourceOfferingId > 0 && in_array($originType, $validOrigins, true)) {
        $srcStmt = $pdo->prepare('
            SELECT discipline_id, teacher_id, turno, dia_semana, room
            FROM course_offerings
            WHERE id = :id AND course_id != :course_id
        ');
        $srcStmt->execute(['id' => $sourceOfferingId, 'course_id' => $courseId]);
        $src = $srcStmt->fetch();

        if ($src) {
            $stmt = $pdo->prepare('
                INSERT INTO course_offerings (course_id, discipline_id, teacher_id, turno, dia_semana, room, origin_type)
                VALUES (:course_id, :discipline_id, :teacher_id, :turno, :dia_semana, :room, :origin_type)
            ');
            $stmt->execute([
                'course_id'     => $courseId,
                'discipline_id' => $src['discipline_id'],
                'teacher_id'    => $src['teacher_id'],
                'turno'         => $src['turno'],
                'dia_semana'    => $src['dia_semana'],
                'room'          => $src['room'],
                'origin_type'   => $originType,
            ]);
            header('Location: ofertas.php?course_id=' . $courseId);
            exit;
        }
    }
}

// Remoção de oferta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_offering') {
    $offeringId   = (int) ($_POST['offering_id'] ?? 0);
    $deleteScope  = $_POST['delete_scope'] ?? 'current'; // 'current' | 'all'

    if ($offeringId > 0) {
        $offStmt = $pdo->prepare('
            SELECT id, course_id, discipline_id, teacher_id, turno, dia_semana, origin_type
            FROM course_offerings
            WHERE id = :id
        ');
        $offStmt->execute(['id' => $offeringId]);
        $off = $offStmt->fetch();

        if ($off && (int) $off['course_id'] === $courseId) {
            if (in_array($off['origin_type'], ['COMPARTILHADA', 'OPTATIVA'], true)) {
                // Compartilhada/optativa: remove apenas do curso atual
                $pdo->prepare('DELETE FROM course_offerings WHERE id = :id')->execute(['id' => $offeringId]);
            } elseif ($deleteScope === 'all') {
                // PROPRIA: remove de todos (mesma disciplina, professor, turno, dia)
                $delStmt = $pdo->prepare('
                    DELETE FROM course_offerings
                    WHERE discipline_id = :did AND teacher_id = :tid AND turno = :turno AND dia_semana = :dia
                ');
                $delStmt->execute([
                    'did'  => $off['discipline_id'],
                    'tid'  => $off['teacher_id'],
                    'turno' => $off['turno'],
                    'dia'  => $off['dia_semana'],
                ]);
            } else {
                // PROPRIA: remove apenas do curso atual
                $pdo->prepare('DELETE FROM course_offerings WHERE id = :id')->execute(['id' => $offeringId]);
            }
            header('Location: ofertas.php?course_id=' . $courseId);
            exit;
        }
    }
}

// Lista atual de ofertas (inclui próprias, optativas e compartilhadas)
$offerStmt = $pdo->prepare("
    SELECT
        o.id,
        o.discipline_id,
        o.teacher_id,
        o.turno,
        o.dia_semana,
        d.name       AS disciplina,
        t.name       AS professor,
        o.room       AS sala,
        o.observation,
        o.origin_type,
        (SELECT COUNT(*) FROM course_offerings o2
         WHERE o2.discipline_id = o.discipline_id
           AND o2.teacher_id = o.teacher_id
           AND o2.turno = o.turno
           AND o2.dia_semana = o.dia_semana
           AND o2.course_id != :course_id_sub) AS other_courses_count
    FROM course_offerings o
    INNER JOIN disciplines d ON d.id = o.discipline_id
    INNER JOIN teachers t    ON t.id = o.teacher_id
    WHERE o.course_id = :course_id
    ORDER BY d.name
");
$offerStmt->execute(['course_id' => $courseId, 'course_id_sub' => $courseId]);
$offerings = $offerStmt->fetchAll();

// Todas as ofertas de OUTROS cursos (cada oferta = uma opção para incluir)
$offeringsStmt = $pdo->prepare('
    SELECT o.id,
           c.code AS course_code,
           c.name AS course_name,
           d.name AS discipline_name,
           o.turno,
           t.name AS professor_name,
           o.dia_semana,
           o.room
    FROM course_offerings o
    INNER JOIN courses c ON c.id = o.course_id
    INNER JOIN disciplines d ON d.id = o.discipline_id
    INNER JOIN teachers t ON t.id = o.teacher_id
    WHERE o.course_id != :course_id
    ORDER BY c.code, d.name, o.turno, t.name
');
$offeringsStmt->execute(['course_id' => $courseId]);
$allOfferings = $offeringsStmt->fetchAll();

$navCurrent = 'oferta';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Salas UniSenac - Ofertas</title>
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
            <p class="mb-1"><a href="index.php">&larr; Oferta 2026/1</a></p>
            <h1 class="h4 mb-1">Ofertas – <?= htmlspecialchars($course['name']) ?> (<?= htmlspecialchars($course['code']) ?>)</h1>
            <p class="mb-0 text-muted">Inclui disciplinas próprias, optativas e compartilhadas deste curso.</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-open-offering">Nova oferta</button>
        </div>
    </div>

    <div class="page-content">
    <table class="table table-hover align-middle bg-white">
        <thead>
        <tr>
            <th>Disciplina</th>
            <th>Professor</th>
            <th>Turno</th>
            <th>Dia</th>
            <th>Sala</th>
            <th>Origem</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($offerings as $o): ?>
            <?php $otherCount = (int) ($o['other_courses_count'] ?? 0); ?>
            <tr>
                <td><?= htmlspecialchars($o['disciplina']) ?></td>
                <td><?= htmlspecialchars($o['professor']) ?></td>
                <td><?= htmlspecialchars($o['turno']) ?></td>
                <td><?= htmlspecialchars($o['dia_semana']) ?></td>
                <td><?= htmlspecialchars($o['sala'] ?? '—') ?></td>
                <td>
                    <span class="pill"><?= htmlspecialchars($o['origin_type']) ?></span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-offering"
                            data-offering-id="<?= (int) $o['id'] ?>"
                            data-origin-type="<?= htmlspecialchars($o['origin_type']) ?>"
                            data-other-count="<?= $otherCount ?>">
                        Remover
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$offerings): ?>
            <tr>
                <td colspan="7">Nenhuma oferta cadastrada ainda.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <form id="form-delete-offering" method="post" style="display:none">
        <input type="hidden" name="action" value="delete_offering">
        <input type="hidden" name="offering_id" id="delete-offering-id">
        <input type="hidden" name="delete_scope" id="delete-scope">
    </form>

    <!-- Modal confirmar remoção (quando afeta outros cursos) -->
    <div class="modal fade" id="modal-delete-all" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0">Remover oferta</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p id="delete-all-msg">Esta oferta está presente em outros cursos. Remover irá remover de todos. Deseja continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-delete-all">Sim, remover de todos</button>
            </div>
        </div>
        </div>
    </div>

    <!-- Modal nova oferta -->
    <div class="modal fade" id="modal-offering-backdrop" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0">Adicionar nova oferta</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post" class="modal-body">
                <input type="hidden" name="action" value="create_offering">

                <div class="mb-3">
                    <label class="form-label">Oferta</label>
                    <select class="form-select" name="offering_id" id="offering-select" required>
                        <option value="">Selecione uma oferta de outro curso</option>
                        <?php foreach ($allOfferings as $o): ?>
                            <?php
                            $label = sprintf(
                                '%s | %s | %s | Prof. %s',
                                htmlspecialchars($o['course_code'] ?? '??'),
                                htmlspecialchars($o['discipline_name']),
                                htmlspecialchars($o['turno']),
                                htmlspecialchars($o['professor_name'])
                            );
                            $extra = array_filter([$o['dia_semana'] ?? '', $o['room'] ?? '']);
                            if (!empty($extra)) {
                                $label .= ' · ' . htmlspecialchars(implode(', ', $extra));
                            }
                            ?>
                            <option value="<?= (int) $o['id'] ?>" title="<?= htmlspecialchars($label) ?>">
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Curso | Disciplina | Turno | Professor (dia, sala)</div>
                    <?php if (empty($allOfferings)): ?>
                        <div class="form-text text-muted">Nenhuma oferta de outro curso disponível.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo da oferta</label>
                    <select class="form-select" name="origin_type" required>
                        <option value="COMPARTILHADA">Compartilhada</option>
                        <option value="OPTATIVA">Optativa</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-outline-secondary" id="btn-cancel-offering" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Adicionar</button>
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
            const modalNew = new bootstrap.Modal(document.getElementById('modal-offering-backdrop'));
            const modalDeleteAll = new bootstrap.Modal(document.getElementById('modal-delete-all'));
            const formDelete = document.getElementById('form-delete-offering');
            const inputOfferingId = document.getElementById('delete-offering-id');
            const inputScope = document.getElementById('delete-scope');
            const msgDeleteAll = document.getElementById('delete-all-msg');
            const btnConfirmDeleteAll = document.getElementById('btn-confirm-delete-all');

            document.getElementById('btn-open-offering')?.addEventListener('click', function () { modalNew.show(); });

            document.querySelectorAll('.btn-remove-offering').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.dataset.offeringId;
                    const origin = this.dataset.originType;
                    const otherCount = parseInt(this.dataset.otherCount || '0', 10);

                    inputOfferingId.value = id;

                    if (origin === 'COMPARTILHADA' || origin === 'OPTATIVA') {
                        if (confirm('Remover esta oferta do curso?')) {
                            inputScope.value = 'current';
                            formDelete.submit();
                        }
                    } else if (otherCount > 0) {
                        msgDeleteAll.textContent = 'Esta oferta está presente em ' + otherCount + ' outro(s) curso(s). Remover irá remover de todos. Deseja continuar?';
                        modalDeleteAll.show();
                    } else {
                        if (confirm('Remover esta oferta?')) {
                            inputScope.value = 'current';
                            formDelete.submit();
                        }
                    }
                });
            });

            btnConfirmDeleteAll?.addEventListener('click', function () {
                inputScope.value = 'all';
                formDelete.submit();
            });
        })();
    </script>
</body>
</html>

