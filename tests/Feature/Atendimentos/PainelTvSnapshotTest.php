<?php

namespace Tests\Feature\Atendimentos;

use App\Models\User;
use App\Modules\Atendimentos\Contracts\PainelTvSnapshotProxyInterface;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Support\EmpresaContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PainelTvSnapshotTest extends TestCase
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

    public function test_painel_tv_retorna_snapshot_com_meta(): void
    {
        $data = now()->toDateString();

        $response = $this->getJson("/api/atendimentos/painel-tv?data={$data}");

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'resumo' => ['total', 'aguardando', 'chamados', 'em_atendimento', 'concluidos', 'cancelados'],
                'assistentes',
                'barbeiros',
                'atendimentos',
                'finalizados',
                'meta' => ['cached', 'refresh_interval_segundos', 'gerado_em'],
            ]);
    }

    public function test_cache_e_invalidado_apos_mudanca_em_atendimento(): void
    {
        $idEmpresa = EmpresaContext::id();
        $data = now()->toDateString();
        $cacheKey = "painel_tv_snapshot:{$idEmpresa}:{$data}";

        Cache::forget($cacheKey);

        $this->getJson("/api/atendimentos/painel-tv?data={$data}")->assertOk();
        $this->assertTrue(Cache::has($cacheKey));

        $atendimento = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->whereDate('data', $data)
            ->firstOrFail();

        $atendimento->update(['observacoes' => 'Atualizado para invalidar cache']);

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_slots_da_assistente_recebem_atendimentos_por_posicao(): void
    {
        $idEmpresa = EmpresaContext::id();
        $data = now()->toDateString();

        $response = $this->getJson("/api/atendimentos/painel-tv?data={$data}");
        $response->assertOk();

        $assistentes = collect($response->json('assistentes'));
        $anaPaula = $assistentes->firstWhere('nome', 'Ana Paula');

        $this->assertNotNull($anaPaula);
        $this->assertSame(2, $anaPaula['ocupados']);

        $slots = collect($anaPaula['slots']);
        $preenchidos = $slots->filter(fn (array $slot) => $slot['atendimento'] !== null);

        $this->assertCount(2, $preenchidos, 'Slots de Ana Paula devem conter os atendimentos abertos');

        $barbeiros = $preenchidos
            ->pluck('atendimento.barbeiro.nome')
            ->filter()
            ->values()
            ->all();

        $this->assertCount(2, $barbeiros, 'Cada slot em atendimento deve expor o barbeiro auxiliado');
    }

    public function test_proxy_invalidar_remove_cache(): void
    {
        $idEmpresa = EmpresaContext::id();
        $data = now()->toDateString();
        $cacheKey = "painel_tv_snapshot:{$idEmpresa}:{$data}";

        Cache::put($cacheKey, ['foo' => 'bar'], 30);

        /** @var PainelTvSnapshotProxyInterface $proxy */
        $proxy = app(PainelTvSnapshotProxyInterface::class);
        $proxy->invalidar($idEmpresa, $data);

        $this->assertFalse(Cache::has($cacheKey));
    }
}
