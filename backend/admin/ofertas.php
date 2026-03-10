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
            $dupStmt = $pdo->prepare('
                SELECT 1 FROM course_offerings
                WHERE course_id = :course_id
                  AND discipline_id = :discipline_id
                  AND teacher_id = :teacher_id
                  AND turno = :turno
                  AND dia_semana = :dia_semana
                LIMIT 1
            ');
            $dupStmt->execute([
                'course_id'     => $courseId,
                'discipline_id' => $src['discipline_id'],
                'teacher_id'    => $src['teacher_id'],
                'turno'         => $src['turno'],
                'dia_semana'    => $src['dia_semana'],
            ]);

            if ($dupStmt->fetch()) {
                $_SESSION['oferta_error'] = 'Esta oferta já está incluída neste curso (mesma disciplina, professor, turno e dia).';
            } else {
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
            }
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

// Edição de oferta própria: docente, turno, dia e sala; propaga para cursos que compartilham a UC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_offering') {
    $offeringId = (int) ($_POST['offering_id'] ?? 0);
    $newTeacherId = (int) ($_POST['teacher_id'] ?? 0);
    $newTurno = $_POST['turno'] ?? '';
    $newDia = $_POST['dia_semana'] ?? '';
    $newRoom = trim($_POST['room'] ?? '');

    $validTurno = ['MANHA', 'NOITE'];
    $validDia = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];

    if ($offeringId > 0 && $newTeacherId > 0 && in_array($newTurno, $validTurno, true) && in_array($newDia, $validDia, true)) {
        $offStmt = $pdo->prepare('
            SELECT id, course_id, discipline_id, teacher_id, turno, dia_semana, room, origin_type
            FROM course_offerings WHERE id = :id
        ');
        $offStmt->execute(['id' => $offeringId]);
        $off = $offStmt->fetch();

        if ($off && (int) $off['course_id'] === $courseId && $off['origin_type'] === 'PROPRIA') {
            $dupStmt = $pdo->prepare('
                SELECT 1 FROM course_offerings
                WHERE course_id = :course_id
                  AND discipline_id = :discipline_id
                  AND teacher_id = :teacher_id
                  AND turno = :turno
                  AND dia_semana = :dia_semana
                  AND id != :id
                LIMIT 1
            ');
            $dupStmt->execute([
                'course_id'     => $courseId,
                'discipline_id' => $off['discipline_id'],
                'teacher_id'    => $newTeacherId,
                'turno'         => $newTurno,
                'dia_semana'    => $newDia,
                'id'            => $offeringId,
            ]);

            if ($dupStmt->fetch()) {
                $_SESSION['oferta_error'] = 'Já existe outra oferta neste curso com esse docente, turno e dia.';
            } else {
                $updStmt = $pdo->prepare('
                    UPDATE course_offerings
                    SET teacher_id = :teacher_id, turno = :turno, dia_semana = :dia_semana, room = :room
                    WHERE discipline_id = :discipline_id
                      AND teacher_id = :old_teacher_id
                      AND turno = :old_turno
                      AND dia_semana = :old_dia
                ');
                $updStmt->execute([
                    'teacher_id'    => $newTeacherId,
                    'turno'         => $newTurno,
                    'dia_semana'    => $newDia,
                    'room'          => $newRoom === '' ? null : $newRoom,
                    'discipline_id' => $off['discipline_id'],
                    'old_teacher_id' => $off['teacher_id'],
                    'old_turno'     => $off['turno'],
                    'old_dia'       => $off['dia_semana'],
                ]);
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

// Lista de docentes (para o modal de edição de oferta própria)
$teachersStmt = $pdo->query('SELECT id, name FROM teachers ORDER BY name');
$teachers = $teachersStmt ? $teachersStmt->fetchAll() : [];

// Todas as ofertas de OUTROS cursos (cada oferta = uma opção para incluir)
$offeringsStmt = $pdo->prepare('
    SELECT o.id,
           o.discipline_id,
           o.teacher_id,
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
$allOfferingsRaw = $offeringsStmt->fetchAll();

$alreadyInCourse = [];
foreach ($offerings as $o) {
    $key = $o['discipline_id'] . '|' . $o['teacher_id'] . '|' . $o['turno'] . '|' . $o['dia_semana'];
    $alreadyInCourse[$key] = true;
}
$allOfferings = array_filter($allOfferingsRaw, function ($o) use ($alreadyInCourse) {
    $key = $o['discipline_id'] . '|' . $o['teacher_id'] . '|' . $o['turno'] . '|' . ($o['dia_semana'] ?? '');
    return !isset($alreadyInCourse[$key]);
});

$navCurrent = 'oferta';
$ofertaError = $_SESSION['oferta_error'] ?? null;
if (isset($_SESSION['oferta_error'])) unset($_SESSION['oferta_error']);
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
    <link rel="stylesheet" href="<?= (defined('APP_ADMIN_BASE') ? APP_ADMIN_BASE : '/admin') ?>/admin.css">
</head>
<body>
    <div class="container py-4">
    <?php require __DIR__ . '/header.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Ofertas – <?= htmlspecialchars($course['name']) ?> (<?= htmlspecialchars($course['code']) ?>)</h1>
            <p class="mb-0 text-muted">Inclui disciplinas próprias, optativas e compartilhadas deste curso.</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-open-offering">Nova oferta</button>
        </div>
    </div>

    <?php if ($ofertaError): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($ofertaError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>

    <div class="page-content">
    <table class="table table-hover align-middle bg-white">
        <thead>
        <tr>
            <th>Disciplina</th>
            <th>Professor</th>
            <th>Turno</th>
            <th class="sortable" data-sort="dia" role="button" tabindex="0">Dia <span class="sort-indicator" aria-hidden="true"></span></th>
            <th>Sala</th>
            <th>Origem</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($offerings as $o): ?>
            <?php $otherCount = (int) ($o['other_courses_count'] ?? 0); ?>
            <tr data-dia="<?= htmlspecialchars($o['dia_semana'] ?? '') ?>">
                <td><?= htmlspecialchars($o['disciplina']) ?></td>
                <td><?= htmlspecialchars($o['professor']) ?></td>
                <td><?= htmlspecialchars($o['turno']) ?></td>
                <td><?= htmlspecialchars($o['dia_semana']) ?></td>
                <td><?= htmlspecialchars($o['sala'] ?? '—') ?></td>
                <td>
                    <span class="pill"><?= htmlspecialchars($o['origin_type']) ?></span>
                </td>
                <td>
                    <?php if ($o['origin_type'] === 'PROPRIA'): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-edit-offering me-1"
                            data-offering-id="<?= (int) $o['id'] ?>"
                            data-teacher-id="<?= (int) $o['teacher_id'] ?>"
                            data-turno="<?= htmlspecialchars($o['turno']) ?>"
                            data-dia="<?= htmlspecialchars($o['dia_semana']) ?>"
                            data-room="<?= htmlspecialchars($o['sala'] ?? '') ?>"
                            title="Alterar docente, turno, dia e sala (afeta cursos que compartilham esta UC)">
                        Editar
                    </button>
                    <?php endif; ?>
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

    <!-- Modal editar oferta própria (docente, turno, dia) -->
    <div class="modal fade" id="modal-edit-offering" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0">Editar oferta própria</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post" id="form-edit-offering" class="modal-body">
                <input type="hidden" name="action" value="edit_offering">
                <input type="hidden" name="offering_id" id="edit-offering-id" value="">
                <p class="text-muted small mb-3">A alteração vale para todos os cursos que compartilham esta unidade curricular.</p>
                <div class="mb-3">
                    <label class="form-label" for="edit-teacher">Docente</label>
                    <select class="form-select" name="teacher_id" id="edit-teacher" required>
                        <option value="">Selecione o docente</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="edit-turno">Turno</label>
                    <select class="form-select" name="turno" id="edit-turno" required>
                        <option value="MANHA">Manhã</option>
                        <option value="NOITE">Noite</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="edit-dia">Dia da semana</label>
                    <select class="form-select" name="dia_semana" id="edit-dia" required>
                        <option value="SEG">Segunda</option>
                        <option value="TER">Terça</option>
                        <option value="QUA">Quarta</option>
                        <option value="QUI">Quinta</option>
                        <option value="SEX">Sexta</option>
                        <option value="SAB">Sábado</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="edit-room">Sala</label>
                    <input type="text" class="form-control" name="room" id="edit-room" maxlength="32" placeholder="Ex: 203">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
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
            const DIA_ORDEM = { SEG: 1, TER: 2, QUA: 3, QUI: 4, SEX: 5, SAB: 6 };
            const tbody = document.querySelector('.page-content table tbody');
            const thDia = document.querySelector('th[data-sort="dia"]');
            let sortDiaAsc = true;

            function sortByDia() {
                const rows = Array.from(tbody.querySelectorAll('tr[data-dia]'));
                const fallback = (v) => DIA_ORDEM[v] ?? 99;
                rows.sort((a, b) => {
                    const va = fallback(a.dataset.dia);
                    const vb = fallback(b.dataset.dia);
                    return sortDiaAsc ? va - vb : vb - va;
                });
                rows.forEach(r => tbody.appendChild(r));
                thDia.querySelector('.sort-indicator').textContent = sortDiaAsc ? ' ▲' : ' ▼';
                sortDiaAsc = !sortDiaAsc;
            }

            thDia?.addEventListener('click', sortByDia);
            thDia?.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sortByDia(); } });
            sortByDia(); // ordem inicial por dia
            const modalNew = new bootstrap.Modal(document.getElementById('modal-offering-backdrop'));
            const modalEdit = new bootstrap.Modal(document.getElementById('modal-edit-offering'));
            const modalDeleteAll = new bootstrap.Modal(document.getElementById('modal-delete-all'));
            const formEdit = document.getElementById('form-edit-offering');
            const editOfferingId = document.getElementById('edit-offering-id');
            const editTeacher = document.getElementById('edit-teacher');
            const editTurno = document.getElementById('edit-turno');
            const editDia = document.getElementById('edit-dia');
            const editRoom = document.getElementById('edit-room');

            document.querySelectorAll('.btn-edit-offering').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    editOfferingId.value = this.dataset.offeringId;
                    editTeacher.value = this.dataset.teacherId;
                    editTurno.value = this.dataset.turno;
                    editDia.value = this.dataset.dia;
                    editRoom.value = this.dataset.room || '';
                    modalEdit.show();
                });
            });
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

