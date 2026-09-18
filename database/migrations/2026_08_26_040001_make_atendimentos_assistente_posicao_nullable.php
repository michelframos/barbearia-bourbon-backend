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
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'atendimentos' AND CONSTRAINT_NAME = 'atendimentos_id_empresa_id_assistente_foreign'"
        ))->isNotEmpty();

        if ($fkExists) {
            Schema::table('atendimentos', function (Blueprint $table): void {
                $table->dropForeign('atendimentos_id_empresa_id_assistente_foreign');
            });
        }

        Schema::table('atendimentos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_assistente')->nullable()->change();
            $table->unsignedTinyInteger('posicao_fila')->nullable()->change();
        });

        Schema::table('atendimentos', function (Blueprint $table): void {
            $table->foreign(['id_empresa', 'id_assistente'], 'atendimentos_id_empresa_id_assistente_foreign')
                ->references(['id_empresa', 'id'])
                ->on('assistentes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos', function (Blueprint $table): void {
            $table->dropForeign('atendimentos_id_empresa_id_assistente_foreign');
        });

        DB::table('atendimentos')->whereNull('id_assistente')->delete();
        DB::table('atendimentos')->whereNull('posicao_fila')->update(['posicao_fila' => 1]);

        Schema::table('atendimentos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_assistente')->nullable(false)->change();
            $table->unsignedTinyInteger('posicao_fila')->nullable(false)->change();
        });

        Schema::table('atendimentos', function (Blueprint $table): void {
            $table->foreign(['id_empresa', 'id_assistente'], 'atendimentos_id_empresa_id_assistente_foreign')
                ->references(['id_empresa', 'id'])
                ->on('assistentes')
                ->restrictOnDelete();
        });
    }
};
