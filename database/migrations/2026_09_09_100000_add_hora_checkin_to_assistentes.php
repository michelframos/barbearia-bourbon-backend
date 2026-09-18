<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistentes', function (Blueprint $table): void {
            $table->dateTime('hora_checkin')->nullable()->after('ordem_sequencial');
        });
    }

    public function down(): void
    {
        Schema::table('assistentes', function (Blueprint $table): void {
            $table->dropColumn('hora_checkin');
        });
    }
};
