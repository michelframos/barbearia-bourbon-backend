<?php

namespace App\Modules\Trinks\Support;

use App\Modules\Agendamentos\Models\AgendamentoModel;

final class TrinksStatusMap
{
    /** @var array<int, int> */
    public const ABERTOS = [3, 4, 7];

    public static function deId(?int $id): ?string
    {
        return match ($id) {
            3 => AgendamentoModel::STATUS_AGENDADO,
            4 => AgendamentoModel::STATUS_CONFIRMADO,
            6 => AgendamentoModel::STATUS_NAO_COMPARECEU,
            7 => AgendamentoModel::STATUS_EM_ATENDIMENTO,
            8 => AgendamentoModel::STATUS_CONCLUIDO,
            9 => AgendamentoModel::STATUS_CANCELADO,
            default => null,
        };
    }

    public static function deNome(?string $nome): string
    {
        $normalizado = TrinksTexto::nome($nome);
        $normalizado = strtr($normalizado, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        return match (true) {
            str_contains($normalizado, 'confirm') => AgendamentoModel::STATUS_CONFIRMADO,
            str_contains($normalizado, 'atendimento') => AgendamentoModel::STATUS_EM_ATENDIMENTO,
            str_contains($normalizado, 'final') => AgendamentoModel::STATUS_CONCLUIDO,
            str_contains($normalizado, 'cancel') => AgendamentoModel::STATUS_CANCELADO,
            str_contains($normalizado, 'falt') => AgendamentoModel::STATUS_NAO_COMPARECEU,
            default => AgendamentoModel::STATUS_AGENDADO,
        };
    }
}
