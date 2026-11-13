<?php

namespace Tests\Feature\Core;

use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Support\EmpresaContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteUniqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_permite_recriar_cliente_com_mesmo_trinks_id_apos_soft_delete(): void
    {
        $idEmpresa = EmpresaContext::id();

        $cliente = ClienteModel::query()->create([
            'id_empresa' => $idEmpresa,
            'nome' => 'Cliente Trinks',
            'telefone' => '11999990000',
            'status' => 'a',
            'trinks_cliente_id' => 999001,
        ]);

        $cliente->delete();
        $this->assertSoftDeleted('clientes', [
            'id' => $cliente->id,
            'id_empresa' => $idEmpresa,
        ]);

        $novo = ClienteModel::query()->create([
            'id_empresa' => $idEmpresa,
            'nome' => 'Cliente Trinks Novo',
            'telefone' => '11999990001',
            'status' => 'a',
            'trinks_cliente_id' => 999001,
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $novo->id,
            'id_empresa' => $idEmpresa,
            'trinks_cliente_id' => 999001,
            'deleted_at' => null,
        ]);
    }
}
