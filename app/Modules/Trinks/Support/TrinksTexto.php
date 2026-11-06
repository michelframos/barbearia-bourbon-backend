<?php

namespace App\Modules\Trinks\Support;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Trinks\Exceptions\TrinksApiException;

final class TrinksTexto
{
    public static function nome(?string $nome): string
    {
        $nome = trim((string) (preg_replace('/\s+/u', ' ', (string) $nome) ?? ''));

        return mb_strtolower($nome);
    }

    public static function digitos(?string $telefone): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefone) ?? '';

        if (str_starts_with($digitos, '55') && strlen($digitos) > 11) {
            $digitos = substr($digitos, 2);
        }

        return $digitos;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function partirDataHora(string $valor): array
    {
        $valor = str_replace('T', ' ', trim($valor));
        $valor = substr($valor, 0, 19);

        if (! str_contains($valor, ' ')) {
            throw new TrinksApiException('Data do agendamento inválida.');
        }

        [$data, $hora] = explode(' ', $valor, 2);

        return [$data, substr($hora, 0, 5)];
    }

    public static function decimal(mixed $valor): float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return 0.0;
        }

        if (str_contains($texto, ',')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        }

        return (float) $texto;
    }

    public static function telefoneExibicao(?string $completo, ?string $ddd = null, ?string $numero = null): ?string
    {
        $texto = trim((string) ($completo ?: trim(trim((string) $ddd).' '.trim((string) $numero))));

        if ($texto === '') {
            return null;
        }

        return mb_substr($texto, 0, 20);
    }
}
