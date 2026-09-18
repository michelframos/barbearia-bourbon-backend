<?php

use App\Modules\Core\Database\TenantTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        TenantTable::create('servicos', function (Blueprint $table): void {
            $table->string('nome', 255);
            $table->unsignedSmallInteger('duracao_minutos')->default(30);
            $table->decimal('preco', 10, 2)->nullable();
            $table->string('status', 1)->default('a');
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        TenantTable::dropIfExists('servicos');
    }
};
