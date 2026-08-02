<?php

namespace App\Services\OfertaCsv;

use RuntimeException;

class OfertaCsvParser
{
    /**
     * @return array<int, array{
     *   line_no:int,
     *   turma:string,
     *   curso_prefixo:string,
     *   disciplina:string,
     *   turno:string,
     *   dia_semana:string,
     *   sala:string,
     *   professor:string
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

            // Header: turma;curso_prefixo;disciplina;turno;dia_semana;sala;professor;vagas;alunos
            if ($lineNo === 1) {
                continue;
            }

            if (!is_array($data) || count($data) < 7) {
                continue;
            }

            [$turma, $cursoPrefixo, $disciplina, $turno, $diaSemana, $sala, $professor] = array_map(
                static fn ($v) => trim((string) $v),
                array_slice($data, 0, 7)
            );

            if ($cursoPrefixo === '' || $disciplina === '' || $turno === '' || $diaSemana === '' || $professor === '') {
                continue;
            }

            $rows[] = [
                'line_no' => $lineNo,
                'turma' => $turma,
                'curso_prefixo' => $cursoPrefixo,
                'disciplina' => $disciplina,
                'turno' => $turno,
                'dia_semana' => $diaSemana,
                'sala' => $sala,
                'professor' => $professor,
            ];
        }

        fclose($fh);

        return $rows;
    }
}
