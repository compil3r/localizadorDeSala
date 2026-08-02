<?php

namespace App\Services;

class TeacherNameFormatter
{
    /** Conectivos que ficam em minúsculo quando no meio do nome. */
    private const CONNECTORS = ['da', 'de', 'do', 'das', 'dos', 'di', 'du', 'e'];

    /**
     * Exceções: chave = nome em MAIÚSCULAS com espaços colapsados; valor = saída final.
     *
     * @var array<string, string>
     */
    private const OVERRIDES = [
        // Nome composto que deve ser preservado além do primeiro+último.
        'VITOR HUGO DA SILVA LOPES' => 'Vitor Hugo Lopes',
        'VITOR HUGO LOPES' => 'Vitor Hugo Lopes',
        // Marcador, não é um nome real.
        'INSTRUTOR A DEFINIR' => 'Instrutor a Definir',
    ];

    /**
     * Regras: capitalização adequada (conectivos em minúsculo) e redução para
     * primeiro + último nome, respeitando as exceções configuradas.
     */
    public static function format(string $name): string
    {
        $collapsed = trim((string) preg_replace('/\s+/u', ' ', $name));
        if ($collapsed === '') {
            return '';
        }

        $override = self::OVERRIDES[mb_strtoupper($collapsed, 'UTF-8')] ?? null;
        if ($override !== null) {
            return $override;
        }

        $tokens = explode(' ', $collapsed);
        if (count($tokens) === 1) {
            return self::formatWord($tokens[0]);
        }

        return self::formatWord($tokens[0]).' '.self::formatWord($tokens[count($tokens) - 1]);
    }

    private static function formatWord(string $word): string
    {
        $lower = mb_strtolower($word, 'UTF-8');
        $first = mb_substr($lower, 0, 1, 'UTF-8');
        $rest = mb_substr($lower, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8').$rest;
    }
}
