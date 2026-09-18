<?php

use App\Modules\Core\Database\TenantTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        TenantTable::create('atendimentos', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_agendamento')->nullable();
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_assistente');
            $table->unsignedBigInteger('id_barbeiro')->nullable();
            $table->unsignedBigInteger('id_servico');
            $table->unsignedTinyInteger('posicao_fila');
            $table->date('data');
            $table->time('hora_chegada');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();
            $table->string('status_atendimento', 30)->default('aguardando');
            $table->text('observacoes')->nullable();
            $table->string('status', 1)->default('a');
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign(['id_empresa', 'id_agendamento'])
                ->references(['id_empresa', 'id'])
                ->on('agendamentos')
                ->restrictOnDelete();
            $table->foreign(['id_empresa', 'id_cliente'])
                ->references(['id_empresa', 'id'])
                ->on('clientes')
                ->restrictOnDelete();
            $table->foreign(['id_empresa', 'id_assistente'])
                ->references(['id_empresa', 'id'])
                ->on('assistentes')
                ->restrictOnDelete();
            $table->foreign(['id_empresa', 'id_barbeiro'])
                ->references(['id_empresa', 'id'])
                ->on('barbeiros')
                ->restrictOnDelete();
            $table->foreign(['id_empresa', 'id_servico'])
                ->references(['id_empresa', 'id'])
                ->on('servicos')
                ->restrictOnDelete();

            // Capacidade 1|2 por assistente/dia — unicidade aberta é regra de UseCase/Strategy
            $table->index(
                ['id_empresa', 'id_assistente', 'posicao_fila', 'data', 'status_atendimento'],
                'atendimentos_fila_dia_idx'
            );
            $table->index(['id_empresa', 'data', 'status_atendimento']);
        });
    }

    public function down(): void
    {
        TenantTable::dropIfExists('atendimentos');
    }
};
