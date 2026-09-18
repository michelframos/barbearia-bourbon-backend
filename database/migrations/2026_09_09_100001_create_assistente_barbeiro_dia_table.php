<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistente_barbeiro_dia', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_assistente');
            $table->unsignedBigInteger('id_barbeiro');
            $table->unsignedTinyInteger('dia_semana');

            $table->primary(
                ['id_empresa', 'id_assistente', 'id_barbeiro', 'dia_semana'],
                'assistente_barbeiro_dia_pk'
            );

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign(['id_empresa', 'id_assistente'])
                ->references(['id_empresa', 'id'])
                ->on('assistentes')
                ->cascadeOnDelete();
            $table->foreign(['id_empresa', 'id_barbeiro'])
                ->references(['id_empresa', 'id'])
                ->on('barbeiros')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistente_barbeiro_dia');
    }
};
