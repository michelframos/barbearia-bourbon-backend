<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('trinks_ativo')->default(false)->after('status');
            $table->string('trinks_estabelecimento_id', 50)->nullable()->after('trinks_ativo');
            $table->text('trinks_api_key')->nullable()->after('trinks_estabelecimento_id');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'trinks_ativo',
                'trinks_estabelecimento_id',
                'trinks_api_key',
            ]);
        });
    }
};
