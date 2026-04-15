<?php

namespace App\Services\MatrizesCsv;

use RuntimeException;

class MatrizesCsvParser
{
    /**
     * @return array<int, array{
     *   line_no:int,
     *   course_code:string,
     *   course_semester:int,
     *   discipline_name:string,
     *   is_optional:bool
     * }>
     */
    public static function parse(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("Arquivo CSV não encontrado: {$filePath}");
        }

        $fh = fopen($filePath, 'rb');
        if (!$fh) {
            throw new RuntimeException("Não foi possível abrir CSV: {$filePath}");
        }

        $rows = [];
        $lineNo = 0;
        while (($data = fgetcsv($fh, 0, ';')) !== false) {
            $lineNo++;

            // Header: CURSO;MÓDULO;DISCIPLINA;OPTATIVA
            if ($lineNo === 1) {
                continue;
            }

            if (!is_array($data) || count($data) < 4) {
                continue;
            }

            [$courseCode, $module, $disciplineName, $optativa] = array_map(
                static fn ($v) => trim((string) $v),
                array_slice($data, 0, 4)
            );

            if ($courseCode === '' || $module === '' || $disciplineName === '') {
                continue;
            }

            $semester = NameNormalizer::parseCourseSemester($module);
            $isOptional = NameNormalizer::parseOptionalFlag($optativa);

            if ($semester === null || $isOptional === null) {
                // Ainda registra para que o verificador/ importador reporte.
                $rows[] = [
                    'line_no' => $lineNo,
                    'course_code' => $courseCode,
                    'course_semester' => -1,
                    'discipline_name' => $disciplineName,
                    'is_optional' => (bool) ($isOptional ?? false),
                    'parse_error' => true,
                    'parse_module' => $module,
                    'parse_optativa' => $optativa,
                ];
                continue;
            }

            $rows[] = [
                'line_no' => $lineNo,
                'course_code' => $courseCode,
                'course_semester' => $semester,
                'discipline_name' => $disciplineName,
                'is_optional' => $isOptional,
            ];
        }

        fclose($fh);

        return $rows;
    }
}

