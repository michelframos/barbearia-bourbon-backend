<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trinks_webhook_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->unsignedSmallInteger('tipo_evento')->nullable();
            $table->unsignedBigInteger('id_agendamento_trinks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trinks_webhook_eventos');
    }
};
