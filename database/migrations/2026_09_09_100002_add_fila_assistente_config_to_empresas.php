<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->string('fonte_ordem_assistente', 20)->default('manual')->after('ultima_assistente_sequencial_id');
            $table->string('reset_cursor_assistente', 30)->default('meia_noite')->after('fonte_ordem_assistente');
            $table->string('momento_atribuicao_assistente', 30)->default('iniciar_atendimento')->after('reset_cursor_assistente');
            $table->date('cursor_sequencial_data')->nullable()->after('momento_atribuicao_assistente');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->dropColumn([
                'fonte_ordem_assistente',
                'reset_cursor_assistente',
                'momento_atribuicao_assistente',
                'cursor_sequencial_data',
            ]);
        });
    }
};
