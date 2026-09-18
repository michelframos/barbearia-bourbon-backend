<?php

namespace App\Modules\Core\Database;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema helper para tabelas tenant: PK (id_empresa, id) + soft deletes.
 *
 * Particionamento MySQL opcional via empresa.mysql_partitioning_enabled.
 */
final class TenantTable
{
    /**
     * @param  Closure(Blueprint): void  $callback
     */
    public static function create(string $table, Closure $callback): void
    {
        Schema::create($table, function (Blueprint $blueprint) use ($callback): void {
            $blueprint->unsignedBigInteger('id');
            $blueprint->unsignedBigInteger('id_empresa');
            $blueprint->primary(['id_empresa', 'id']);
            $blueprint->softDeletes();

            $callback($blueprint);
        });

        self::applyPartitioningIfEnabled($table);
    }

    public static function dropIfExists(string $table): void
    {
        Schema::dropIfExists($table);
    }

    private static function applyPartitioningIfEnabled(string $table): void
    {
        if (! config('empresa.mysql_partitioning_enabled', false)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $partitions = max(1, (int) config('empresa.mysql_partition_count', 8));
        $quoted = '`'.str_replace('`', '``', $table).'`';

        DB::statement("ALTER TABLE {$quoted} PARTITION BY KEY(`id_empresa`) PARTITIONS {$partitions}");
    }
}
