<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistentes', function (Blueprint $table): void {
            $table->unsignedSmallInteger('ordem_sequencial')->nullable()->after('turno');
        });

        DB::table('assistentes')->whereNull('ordem_sequencial')->update([
            'ordem_sequencial' => DB::raw('id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('assistentes', function (Blueprint $table): void {
            $table->dropColumn('ordem_sequencial');
        });
    }
};
