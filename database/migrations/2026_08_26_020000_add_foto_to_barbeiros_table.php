<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbeiros', function (Blueprint $table): void {
            $table->string('foto', 500)->nullable()->after('telefone');
        });
    }

    public function down(): void
    {
        Schema::table('barbeiros', function (Blueprint $table): void {
            $table->dropColumn('foto');
        });
    }
};
