<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$pdo = get_pdo();

// Lista todos os cursos com coordenador
$stmt = $pdo->query("
    SELECT c.id, c.name, c.code, co.name AS coordinator
    FROM courses c
    LEFT JOIN coordinators co ON co.id = c.coordinator_id
    ORDER BY c.name
");

echo json_encode([
    'data' => $stmt->fetchAll(),
]);

