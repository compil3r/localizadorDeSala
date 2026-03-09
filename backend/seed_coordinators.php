<?php

// Script de seed para criar coordenadores, usuários e vincular cursos.
// Uso:
//   php backend/seed_coordinators.php
//
// A senha padrão de cada coordenador segue o padrão:
//   <parte_anterior_ao_@>@2026!
// Ex.: ndbranda@senacrs.com.br => senha "ndbranda@2026!"

require_once __DIR__ . '/db.php';

$pdo = get_pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = [
    [
        'name'   => 'Nicele Branda',
        'email'  => 'ndbranda@senacrs.com.br',
        'courses' => [
            ['code' => 'MODA', 'name' => 'Curso Superior de Tecnologia em Design de Moda'],
        ],
    ],
    [
        'name'   => 'Fabrizzia Lacerda',
        'email'  => 'fblacerda@senacrs.com.br',
        'courses' => [
            ['code' => 'PMM', 'name' => 'Curso Superior de Tecnologia em Produção Multimídia'],
        ],
    ],
    [
        'name'   => 'Rafael Rehm',
        'email'  => 'rjrehm@senacrs.com.br',
        'courses' => [
            ['code' => 'ADS', 'name' => 'Curso Superior de Tecnologia em Análise e Desenvolvimento de Sistemas'],
        ],
    ],
    [
        'name'   => 'Vitor Hugo Lopes',
        'email'  => 'vhlopes@senacrs.com.br',
        'courses' => [
            ['code' => 'IA', 'name' => 'Curso Superior de Tecnologia em Inteligência Artificial e Ciência de Dados'],
        ],
    ],
    [
        'name'   => 'Marcio Pohlmann',
        'email'  => 'mpohlmann@senacrs.com.br',
        'courses' => [
            ['code' => 'SEG', 'name' => 'Curso Superior de Tecnologia em Segurança Cibernética'],
            ['code' => 'REDES', 'name' => 'Curso Superior de Tecnologia em Redes de Computadores'],
        ],
    ],
    [
        'name'   => 'Claudia Mallmann',
        'email'  => 'ccmallmann@senacrs.com.br',
        'courses' => [
            ['code' => 'MKT', 'name' => 'Curso Superior de Tecnologia em Marketing'],
        ],
    ],
    [
        'name'   => 'Jacqueline Zapp',
        'email'  => 'jszapp@senacrs.com.br',
        'courses' => [
            ['code' => 'PG', 'name' => 'Curso Superior de Tecnologia em Processos Gerenciais'],
        ],
    ],
];

foreach ($data as $entry) {
    $pdo->beginTransaction();

    try {
        // 1) Garante coordenador
        $coordStmt = $pdo->prepare('SELECT id FROM coordinators WHERE name = :name LIMIT 1');
        $coordStmt->execute(['name' => $entry['name']]);
        $coord = $coordStmt->fetch();

        if ($coord) {
            $coordinatorId = (int) $coord['id'];
        } else {
            $insertCoord = $pdo->prepare('INSERT INTO coordinators (name) VALUES (:name)');
            $insertCoord->execute(['name' => $entry['name']]);
            $coordinatorId = (int) $pdo->lastInsertId();
        }

        // 2) Garante usuário (COORDINATOR) vinculado
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $userStmt->execute(['email' => $entry['email']]);
        $user = $userStmt->fetch();

        $emailLocalPart = strtolower(strtok($entry['email'], '@'));
        $plainPassword  = $emailLocalPart . '@2026!';
        $passwordHash   = password_hash($plainPassword, PASSWORD_DEFAULT);

        if ($user) {
            $updateUser = $pdo->prepare('
                UPDATE users
                SET name = :name,
                    password_hash = :hash,
                    role = "COORDINATOR",
                    coordinator_id = :coord_id,
                    active = 1
                WHERE id = :id
            ');
            $updateUser->execute([
                'name'     => $entry['name'],
                'hash'     => $passwordHash,
                'coord_id' => $coordinatorId,
                'id'       => (int) $user['id'],
            ]);
            $userId = (int) $user['id'];
        } else {
            $insertUser = $pdo->prepare('
                INSERT INTO users (name, email, password_hash, role, coordinator_id, active)
                VALUES (:name, :email, :hash, "COORDINATOR", :coord_id, 1)
            ');
            $insertUser->execute([
                'name'     => $entry['name'],
                'email'    => $entry['email'],
                'hash'     => $passwordHash,
                'coord_id' => $coordinatorId,
            ]);
            $userId = (int) $pdo->lastInsertId();
        }

        // 3) Garante cursos apontando para esse coordenador
        foreach ($entry['courses'] as $courseData) {
            $courseStmt = $pdo->prepare('SELECT id FROM courses WHERE code = :code LIMIT 1');
            $courseStmt->execute(['code' => $courseData['code']]);
            $course = $courseStmt->fetch();

            if ($course) {
                $updateCourse = $pdo->prepare('
                    UPDATE courses
                    SET name = :name,
                        coordinator_id = :coord_id
                    WHERE id = :id
                ');
                $updateCourse->execute([
                    'name'     => $courseData['name'],
                    'coord_id' => $coordinatorId,
                    'id'       => (int) $course['id'],
                ]);
            } else {
                $insertCourse = $pdo->prepare('
                    INSERT INTO courses (name, code, coordinator_id)
                    VALUES (:name, :code, :coord_id)
                ');
                $insertCourse->execute([
                    'name'     => $courseData['name'],
                    'code'     => $courseData['code'],
                    'coord_id' => $coordinatorId,
                ]);
            }
        }

        $pdo->commit();

        echo "Seed OK para coordenador {$entry['name']} ({$entry['email']})" . PHP_EOL;
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "Erro ao processar coordenador {$entry['name']}: {$e->getMessage()}" . PHP_EOL;
    }
}

