<?php

use App\Modules\Core\Database\TenantTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        TenantTable::create('barbeiros', function (Blueprint $table): void {
            $table->string('nome', 255);
            $table->string('telefone', 20)->nullable();
            $table->string('status_operacional', 30)->default('offline');
            $table->time('hora_entrada')->nullable();
            $table->string('status', 1)->default('a');
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        TenantTable::dropIfExists('barbeiros');
    }
};
