<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->string('modo_selecao_assistente', 30)->default('manual')->after('painel_tv_intervalo_segundos');
            $table->unsignedSmallInteger('max_atendimentos_simultaneos_assistente')->default(2)->after('modo_selecao_assistente');
            $table->unsignedBigInteger('ultima_assistente_sequencial_id')->nullable()->after('max_atendimentos_simultaneos_assistente');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->dropColumn([
                'modo_selecao_assistente',
                'max_atendimentos_simultaneos_assistente',
                'ultima_assistente_sequencial_id',
            ]);
        });
    }
};
