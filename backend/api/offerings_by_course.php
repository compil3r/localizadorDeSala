<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'course_id inválido']);
    exit;
}

$pdo = get_pdo();

$sql = "
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
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['course_id' => $courseId]);

echo json_encode([
    'data' => $stmt->fetchAll(),
]);

