<?php

namespace App\Services\MatrizesCsv;

use App\Models\Course;
use App\Models\Discipline;

class MatrizesCsvMapper
{
    /**
     * @return array{courseByCode: array<string,int>, disciplineByNameNorm: array<string,int>, disciplineNameDuplicates: array<string,array<int>>}
     */
    public static function buildMaps(): array
    {
        $courses = Course::query()->select(['id', 'code'])->get();
        $courseByCode = [];
        foreach ($courses as $c) {
            $key = NameNormalizer::normalizeCourseCode((string) $c->code);
            if ($key !== '') {
                $courseByCode[$key] = (int) $c->id;
            }
        }

        $disciplines = Discipline::query()->select(['id', 'name'])->get();
        $disciplineByNameNorm = [];
        $disciplineNameDuplicates = [];
        foreach ($disciplines as $d) {
            $norm = NameNormalizer::normalize((string) $d->name);
            if ($norm === '') {
                continue;
            }

            if (!isset($disciplineByNameNorm[$norm])) {
                $disciplineByNameNorm[$norm] = (int) $d->id;
                continue;
            }

            $disciplineNameDuplicates[$norm] ??= [];
            $disciplineNameDuplicates[$norm][] = (int) $d->id;
        }

        return [
            'courseByCode' => $courseByCode,
            'disciplineByNameNorm' => $disciplineByNameNorm,
            'disciplineNameDuplicates' => $disciplineNameDuplicates,
        ];
    }
}

