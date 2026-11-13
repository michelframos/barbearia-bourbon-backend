<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uniques passam a considerar apenas registros ativos (deleted_at IS NULL),
 * via colunas geradas — compatível com MariaDB 10.4+ / MySQL 5.7+.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('users', 'users_email_unique');
        $this->dropIndexIfExists('users', 'users_email_active_unique');

        if (! Schema::hasColumn('users', 'email_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('email_active')
                    ->nullable()
                    ->storedAs('IF(deleted_at IS NULL, email, NULL)')
                    ->after('email');
            });
        }

        $this->dropIndexIfExists('users', 'users_email_active_unique');
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email_active', 'users_email_active_unique');
        });

        $this->replaceTrinksUnique(
            table: 'clientes',
            oldIndex: 'clientes_empresa_trinks_unique',
            newIndex: 'clientes_empresa_trinks_active_unique',
            sourceColumn: 'trinks_cliente_id',
            activeColumn: 'trinks_cliente_id_active',
        );

        $this->replaceTrinksUnique(
            table: 'barbeiros',
            oldIndex: 'barbeiros_empresa_trinks_unique',
            newIndex: 'barbeiros_empresa_trinks_active_unique',
            sourceColumn: 'trinks_profissional_id',
            activeColumn: 'trinks_profissional_id_active',
        );

        $this->replaceTrinksUnique(
            table: 'servicos',
            oldIndex: 'servicos_empresa_trinks_unique',
            newIndex: 'servicos_empresa_trinks_active_unique',
            sourceColumn: 'trinks_servico_id',
            activeColumn: 'trinks_servico_id_active',
        );

        $this->replaceTrinksUnique(
            table: 'assistentes',
            oldIndex: 'assistentes_empresa_trinks_unique',
            newIndex: 'assistentes_empresa_trinks_active_unique',
            sourceColumn: 'trinks_profissional_id',
            activeColumn: 'trinks_profissional_id_active',
        );

        $this->replaceTrinksUnique(
            table: 'agendamentos',
            oldIndex: 'agendamentos_empresa_trinks_unique',
            newIndex: 'agendamentos_empresa_trinks_active_unique',
            sourceColumn: 'trinks_agendamento_id',
            activeColumn: 'trinks_agendamento_id_active',
        );
    }

    public function down(): void
    {
        $this->dropIndexIfExists('users', 'users_email_active_unique');
        if (Schema::hasColumn('users', 'email_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('email_active');
            });
        }
        $this->dropIndexIfExists('users', 'users_email_unique');
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email', 'users_email_unique');
        });

        $this->restoreTrinksUnique('clientes', 'clientes_empresa_trinks_active_unique', 'clientes_empresa_trinks_unique', 'trinks_cliente_id', 'trinks_cliente_id_active');
        $this->restoreTrinksUnique('barbeiros', 'barbeiros_empresa_trinks_active_unique', 'barbeiros_empresa_trinks_unique', 'trinks_profissional_id', 'trinks_profissional_id_active');
        $this->restoreTrinksUnique('servicos', 'servicos_empresa_trinks_active_unique', 'servicos_empresa_trinks_unique', 'trinks_servico_id', 'trinks_servico_id_active');
        $this->restoreTrinksUnique('assistentes', 'assistentes_empresa_trinks_active_unique', 'assistentes_empresa_trinks_unique', 'trinks_profissional_id', 'trinks_profissional_id_active');
        $this->restoreTrinksUnique('agendamentos', 'agendamentos_empresa_trinks_active_unique', 'agendamentos_empresa_trinks_unique', 'trinks_agendamento_id', 'trinks_agendamento_id_active');
    }

    private function replaceTrinksUnique(
        string $table,
        string $oldIndex,
        string $newIndex,
        string $sourceColumn,
        string $activeColumn,
    ): void {
        $this->dropIndexIfExists($table, $oldIndex);
        $this->dropIndexIfExists($table, $newIndex);

        if (! Schema::hasColumn($table, $activeColumn)) {
            Schema::table($table, function (Blueprint $blueprint) use ($sourceColumn, $activeColumn): void {
                $blueprint->unsignedBigInteger($activeColumn)
                    ->nullable()
                    ->storedAs("IF(deleted_at IS NULL, {$sourceColumn}, NULL)");
            });
        }

        $this->dropIndexIfExists($table, $newIndex);
        Schema::table($table, function (Blueprint $blueprint) use ($newIndex, $activeColumn): void {
            $blueprint->unique(['id_empresa', $activeColumn], $newIndex);
        });
    }

    private function restoreTrinksUnique(
        string $table,
        string $activeIndex,
        string $legacyIndex,
        string $sourceColumn,
        string $activeColumn,
    ): void {
        $this->dropIndexIfExists($table, $activeIndex);

        if (Schema::hasColumn($table, $activeColumn)) {
            Schema::table($table, function (Blueprint $blueprint) use ($activeColumn): void {
                $blueprint->dropColumn($activeColumn);
            });
        }

        $this->dropIndexIfExists($table, $legacyIndex);
        Schema::table($table, function (Blueprint $blueprint) use ($legacyIndex, $sourceColumn): void {
            $blueprint->unique(['id_empresa', $sourceColumn], $legacyIndex);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]);

        if ($rows === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }
};
