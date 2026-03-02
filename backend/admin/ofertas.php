<?php

require_once __DIR__ . '/../db.php';

$pdo = get_pdo();

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($courseId <= 0) {
    http_response_code(400);
    echo 'course_id inválido';
    exit;
}

// Dados do curso
$courseStmt = $pdo->prepare("
    SELECT c.id, c.name, c.code, co.name AS coordinator
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

// Lista atual de ofertas (inclui próprias, optativas e compartilhadas)
$offerStmt = $pdo->prepare("
    SELECT
        o.id,
        d.name       AS disciplina,
        t.name       AS professor,
        o.turno,
        o.dia_semana,
        o.room       AS sala,
        o.observation,
        o.origin_type
    FROM course_offerings o
    INNER JOIN disciplines d ON d.id = o.discipline_id
    INNER JOIN teachers t    ON t.id = o.teacher_id
    WHERE o.course_id = :course_id
    ORDER BY d.name
");
$offerStmt->execute(['course_id' => $courseId]);
$offerings = $offerStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel START UniSenac - Ofertas</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0b0b16;
            color: #f5f5ff;
            margin: 0;
            padding: 24px;
        }
        h1 {
            margin-top: 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 16px;
            background: #141428;
        }
        th, td {
            border: 1px solid #33334d;
            padding: 8px 12px;
        }
        th {
            background: #202040;
            text-align: left;
        }
        a {
            color: #4cfffb;
        }
        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            background: #202040;
        }
    </style>
</head>
<body>
    <p><a href="index.php">&larr; Voltar para cursos</a></p>
    <h1>Ofertas – <?= htmlspecialchars($course['name']) ?> (<?= htmlspecialchars($course['code']) ?>)</h1>
    <p>Inclui disciplinas próprias, optativas e compartilhadas deste curso.</p>

    <table>
        <thead>
        <tr>
            <th>Disciplina</th>
            <th>Professor</th>
            <th>Turno</th>
            <th>Dia</th>
            <th>Sala</th>
            <th>Origem</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($offerings as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['disciplina']) ?></td>
                <td><?= htmlspecialchars($o['professor']) ?></td>
                <td><?= htmlspecialchars($o['turno']) ?></td>
                <td><?= htmlspecialchars($o['dia_semana']) ?></td>
                <td><?= htmlspecialchars($o['sala'] ?? '—') ?></td>
                <td>
                    <span class="pill"><?= htmlspecialchars($o['origin_type']) ?></span>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$offerings): ?>
            <tr>
                <td colspan="6">Nenhuma oferta cadastrada ainda.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

