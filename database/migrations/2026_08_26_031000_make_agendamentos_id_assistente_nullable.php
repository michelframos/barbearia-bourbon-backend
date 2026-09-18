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
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND CONSTRAINT_NAME = 'agendamentos_id_empresa_id_assistente_foreign'"
        ))->isNotEmpty();

        if ($fkExists) {
            Schema::table('agendamentos', function (Blueprint $table): void {
                $table->dropForeign('agendamentos_id_empresa_id_assistente_foreign');
            });
        }

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_assistente')->nullable()->change();
        });

        // MySQL: ON DELETE SET NULL não é válido em FK composta com id_empresa NOT NULL.
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->foreign(['id_empresa', 'id_assistente'], 'agendamentos_id_empresa_id_assistente_foreign')
                ->references(['id_empresa', 'id'])
                ->on('assistentes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropForeign('agendamentos_id_empresa_id_assistente_foreign');
        });

        DB::table('agendamentos')->whereNull('id_assistente')->delete();

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_assistente')->nullable(false)->change();
        });

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->foreign(['id_empresa', 'id_assistente'], 'agendamentos_id_empresa_id_assistente_foreign')
                ->references(['id_empresa', 'id'])
                ->on('assistentes')
                ->restrictOnDelete();
        });
    }
};
