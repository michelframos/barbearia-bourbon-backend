<?php

namespace Tests\Feature\Atendimentos;

use App\Models\User;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Core\Support\EmpresaContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HistoricoAtendimentosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->user = User::query()->where('email', 'admin@barbearia.test')->firstOrFail();
        Sanctum::actingAs($this->user);
    }

    public function test_listagem_exige_data(): void
    {
        $response = $this->getJson('/api/atendimentos');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['data']);
    }

    public function test_listagem_filtra_por_status_concluido(): void
    {
        $data = now()->toDateString();

        $response = $this->getJson("/api/atendimentos?data={$data}&status_atendimento=concluido");

        $response->assertOk();

        $payload = $response->json('data');
        $this->assertIsArray($payload);

        foreach ($payload as $item) {
            $this->assertSame(AtendimentoModel::STATUS_CONCLUIDO, $item['status_atendimento']);
        }
    }

    public function test_listagem_rejeita_status_invalido(): void
    {
        $data = now()->toDateString();

        $response = $this->getJson("/api/atendimentos?data={$data}&status_atendimento=invalido");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status_atendimento']);
    }
}
