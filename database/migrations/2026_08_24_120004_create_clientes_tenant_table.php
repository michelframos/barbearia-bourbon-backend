<?php

use App\Modules\Core\Database\TenantTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        TenantTable::create('clientes', function (Blueprint $table): void {
            $table->string('nome', 255);
            $table->string('telefone', 20)->nullable()->index();
            $table->unsignedBigInteger('id_assistente_preferida')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('status', 1)->default('a');
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign(['id_empresa', 'id_assistente_preferida'])
                ->references(['id_empresa', 'id'])
                ->on('assistentes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        TenantTable::dropIfExists('clientes');
    }
};
