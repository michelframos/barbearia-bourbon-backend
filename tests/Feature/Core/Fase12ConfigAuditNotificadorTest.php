<?php

namespace Tests\Feature\Core;

use App\Models\User;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Support\AuditAcao;
use App\Modules\Core\Support\EmpresaContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Fase12ConfigAuditNotificadorTest extends TestCase
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

    public function test_atualizar_empresa_persiste_configuracoes_e_registra_audit(): void
    {
        $response = $this->putJson('/api/empresa', [
            'nome' => 'Bourbon Barbearia Centro',
            'telefone' => '(11) 97777-0000',
            'painel_tv_auto_refresh' => false,
            'painel_tv_intervalo_segundos' => 45,
            'modo_selecao_assistente' => Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL,
            'max_atendimentos_simultaneos_assistente' => 3,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nome', 'Bourbon Barbearia Centro')
            ->assertJsonPath('data.painel_tv_auto_refresh', false)
            ->assertJsonPath('data.painel_tv_intervalo_segundos', 45);

        $this->assertDatabaseHas('empresas', [
            'id' => EmpresaContext::id(),
            'nome' => 'Bourbon Barbearia Centro',
            'painel_tv_auto_refresh' => false,
        ]);

        $this->assertTrue(
            AuditLog::query()
                ->where('id_empresa', EmpresaContext::id())
                ->where('acao', AuditAcao::CONFIG)
                ->where('entidade', 'empresa')
                ->exists()
        );
    }

    public function test_check_in_registra_audit_log(): void
    {
        $atendimento = AtendimentoModel::query()
            ->where('id_empresa', EmpresaContext::id())
            ->whereDate('data', now()->toDateString())
            ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
            ->firstOrFail();

        $atendimento->update(['status_atendimento' => AtendimentoModel::STATUS_CONCLUIDO]);

        $cliente = $atendimento->cliente;
        $servico = $atendimento->servico;

        $response = $this->postJson('/api/atendimentos/check-in', [
            'id_cliente' => $cliente->id,
            'id_servico' => $servico->id,
            'id_assistente' => $atendimento->id_assistente,
            'id_barbeiro' => $atendimento->id_barbeiro,
        ]);

        $response->assertCreated();

        $this->assertTrue(
            AuditLog::query()
                ->where('acao', AuditAcao::CHECK_IN)
                ->where('entidade', 'atendimento')
                ->where('id_user', $this->user->id)
                ->exists()
        );
    }

    public function test_chamar_cliente_registra_audit_e_stub_notificador(): void
    {
        Log::spy();

        $atendimento = AtendimentoModel::query()
            ->where('id_empresa', EmpresaContext::id())
            ->whereDate('data', now()->toDateString())
            ->where('status_atendimento', AtendimentoModel::STATUS_AGUARDANDO)
            ->first();

        if (! $atendimento) {
            $atendimento = AtendimentoModel::query()
                ->where('id_empresa', EmpresaContext::id())
                ->whereDate('data', now()->toDateString())
                ->where('status_atendimento', AtendimentoModel::STATUS_CHAMADO)
                ->firstOrFail();

            $atendimento->update(['status_atendimento' => AtendimentoModel::STATUS_AGUARDANDO]);
        }

        $response = $this->postJson("/api/atendimentos/{$atendimento->id}/chamar");
        $response->assertOk();

        $this->assertTrue(
            AuditLog::query()
                ->where('acao', AuditAcao::CHAMAR)
                ->where('entidade', 'atendimento')
                ->where('id_entidade', $atendimento->id)
                ->exists()
        );

        if ($atendimento->cliente?->telefone) {
            Log::shouldHaveReceived('info')
                ->withArgs(fn (string $message) => str_contains($message, '[Notificador]'))
                ->atLeast()
                ->once();
        }
    }
}
