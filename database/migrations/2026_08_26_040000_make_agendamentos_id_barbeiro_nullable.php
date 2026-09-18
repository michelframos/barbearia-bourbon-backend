<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $fkExists = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND CONSTRAINT_NAME = 'agendamentos_id_empresa_id_barbeiro_foreign'"
        ))->isNotEmpty();

        if ($fkExists) {
            Schema::table('agendamentos', function (Blueprint $table): void {
                $table->dropForeign('agendamentos_id_empresa_id_barbeiro_foreign');
            });
        }

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_barbeiro')->nullable()->change();
        });

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->foreign(['id_empresa', 'id_barbeiro'], 'agendamentos_id_empresa_id_barbeiro_foreign')
                ->references(['id_empresa', 'id'])
                ->on('barbeiros')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropForeign('agendamentos_id_empresa_id_barbeiro_foreign');
        });

        DB::table('agendamentos')->whereNull('id_barbeiro')->delete();

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_barbeiro')->nullable(false)->change();
        });

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->foreign(['id_empresa', 'id_barbeiro'], 'agendamentos_id_empresa_id_barbeiro_foreign')
                ->references(['id_empresa', 'id'])
                ->on('barbeiros')
                ->restrictOnDelete();
        });
    }
};
