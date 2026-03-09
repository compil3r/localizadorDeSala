<?php

// Seed das ofertas de disciplinas (course_offerings) a partir do CSV aulas_26_1.csv.
//
// Uso:
//   php backend/seed_offerings_from_csv.php
//
// O script:
// - Lê backend/aulas_26_1.csv (separador ';')
// - Ignora cursos de BACHARELADO
// - Encontra o curso de tecnologia correspondente na tabela courses
// - Cria/atualiza teachers (sem duplicar professores)
// - Cria/atualiza disciplines por (nome + owning_course_id)
// - Cria registros em course_offerings com turno, dia_semana, sala, professor
// - Aplica capitalização amigável (Title Case) para nomes de disciplina/ professor

require_once __DIR__ . '/db.php';

$pdo = get_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$csvPath = __DIR__ . '/aulas_26_1.csv';

if (!is_file($csvPath)) {
    fwrite(STDERR, "Arquivo CSV não encontrado em {$csvPath}" . PHP_EOL);
    exit(1);
}

/**
 * Converte texto em Title Case preservando acentos.
 */
function to_title(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
}

/**
 * Normaliza o turno do CSV ("MANHÃ - M" / "NOITE - N") para ENUM MANHA/NOITE.
 */
function normalize_turno(string $raw): ?string
{
    $raw = mb_strtoupper(trim($raw), 'UTF-8');
    if (str_starts_with($raw, 'MANHÃ')) {
        return 'MANHA';
    }
    if (str_starts_with($raw, 'NOITE')) {
        return 'NOITE';
    }
    return null;
}

/**
 * Normaliza o dia da semana para ENUM SEG/TER/QUA/QUI/SEX/SAB.
 */
function normalize_dia(string $raw): ?string
{
    $raw = mb_strtoupper(trim($raw), 'UTF-8');
    if (str_starts_with($raw, 'SEGUNDA')) return 'SEG';
    if (str_starts_with($raw, 'TERÇA'))  return 'TER';
    if (str_starts_with($raw, 'QUARTA')) return 'QUA';
    if (str_starts_with($raw, 'QUINTA')) return 'QUI';
    if (str_starts_with($raw, 'SEXTA'))  return 'SEX';
    if (str_starts_with($raw, 'SÁBADO') || str_starts_with($raw, 'SABADO')) return 'SAB';
    return null;
}

// Prepared statements reutilizáveis
$stmtFindCourse = $pdo->prepare('SELECT id FROM courses WHERE UPPER(name) = :name LIMIT 1');
$stmtFindTeacher = $pdo->prepare('SELECT id FROM teachers WHERE name = :name LIMIT 1');
$stmtInsertTeacher = $pdo->prepare('INSERT INTO teachers (name) VALUES (:name)');
$stmtFindDiscipline = $pdo->prepare('SELECT id FROM disciplines WHERE name = :name AND owning_course_id = :course_id LIMIT 1');
$stmtInsertDiscipline = $pdo->prepare('INSERT INTO disciplines (name, owning_course_id) VALUES (:name, :course_id)');

$stmtInsertOffering = $pdo->prepare('
    INSERT INTO course_offerings (course_id, discipline_id, teacher_id, turno, dia_semana, room, observation, origin_type)
    VALUES (:course_id, :discipline_id, :teacher_id, :turno, :dia_semana, :room, :observation, :origin_type)
');

$handle = fopen($csvPath, 'r');
if ($handle === false) {
    fwrite(STDERR, "Não foi possível abrir o CSV {$csvPath}" . PHP_EOL);
    exit(1);
}

// Lê cabeçalho e descarta
fgetcsv($handle, 0, ';');

$linha = 1;
$inseridos = 0;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $linha++;
    if (count($row) < 14) {
        continue;
    }

    [
        $unidade,
        $ano,
        $semestre,
        $cursoRaw,
        $periodo,
        $turma,
        $dataInicio,
        $dataTermino,
        $disciplinaRaw,
        $turnoRaw,
        $diaSemanaRaw,
        $horario,
        $salaRaw,
        $profRaw,
    ] = $row;

    // Ignora bacharelados
    if (str_contains(mb_strtoupper($cursoRaw, 'UTF-8'), 'BACHARELADO')) {
        continue;
    }

    $turno = normalize_turno($turnoRaw);
    $dia   = normalize_dia($diaSemanaRaw);
    if ($turno === null || $dia === null) {
        fwrite(STDERR, "Linha {$linha}: turno/dia inválidos, pulando." . PHP_EOL);
        continue;
    }

    $cursoUpper = mb_strtoupper(trim($cursoRaw), 'UTF-8');
    $stmtFindCourse->execute(['name' => $cursoUpper]);
    $course = $stmtFindCourse->fetch();
    if (!$course) {
        fwrite(STDERR, "Linha {$linha}: curso não encontrado em courses: {$cursoRaw}" . PHP_EOL);
        continue;
    }
    $courseId = (int) $course['id'];

    // Professor (teacher) – deduplicado por nome
    $teacherName = to_title(trim($profRaw));
    $stmtFindTeacher->execute(['name' => $teacherName]);
    $teacher = $stmtFindTeacher->fetch();

    if ($teacher) {
        $teacherId = (int) $teacher['id'];
    } else {
        $stmtInsertTeacher->execute(['name' => $teacherName]);
        $teacherId = (int) $pdo->lastInsertId();
    }

    // Disciplina – deduplicada por (nome, owning_course_id)
    $disciplinaName = to_title(trim($disciplinaRaw));
    $stmtFindDiscipline->execute([
        'name'      => $disciplinaName,
        'course_id' => $courseId,
    ]);
    $disc = $stmtFindDiscipline->fetch();

    if ($disc) {
        $disciplineId = (int) $disc['id'];
    } else {
        $stmtInsertDiscipline->execute([
            'name'      => $disciplinaName,
            'course_id' => $courseId,
        ]);
        $disciplineId = (int) $pdo->lastInsertId();
    }

    $sala = trim($salaRaw);
    $observation = null;

    $stmtInsertOffering->execute([
        'course_id'     => $courseId,
        'discipline_id' => $disciplineId,
        'teacher_id'    => $teacherId,
        'turno'         => $turno,
        'dia_semana'    => $dia,
        'room'          => $sala !== '' ? $sala : null,
        'observation'   => $observation,
        'origin_type'   => 'PROPRIA',
    ]);

    $inseridos++;
}

fclose($handle);

echo "Seed concluído. Ofertas inseridas: {$inseridos}" . PHP_EOL;

