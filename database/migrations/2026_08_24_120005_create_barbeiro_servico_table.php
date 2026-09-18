<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbeiro_servico', function (Blueprint $table) {
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_barbeiro');
            $table->unsignedBigInteger('id_servico');

            $table->primary(['id_empresa', 'id_barbeiro', 'id_servico']);

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign(['id_empresa', 'id_barbeiro'])
                ->references(['id_empresa', 'id'])
                ->on('barbeiros')
                ->cascadeOnDelete();
            $table->foreign(['id_empresa', 'id_servico'])
                ->references(['id_empresa', 'id'])
                ->on('servicos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbeiro_servico');
    }
};
