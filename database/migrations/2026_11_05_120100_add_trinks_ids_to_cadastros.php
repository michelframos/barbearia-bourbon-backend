<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('trinks_cliente_id')->nullable()->after('status');
            $table->unique(['id_empresa', 'trinks_cliente_id'], 'clientes_empresa_trinks_unique');
        });

        Schema::table('barbeiros', function (Blueprint $table) {
            $table->unsignedBigInteger('trinks_profissional_id')->nullable()->after('status');
            $table->unique(['id_empresa', 'trinks_profissional_id'], 'barbeiros_empresa_trinks_unique');
        });

        Schema::table('servicos', function (Blueprint $table) {
            $table->unsignedBigInteger('trinks_servico_id')->nullable()->after('status');
            $table->unique(['id_empresa', 'trinks_servico_id'], 'servicos_empresa_trinks_unique');
        });

        Schema::table('assistentes', function (Blueprint $table) {
            $table->unsignedBigInteger('trinks_profissional_id')->nullable()->after('status');
            $table->unique(['id_empresa', 'trinks_profissional_id'], 'assistentes_empresa_trinks_unique');
        });

        Schema::table('agendamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('trinks_agendamento_id')->nullable()->after('status');
            $table->text('trinks_sync_erro')->nullable()->after('trinks_agendamento_id');
            $table->timestamp('trinks_sincronizado_em')->nullable()->after('trinks_sync_erro');
            $table->unique(['id_empresa', 'trinks_agendamento_id'], 'agendamentos_empresa_trinks_unique');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropUnique('agendamentos_empresa_trinks_unique');
            $table->dropColumn(['trinks_agendamento_id', 'trinks_sync_erro', 'trinks_sincronizado_em']);
        });

        Schema::table('assistentes', function (Blueprint $table) {
            $table->dropUnique('assistentes_empresa_trinks_unique');
            $table->dropColumn('trinks_profissional_id');
        });

        Schema::table('servicos', function (Blueprint $table) {
            $table->dropUnique('servicos_empresa_trinks_unique');
            $table->dropColumn('trinks_servico_id');
        });

        Schema::table('barbeiros', function (Blueprint $table) {
            $table->dropUnique('barbeiros_empresa_trinks_unique');
            $table->dropColumn('trinks_profissional_id');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_empresa_trinks_unique');
            $table->dropColumn('trinks_cliente_id');
        });
    }
};
