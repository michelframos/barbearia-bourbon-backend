<?php

namespace Tests\Feature\Atendimentos;

use App\Models\User;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Support\EmpresaContext;
use App\Modules\Servicos\Models\ServicoModel;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SelecaoAssistenteSequencialTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $idEmpresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->idEmpresa = EmpresaContext::id();
        $this->user = User::query()->where('email', 'admin@barbearia.test')->firstOrFail();

        Sanctum::actingAs($this->user);
    }

    public function test_modo_manual_exige_assistente_ao_iniciar(): void
    {
        [$atendimentoId, $barbeiroId] = $this->criarAtendimentoAguardando();

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroId,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id_assistente']);
    }

    public function test_modo_manual_bloqueia_assistente_sem_vinculo(): void
    {
        $assistente = $this->criarAssistente('Sem vínculo', 1, 'livre');
        [$atendimentoId, $barbeiroId] = $this->criarAtendimentoAguardando();

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroId,
            'id_assistente' => $assistente->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id_assistente']);
    }

    public function test_modo_automatico_atribui_proxima_assistente_e_avanca_cursor(): void
    {
        $this->configurarEmpresa(Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL);
        $this->limparAssistentes();

        $assistente1 = $this->criarAssistente('Assistente 1', 1, 'livre');
        $assistente2 = $this->criarAssistente('Assistente 2', 2, 'livre');
        [$atendimentoId, $barbeiroId] = $this->criarAtendimentoAguardando();
        $this->vincular($assistente1, $barbeiroId);
        $this->vincular($assistente2, $barbeiroId);

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroId,
        ]);

        $response->assertOk();
        $this->assertSame($assistente1->id, $response->json('data.id_assistente'));

        $empresa = Empresa::query()->find($this->idEmpresa);
        $this->assertSame($assistente1->id, $empresa->ultima_assistente_sequencial_id);

        [$atendimento2Id, $barbeiro2Id] = $this->criarAtendimentoAguardando();
        $this->vincular($assistente1, $barbeiro2Id);
        $this->vincular($assistente2, $barbeiro2Id);

        $response2 = $this->postJson("/api/atendimentos/{$atendimento2Id}/iniciar", [
            'id_barbeiro' => $barbeiro2Id,
        ]);

        $response2->assertOk();
        $this->assertSame($assistente2->id, $response2->json('data.id_assistente'));
    }

    public function test_modo_automatico_pula_assistente_em_checkout(): void
    {
        $this->configurarEmpresa(Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL);
        $this->limparAssistentes();

        $offline = $this->criarAssistente('Offline', 1, 'offline');
        $assistente2 = $this->criarAssistente('Disponível', 2, 'livre');
        [$atendimentoId, $barbeiroId] = $this->criarAtendimentoAguardando();
        $this->vincular($offline, $barbeiroId);
        $this->vincular($assistente2, $barbeiroId);

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroId,
        ]);

        $response->assertOk();
        $this->assertSame($assistente2->id, $response->json('data.id_assistente'));
    }

    public function test_modo_automatico_pula_assistente_sem_vinculo_no_dia(): void
    {
        $this->configurarEmpresa(Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL);
        $this->limparAssistentes();

        $semVinculo = $this->criarAssistente('Sem vínculo', 1, 'livre');
        $comVinculo = $this->criarAssistente('Com vínculo', 2, 'livre');
        [$atendimentoId, $barbeiroId] = $this->criarAtendimentoAguardando();
        $this->vincular($comVinculo, $barbeiroId);

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroId,
        ]);

        $response->assertOk();
        $this->assertSame($comVinculo->id, $response->json('data.id_assistente'));
        $this->assertNotSame($semVinculo->id, $response->json('data.id_assistente'));
    }

    public function test_modo_automatico_ordem_por_checkin_atrasada_fica_no_fim(): void
    {
        $this->configurarEmpresa(
            Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL,
            2,
            Empresa::FONTE_ORDEM_CHECKIN,
        );
        $this->limparAssistentes();

        $atrasada = $this->criarAssistente('Atrasada', 1, 'livre', now()->setTime(9, 30));
        $cedo = $this->criarAssistente('Cedo', 2, 'livre', now()->setTime(8, 0));
        [$atendimentoId, $barbeiroId] = $this->criarAtendimentoAguardando();
        $this->vincular($atrasada, $barbeiroId);
        $this->vincular($cedo, $barbeiroId);

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroId,
        ]);

        $response->assertOk();
        $this->assertSame($cedo->id, $response->json('data.id_assistente'));
    }

    public function test_modo_automatico_pula_assistente_com_capacidade_maxima(): void
    {
        $this->configurarEmpresa(Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL, 2);
        $this->limparAssistentes();

        $assistente1 = $this->criarAssistente('Cheia', 1, 'exclusiva');
        $assistente2 = $this->criarAssistente('Livre', 2, 'livre');
        $barbeiroOcupado = $this->criarBarbeiro('Barbeiro Ocupado');
        $barbeiroInicio = $this->criarBarbeiro('Barbeiro Livre');
        $this->vincular($assistente1, $barbeiroOcupado->id);
        $this->vincular($assistente1, $barbeiroInicio->id);
        $this->vincular($assistente2, $barbeiroInicio->id);
        $cliente = $this->criarCliente();
        $servico = $this->criarServico();
        $data = now()->toDateString();

        AtendimentoModel::create([
            'id_empresa' => $this->idEmpresa,
            'id_cliente' => $cliente->id,
            'id_assistente' => $assistente1->id,
            'id_barbeiro' => $barbeiroOcupado->id,
            'id_servico' => $servico->id,
            'posicao_fila' => 1,
            'data' => $data,
            'hora_chegada' => '09:00',
            'status_atendimento' => AtendimentoModel::STATUS_EM_ATENDIMENTO,
            'status' => 'a',
        ]);

        AtendimentoModel::create([
            'id_empresa' => $this->idEmpresa,
            'id_cliente' => $cliente->id,
            'id_assistente' => $assistente1->id,
            'id_barbeiro' => null,
            'id_servico' => $servico->id,
            'posicao_fila' => 2,
            'data' => $data,
            'hora_chegada' => '09:05',
            'status_atendimento' => AtendimentoModel::STATUS_AGUARDANDO,
            'status' => 'a',
        ]);

        [$atendimentoId] = $this->criarAtendimentoAguardando();

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroInicio->id,
        ]);

        $response->assertOk();
        $this->assertSame($assistente2->id, $response->json('data.id_assistente'));
    }

    public function test_modo_automatico_nao_pula_assistente_com_vaga_disponivel(): void
    {
        $this->configurarEmpresa(Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL, 2);
        $this->limparAssistentes();

        $assistente1 = $this->criarAssistente('Parcial', 1, 'exclusiva');
        $barbeiroOcupado = $this->criarBarbeiro('Barbeiro Ocupado');
        $barbeiroInicio = $this->criarBarbeiro('Barbeiro Livre');
        $this->vincular($assistente1, $barbeiroOcupado->id);
        $this->vincular($assistente1, $barbeiroInicio->id);
        $cliente = $this->criarCliente();
        $servico = $this->criarServico();
        $data = now()->toDateString();

        AtendimentoModel::create([
            'id_empresa' => $this->idEmpresa,
            'id_cliente' => $cliente->id,
            'id_assistente' => $assistente1->id,
            'id_barbeiro' => $barbeiroOcupado->id,
            'id_servico' => $servico->id,
            'posicao_fila' => 1,
            'data' => $data,
            'hora_chegada' => '09:00',
            'status_atendimento' => AtendimentoModel::STATUS_EM_ATENDIMENTO,
            'status' => 'a',
        ]);

        [$atendimentoId] = $this->criarAtendimentoAguardando();

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroInicio->id,
        ]);

        $response->assertOk();
        $this->assertSame($assistente1->id, $response->json('data.id_assistente'));
        $this->assertSame(2, $response->json('data.posicao_fila'));
    }

    public function test_modo_automatico_retorna_erro_quando_nenhuma_assistente_elegivel(): void
    {
        $this->configurarEmpresa(Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL, 1);
        $this->limparAssistentes();

        $assistente = $this->criarAssistente('Cheia', 1, 'exclusiva');
        $barbeiro = $this->criarBarbeiro();
        $this->vincular($assistente, $barbeiro->id);
        $cliente = $this->criarCliente();
        $servico = $this->criarServico();
        $data = now()->toDateString();

        AtendimentoModel::create([
            'id_empresa' => $this->idEmpresa,
            'id_cliente' => $cliente->id,
            'id_assistente' => $assistente->id,
            'id_barbeiro' => $barbeiro->id,
            'id_servico' => $servico->id,
            'posicao_fila' => 1,
            'data' => $data,
            'hora_chegada' => '09:00',
            'status_atendimento' => AtendimentoModel::STATUS_EM_ATENDIMENTO,
            'status' => 'a',
        ]);

        [$atendimentoId] = $this->criarAtendimentoAguardando();
        $barbeiroInicio = $this->criarBarbeiro('Barbeiro Livre');
        $this->vincular($assistente, $barbeiroInicio->id);

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroInicio->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id_assistente']);
    }

    public function test_max_tres_atendimentos_simultaneos_permite_terceira_posicao(): void
    {
        $this->configurarEmpresa(Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL, 3);
        $this->limparAssistentes();

        $assistente = $this->criarAssistente('Assistente', 1, 'exclusiva');
        $barbeiroOcupado = $this->criarBarbeiro('Barbeiro Ocupado');
        $barbeiroInicio = $this->criarBarbeiro('Barbeiro Livre');
        $this->vincular($assistente, $barbeiroOcupado->id);
        $this->vincular($assistente, $barbeiroInicio->id);
        $cliente = $this->criarCliente();
        $servico = $this->criarServico();
        $data = now()->toDateString();

        foreach ([1, 2] as $posicao) {
            AtendimentoModel::create([
                'id_empresa' => $this->idEmpresa,
                'id_cliente' => $cliente->id,
                'id_assistente' => $assistente->id,
                'id_barbeiro' => $posicao === 1 ? $barbeiroOcupado->id : null,
                'id_servico' => $servico->id,
                'posicao_fila' => $posicao,
                'data' => $data,
                'hora_chegada' => '09:0'.($posicao - 1),
                'status_atendimento' => $posicao === 1
                    ? AtendimentoModel::STATUS_EM_ATENDIMENTO
                    : AtendimentoModel::STATUS_AGUARDANDO,
                'status' => 'a',
            ]);
        }

        [$atendimentoId] = $this->criarAtendimentoAguardando();

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroInicio->id,
        ]);

        $response->assertOk();
        $this->assertSame($assistente->id, $response->json('data.id_assistente'));
        $this->assertSame(3, $response->json('data.posicao_fila'));
    }

    public function test_reset_cursor_lazy_meia_noite(): void
    {
        $this->configurarEmpresa(
            Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL,
            2,
            Empresa::FONTE_ORDEM_MANUAL,
            Empresa::RESET_CURSOR_MEIA_NOITE,
        );
        $this->limparAssistentes();

        $assistente1 = $this->criarAssistente('A1', 1, 'livre');
        $assistente2 = $this->criarAssistente('A2', 2, 'livre');

        Empresa::query()->where('id', $this->idEmpresa)->update([
            'ultima_assistente_sequencial_id' => $assistente1->id,
            'cursor_sequencial_data' => now()->subDay()->toDateString(),
        ]);

        [$atendimentoId, $barbeiroId] = $this->criarAtendimentoAguardando();
        $this->vincular($assistente1, $barbeiroId);
        $this->vincular($assistente2, $barbeiroId);

        $response = $this->postJson("/api/atendimentos/{$atendimentoId}/iniciar", [
            'id_barbeiro' => $barbeiroId,
        ]);

        $response->assertOk();
        $this->assertSame($assistente1->id, $response->json('data.id_assistente'));
    }

    public function test_reset_cursor_no_primeiro_checkin_do_dia(): void
    {
        $this->configurarEmpresa(
            Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL,
            2,
            Empresa::FONTE_ORDEM_CHECKIN,
            Empresa::RESET_CURSOR_PRIMEIRO_CHECKIN,
        );
        $this->limparAssistentes();

        $assistente = $this->criarAssistente('A1', 1, 'offline');

        Empresa::query()->where('id', $this->idEmpresa)->update([
            'ultima_assistente_sequencial_id' => 999,
            'cursor_sequencial_data' => now()->subDay()->toDateString(),
        ]);

        $response = $this->putJson("/api/assistentes/{$assistente->id}", [
            'status_operacional' => 'livre',
        ]);

        $response->assertOk();

        $empresa = Empresa::query()->find($this->idEmpresa);
        $this->assertNull($empresa->ultima_assistente_sequencial_id);
        $this->assertSame(now()->toDateString(), $empresa->cursor_sequencial_data?->format('Y-m-d'));
        $this->assertNotNull($response->json('data.hora_checkin'));
    }

    public function test_atribuicao_no_checkin_cliente_exige_barbeiro_e_atribui(): void
    {
        $this->configurarEmpresa(
            Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL,
            2,
            Empresa::FONTE_ORDEM_MANUAL,
            Empresa::RESET_CURSOR_MEIA_NOITE,
            Empresa::MOMENTO_ATRIBUICAO_CHECKIN_CLIENTE,
        );
        $this->limparAssistentes();

        $assistente = $this->criarAssistente('A1', 1, 'livre');
        $barbeiro = $this->criarBarbeiro();
        $this->vincular($assistente, $barbeiro->id);
        $cliente = $this->criarCliente();
        $servico = $this->criarServico();

        $semBarbeiro = $this->postJson('/api/atendimentos/check-in', [
            'id_cliente' => $cliente->id,
            'id_servico' => $servico->id,
        ]);
        $semBarbeiro->assertUnprocessable()
            ->assertJsonValidationErrors(['id_barbeiro']);

        $comBarbeiro = $this->postJson('/api/atendimentos/check-in', [
            'id_cliente' => $cliente->id,
            'id_servico' => $servico->id,
            'id_barbeiro' => $barbeiro->id,
        ]);

        $comBarbeiro->assertCreated();
        $this->assertSame($assistente->id, $comBarbeiro->json('data.id_assistente'));
        $this->assertSame($barbeiro->id, $comBarbeiro->json('data.id_barbeiro'));
    }

    private function configurarEmpresa(
        string $modo,
        int $max = 2,
        string $fonte = Empresa::FONTE_ORDEM_MANUAL,
        string $reset = Empresa::RESET_CURSOR_MEIA_NOITE,
        string $momento = Empresa::MOMENTO_ATRIBUICAO_INICIAR,
    ): void {
        Empresa::query()->where('id', $this->idEmpresa)->update([
            'modo_selecao_assistente' => $modo,
            'max_atendimentos_simultaneos_assistente' => $max,
            'ultima_assistente_sequencial_id' => null,
            'fonte_ordem_assistente' => $fonte,
            'reset_cursor_assistente' => $reset,
            'momento_atribuicao_assistente' => $momento,
            'cursor_sequencial_data' => now()->toDateString(),
        ]);
    }

    private function limparAssistentes(): void
    {
        AssistenteModel::query()
            ->where('id_empresa', $this->idEmpresa)
            ->update([
                'status_operacional' => 'offline',
                'ordem_sequencial' => 999,
                'hora_checkin' => null,
            ]);
    }

    private function criarAssistente(
        string $nome,
        int $ordem,
        string $operacional,
        mixed $horaCheckin = null,
    ): AssistenteModel {
        return AssistenteModel::create([
            'id_empresa' => $this->idEmpresa,
            'nome' => $nome,
            'ordem_sequencial' => $ordem,
            'hora_checkin' => $horaCheckin,
            'status_operacional' => $operacional,
            'status' => 'a',
        ]);
    }

    private function vincular(AssistenteModel $assistente, int $idBarbeiro, ?int $diaSemana = null): void
    {
        $diaSemana ??= now()->dayOfWeek;

        DB::table('assistente_barbeiro_dia')->updateOrInsert(
            [
                'id_empresa' => $this->idEmpresa,
                'id_assistente' => $assistente->id,
                'id_barbeiro' => $idBarbeiro,
                'dia_semana' => $diaSemana,
            ],
            []
        );
    }

    private function criarBarbeiro(string $nome = 'Barbeiro Teste'): BarbeiroModel
    {
        return BarbeiroModel::create([
            'id_empresa' => $this->idEmpresa,
            'nome' => $nome,
            'status_operacional' => 'disponivel',
            'status' => 'a',
        ]);
    }

    private function criarCliente(): ClienteModel
    {
        return ClienteModel::create([
            'id_empresa' => $this->idEmpresa,
            'nome' => 'Cliente Teste',
            'status' => 'a',
        ]);
    }

    private function criarServico(): ServicoModel
    {
        return ServicoModel::create([
            'id_empresa' => $this->idEmpresa,
            'nome' => 'Corte',
            'duracao_minutos' => 30,
            'status' => 'a',
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function criarAtendimentoAguardando(): array
    {
        $barbeiro = $this->criarBarbeiro();
        $cliente = $this->criarCliente();
        $servico = $this->criarServico();

        $atendimento = AtendimentoModel::create([
            'id_empresa' => $this->idEmpresa,
            'id_cliente' => $cliente->id,
            'id_assistente' => null,
            'id_barbeiro' => null,
            'id_servico' => $servico->id,
            'posicao_fila' => null,
            'data' => now()->toDateString(),
            'hora_chegada' => now()->format('H:i'),
            'status_atendimento' => AtendimentoModel::STATUS_AGUARDANDO,
            'status' => 'a',
        ]);

        return [(int) $atendimento->id, (int) $barbeiro->id];
    }
}
