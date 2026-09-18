<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Troca unique rígido por índice comum: cancelados/soft-deleted
 * liberam o slot via Strategy (anti double-booking na aplicação).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropUnique('agendamentos_barbeiro_slot_unique');
            $table->index(
                ['id_empresa', 'data', 'hora', 'id_barbeiro'],
                'agendamentos_barbeiro_slot_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropIndex('agendamentos_barbeiro_slot_index');
            $table->unique(
                ['id_empresa', 'data', 'hora', 'id_barbeiro'],
                'agendamentos_barbeiro_slot_unique'
            );
        });
    }
};
