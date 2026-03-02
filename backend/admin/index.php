<?php

require_once __DIR__ . '/../db.php';

$pdo = get_pdo();

// Lista simples de cursos e link para gerenciar ofertas por curso.
$stmt = $pdo->query("
    SELECT c.id, c.name, c.code, co.name AS coordinator
    FROM courses c
    LEFT JOIN coordinators co ON co.id = c.coordinator_id
    ORDER BY c.name
");

$courses = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel START UniSenac - Cursos</title>
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
    </style>
</head>
<body>
    <h1>Painel administrativo – Cursos</h1>
    <p>Escolha um curso para gerenciar as unidades curriculares (incluindo optativas e compartilhadas).</p>

    <table>
        <thead>
        <tr>
            <th>Código</th>
            <th>Curso</th>
            <th>Coordenador</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td><?= htmlspecialchars($course['code']) ?></td>
                <td><?= htmlspecialchars($course['name']) ?></td>
                <td><?= htmlspecialchars($course['coordinator'] ?? '—') ?></td>
                <td>
                    <a href="ofertas.php?course_id=<?= (int) $course['id'] ?>">Gerenciar ofertas</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

