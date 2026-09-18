<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('notificacoes_ativas')->default(true)->after('painel_tv_intervalo_segundos');
            $table->boolean('som_alerta_ativo')->default(false)->after('notificacoes_ativas');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['notificacoes_ativas', 'som_alerta_ativo']);
        });
    }
};
