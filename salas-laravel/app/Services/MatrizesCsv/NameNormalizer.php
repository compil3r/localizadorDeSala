<?php

namespace App\Services\MatrizesCsv;

class NameNormalizer
{
    public static function normalize(string $value): string
    {
        $v = trim($value);
        $v = mb_strtolower($v, 'UTF-8');

        // Remove acentos/diacríticos para reduzir divergências de "match por nome".
        if (class_exists(\Normalizer::class)) {
            $v = \Normalizer::normalize($v, \Normalizer::FORM_D);
            $v = preg_replace('/\p{Mn}/u', '', $v) ?? $v;
        }

        // Colapsa múltiplos espaços.
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return $v;
    }

    public static function normalizeCourseCode(string $value): string
    {
        // Código costuma ser curto (ex.: ADS). Mantém compatibilidade por caixa.
        return trim(mb_strtoupper($value, 'UTF-8'));
    }

    public static function parseOptionalFlag(string $value): ?bool
    {
        $norm = self::normalize($value);
        if ($norm === 'sim') {
            return true;
        }
        if ($norm === 'nao' || $norm === 'nao ') {
            return false;
        }
        return null;
    }

    public static function parseCourseSemester(string $value): ?int
    {
        // Ex.: "1º Semestre", "6º Semestre".
        if (preg_match('/(\d+)\s*º?\s*semestre/i', $value, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}

