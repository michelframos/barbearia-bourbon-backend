<?php

namespace App\Modules\Trinks\Support;

/**
 * Evita reenviar à Trinks uma alteração que acabou de chegar pelo webhook ou pela importação.
 */
final class TrinksSyncContext
{
    private static int $depth = 0;

    public static function isInbound(): bool
    {
        return self::$depth > 0;
    }

    public static function withoutOutbound(callable $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }
}
