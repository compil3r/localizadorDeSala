<?php

require_once __DIR__ . '/../auth.php';

auth_require_login();

$pdo  = get_pdo();
$user = auth_current_user();

// Admin enxerga todos os cursos; coordenador enxerga só os seus
if ($user['role'] === 'ADMIN') {
    $stmt = $pdo->query("
        SELECT c.id, c.name, c.code, co.name AS coordinator
        FROM courses c
        LEFT JOIN coordinators co ON co.id = c.coordinator_id
        ORDER BY c.name
    ");
    $courses = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.code, co.name AS coordinator
        FROM courses c
        LEFT JOIN coordinators co ON co.id = c.coordinator_id
        WHERE c.coordinator_id = :coord_id
        ORDER BY c.name
    ");
    $stmt->execute(['coord_id' => (int) $user['coordinator_id']]);
    $courses = $stmt->fetchAll();
}

$navCurrent = 'oferta';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Oferta 2026/1 – Painel Salas UniSenac</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    >
    <link rel="stylesheet" href="<?= (defined('APP_BASE_PATH') ? APP_BASE_PATH : '') ?>/backend/admin/admin.css">
</head>
<body>
    <div class="container py-4">
    <?php require __DIR__ . '/header.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Oferta 2026/1</h1>
            <p class="mb-0 text-muted">Escolha um curso para gerenciar ofertas (disciplinas próprias, optativas e compartilhadas).</p>
        </div>
    </div>

    <div class="page-content">
    <table class="table table-hover align-middle bg-white">
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
                    <a class="btn btn-sm btn-primary" href="ofertas.php?course_id=<?= (int) $course['id'] ?>">Gerenciar ofertas</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
  </body>
</html>

