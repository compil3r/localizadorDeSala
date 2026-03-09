-- Esquema inicial para o backend Salas UniSenac
-- Foco: cursos, unidades curriculares (disciplinas), ofertas por curso
-- e autenticação básica de usuários (admin + coordenadores).

-- IMPORTANTE:
-- Este script foi pensado para ser executado inteiro em ambiente de desenvolvimento.
-- Ele apaga as tabelas existentes e recria tudo do zero.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS course_offerings;
DROP TABLE IF EXISTS disciplines;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS coordinators;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE coordinators (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN','COORDINATOR') NOT NULL DEFAULT 'COORDINATOR',
    coordinator_id INT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_coordinator
        FOREIGN KEY (coordinator_id) REFERENCES coordinators(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_users_email (email)
);

CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(32) NOT NULL,
    coordinator_id INT UNSIGNED NULL,
    CONSTRAINT fk_courses_coordinator
        FOREIGN KEY (coordinator_id) REFERENCES coordinators(id)
        ON DELETE SET NULL
);

CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE disciplines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owning_course_id INT UNSIGNED NULL,
    CONSTRAINT fk_disciplines_owning_course
        FOREIGN KEY (owning_course_id) REFERENCES courses(id)
        ON DELETE SET NULL
);

-- Oferta de uma disciplina em um curso específico (inclusive optativas/compartilhadas)
CREATE TABLE course_offerings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    discipline_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    turno ENUM('MANHA','NOITE') NOT NULL,
    dia_semana ENUM('SEG','TER','QUA','QUI','SEX','SAB') NOT NULL,
    room VARCHAR(32) NULL,
    observation VARCHAR(255) NULL,
    origin_type ENUM('PROPRIA','OPTATIVA','COMPARTILHADA') NOT NULL DEFAULT 'PROPRIA',
    CONSTRAINT fk_offering_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_offering_discipline
        FOREIGN KEY (discipline_id) REFERENCES disciplines(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_offering_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers(id)
        ON DELETE RESTRICT
);

CREATE INDEX idx_course_offerings_course_turno_dia
    ON course_offerings (course_id, turno, dia_semana);

