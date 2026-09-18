<?php

use App\Modules\Core\Database\TenantTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        TenantTable::create('agendamentos', function (Blueprint $table): void {
            $table->date('data');
            $table->time('hora');
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_assistente');
            $table->unsignedBigInteger('id_barbeiro');
            $table->unsignedBigInteger('id_servico');
            $table->text('observacoes')->nullable();
            $table->string('status_agenda', 30)->default('agendado');
            $table->string('status', 1)->default('a');
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
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

            $table->unique(['id_empresa', 'data', 'hora', 'id_barbeiro'], 'agendamentos_barbeiro_slot_unique');
            $table->index(['id_empresa', 'data']);
        });
    }

    public function down(): void
    {
        TenantTable::dropIfExists('agendamentos');
    }
};
