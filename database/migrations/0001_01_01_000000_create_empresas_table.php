<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->string('telefone', 20)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->time('horario_abertura')->default('08:00:00');
            $table->time('horario_fechamento')->default('19:00:00');
            $table->unsignedSmallInteger('intervalo_agenda_minutos')->default(30);
            $table->boolean('painel_tv_auto_refresh')->default(true);
            $table->unsignedSmallInteger('painel_tv_intervalo_segundos')->default(30);
            $table->string('status', 1)->default('a');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
