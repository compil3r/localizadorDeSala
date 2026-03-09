<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$pdo = get_pdo();

// Horário e dia da semana (timezone Brasil)
$tz = new DateTimeZone('America/Sao_Paulo');
$now = new DateTime('now', $tz);
$h = (int) $now->format('G');
$w = (int) $now->format('w'); // 0=dom, 1=seg, ..., 6=sab

// Turno: MANHA (06-12), NOITE (18-23). Fora: 00-05 usa MANHA, 13-17 usa NOITE
$turno = ($h >= 6 && $h < 12) ? 'MANHA' : 'NOITE';

// Dia da semana: SEG, TER, QUA, QUI, SEX, SAB (domingo = sem aulas)
$dias = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];
$diaSemana = $dias[$w];

// Domingo: retorna lista vazia
if ($diaSemana === 'DOM') {
    echo json_encode([
        'cursos' => [],
        'meta' => [
            'turno' => $turno,
            'dia_semana' => $diaSemana,
            'hora' => $now->format('H:i'),
            'mensagem' => 'Nenhuma aula aos domingos.',
        ],
    ]);
    exit;
}

// Cursos que têm ofertas hoje neste turno
$sql = "
    SELECT DISTINCT c.id, c.name, c.code
    FROM courses c
    INNER JOIN course_offerings o ON o.course_id = c.id
    WHERE o.turno = :turno AND o.dia_semana = :dia
    ORDER BY c.name
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['turno' => $turno, 'dia' => $diaSemana]);
$courses = $stmt->fetchAll();

// Para cada curso, buscar ofertas (disciplinas) de hoje neste turno
$cursos = [];
foreach ($courses as $c) {
    $offStmt = $pdo->prepare("
        SELECT d.name AS disciplina, t.name AS professor, o.room AS sala
        FROM course_offerings o
        INNER JOIN disciplines d ON d.id = o.discipline_id
        INNER JOIN teachers t ON t.id = o.teacher_id
        WHERE o.course_id = :course_id
          AND o.turno = :turno
          AND o.dia_semana = :dia
        ORDER BY d.name
    ");
    $offStmt->execute([
        'course_id' => (int) $c['id'],
        'turno' => $turno,
        'dia' => $diaSemana,
    ]);
    $offerings = $offStmt->fetchAll();

    $disciplinas = array_map(function ($o) {
        return [
            'nome' => $o['disciplina'],
            'sala' => $o['sala'] ?? '',
            'docente' => $o['professor'],
        ];
    }, $offerings);

    $cursos[] = [
        'id' => (int) $c['id'],
        'codigoCurto' => $c['code'],
        'nome' => $c['name'],
        'disciplinas' => $disciplinas,
    ];
}

echo json_encode([
    'cursos' => $cursos,
    'meta' => [
        'turno' => $turno,
        'dia_semana' => $diaSemana,
        'hora' => $now->format('H:i'),
    ],
]);
