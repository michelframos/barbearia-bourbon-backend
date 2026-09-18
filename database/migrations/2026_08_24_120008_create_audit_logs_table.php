<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('id_empresa');
            $table->primary(['id_empresa', 'id']);

            $table->foreignId('id_user')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 50);
            $table->string('entidade', 50);
            $table->unsignedBigInteger('id_entidade')->nullable();
            $table->string('descricao', 500);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
            $table->index(['id_empresa', 'entidade', 'id_entidade']);
            $table->index(['id_empresa', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
