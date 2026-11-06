<?php

namespace Tests\Feature\Trinks;

use App\Models\User;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Support\EmpresaContext;
use App\Modules\Servicos\Models\ServicoModel;
use App\Modules\Trinks\Contracts\VerifiesTrinksSnsSignature;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegracaoTrinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Sanctum::actingAs(User::query()->where('email', 'admin@barbearia.test')->firstOrFail());
    }

    public function test_ativar_trinks_exige_chave_e_estabelecimento_e_nao_devolve_a_chave(): void
    {
        $this->putJson('/api/empresa', [
            'trinks_ativo' => true,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['trinks_api_key', 'trinks_estabelecimento_id']);

        $response = $this->putJson('/api/empresa', [
            'trinks_ativo' => true,
            'trinks_estabelecimento_id' => '1253',
            'trinks_api_key' => 'segredo-teste',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.trinks_ativo', true)
            ->assertJsonPath('data.trinks_estabelecimento_id', '1253')
            ->assertJsonPath('data.trinks_api_key_definida', true);

        $this->assertArrayNotHasKey('trinks_api_key', $response->json('data'));
        $this->assertSame('segredo-teste', Empresa::query()->find(EmpresaContext::id())->trinks_api_key);

        $this->putJson('/api/empresa', [
            'trinks_ativo' => true,
            'trinks_estabelecimento_id' => '1253',
            'trinks_api_key' => '',
        ])->assertOk();

        $this->assertSame('segredo-teste', Empresa::query()->find(EmpresaContext::id())->trinks_api_key);
    }

    public function test_criar_agendamento_sem_integracao_nao_chama_a_trinks(): void
    {
        Http::fake();

        $this->postJson('/api/agendamentos', $this->payloadAgendamento())->assertCreated();

        Http::assertNothingSent();
        $this->assertNull(
            AgendamentoModel::query()->whereDate('data', now()->addDay()->toDateString())->value('trinks_agendamento_id')
        );
    }

    public function test_criar_agendamento_com_integracao_envia_e_grava_o_id(): void
    {
        $this->ativarTrinks();
        $this->fakeApi();

        $this->postJson('/api/agendamentos', $this->payloadAgendamento())->assertCreated();

        $agendamento = AgendamentoModel::query()
            ->whereDate('data', now()->addDay()->toDateString())
            ->whereTime('hora', '16:00')
            ->firstOrFail();

        $this->assertSame(71428, (int) $agendamento->trinks_agendamento_id);
        $this->assertNull($agendamento->trinks_sync_erro);

        Http::assertSent(function (Request $request) {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), '/v1/agendamentos')) {
                return false;
            }

            $corpo = $request->data();

            return ($corpo['confirmado'] ?? null) === false
                && ($corpo['clienteId'] ?? null) === 9001
                && ($corpo['servicoId'] ?? null) === 301
                && ($corpo['profissionalId'] ?? null) === 501;
        });
    }

    public function test_falha_da_trinks_mantem_o_agendamento_local_e_devolve_erro(): void
    {
        $this->ativarTrinks();
        $this->fakeApi(agendamentoStatus: 400);

        $this->postJson('/api/agendamentos', $this->payloadAgendamento())
            ->assertStatus(422)
            ->assertJsonPath('errors.trinks.0', 'Profissional inativo.');

        $agendamento = AgendamentoModel::query()
            ->whereDate('data', now()->addDay()->toDateString())
            ->firstOrFail();

        $this->assertNull($agendamento->trinks_agendamento_id);
        $this->assertSame('Profissional inativo.', $agendamento->trinks_sync_erro);
    }

    public function test_cancelar_agendamento_vinculado_cancela_na_trinks(): void
    {
        $this->ativarTrinks();
        Http::fake(fn () => Http::response('', 204));

        $agendamento = AgendamentoModel::query()->where('status_agenda', AgendamentoModel::STATUS_AGENDADO)->firstOrFail();
        $agendamento->update(['trinks_agendamento_id' => 71428]);

        $this->postJson('/api/agendamentos/'.$agendamento->id.'/cancelar')->assertOk();

        Http::assertSent(function (Request $request) {
            return $request->method() === 'PATCH'
                && str_contains($request->url(), '/v1/agendamentos/71428/status/cancelado')
                && ($request->data()['quemCancelou'] ?? null) === 3;
        });

        $this->assertSoftDeleted('agendamentos', [
            'id' => $agendamento->id,
            'id_empresa' => EmpresaContext::id(),
        ]);
    }

    public function test_importar_cria_novos_vincula_slot_existente_e_ignora_repetido(): void
    {
        $this->ativarTrinks();
        $hoje = now()->toDateString();

        Http::fake(function (Request $request) use ($hoje) {
            if ($request->method() === 'GET' && str_contains($request->url(), '/v1/agendamentos')) {
                return Http::response([
                    'page' => 1,
                    'pageSize' => 50,
                    'totalPages' => 1,
                    'totalRecords' => 3,
                    'data' => [
                        $this->itemTrinks(111, 4, 'Rafael Costa', 'Marcos Oliveira', 'Corte', $hoje.'T09:00:00'),
                        $this->itemTrinks(222, 3, 'Barbeiro Importado', 'Cliente Importado', 'Servico Importado', $hoje.'T18:00:00', 21, 2, 11),
                        $this->itemTrinks(333, 8, 'Rafael Costa', 'Marcos Oliveira', 'Corte', $hoje.'T15:00:00', 20, 1, 10, 333),
                    ],
                ]);
            }

            return Http::response([
                'page' => 1,
                'pageSize' => 50,
                'totalPages' => 1,
                'totalRecords' => 0,
                'data' => [],
            ]);
        });

        $primeira = $this->postJson('/api/agendamentos/trinks/importar')->assertOk();
        $primeira->assertJsonPath('criados', 1)
            ->assertJsonPath('vinculados', 1)
            ->assertJsonPath('ignorados', 1);

        $this->assertDatabaseHas('agendamentos', [
            'id_empresa' => EmpresaContext::id(),
            'trinks_agendamento_id' => 111,
        ]);
        $this->assertDatabaseHas('agendamentos', [
            'id_empresa' => EmpresaContext::id(),
            'trinks_agendamento_id' => 222,
        ]);
        $this->assertDatabaseMissing('agendamentos', [
            'trinks_agendamento_id' => 333,
        ]);

        $segunda = $this->postJson('/api/agendamentos/trinks/importar')->assertOk();
        $segunda->assertJsonPath('criados', 0)
            ->assertJsonPath('vinculados', 0);

        $this->assertSame(
            1,
            AgendamentoModel::withTrashed()->where('trinks_agendamento_id', 222)->count()
        );
    }

    public function test_exportar_envia_agendamentos_locais_sem_id_da_trinks(): void
    {
        $sequencia = 8000;
        foreach (AgendamentoModel::query()->whereNull('trinks_agendamento_id')->get() as $agendamento) {
            $sequencia++;
            $agendamento->update(['trinks_agendamento_id' => $sequencia]);
        }

        $this->postJson('/api/agendamentos', $this->payloadAgendamento())->assertCreated();
        $this->ativarTrinks();
        $this->fakeApi(agendamentoId: 88001);

        $this->postJson('/api/agendamentos/trinks/exportar')
            ->assertOk()
            ->assertJsonPath('enviados', 1)
            ->assertJsonPath('erros', []);

        $this->assertDatabaseHas('agendamentos', [
            'trinks_agendamento_id' => 88001,
        ]);
    }

    public function test_webhook_confirma_assinatura_e_inclui_agendamento(): void
    {
        $this->ativarTrinks();
        $this->ignorarAssinaturaSns();
        Http::fake(fn () => Http::response([
            'page' => 1,
            'pageSize' => 50,
            'totalPages' => 1,
            'data' => [],
        ], 200));

        $this->postJson('/api/webhooks/trinks', $this->envelopeSns(
            'sub-1',
            'SubscriptionConfirmation',
            'Confirme a inscrição',
            'https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription&Token=abc',
        ))->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'ConfirmSubscription'));

        $this->postJson('/api/webhooks/trinks', $this->envelopeSns(
            'msg-inclusao',
            'Notification',
            json_encode($this->eventoAgendamento(11, 71428), JSON_THROW_ON_ERROR),
        ))->assertOk();

        $this->assertDatabaseHas('agendamentos', [
            'trinks_agendamento_id' => 71428,
            'status_agenda' => AgendamentoModel::STATUS_CONFIRMADO,
            'observacoes' => 'Teste',
        ]);
        $this->assertDatabaseHas('clientes', [
            'nome' => 'Ágata Cristina',
            'trinks_cliente_id' => 313603,
        ]);
        $this->assertDatabaseHas('servicos', [
            'nome' => 'Alisamento',
            'trinks_servico_id' => 5233,
        ]);
        $this->assertDatabaseHas('barbeiros', [
            'nome' => 'Profissional 14342',
            'trinks_profissional_id' => 14342,
        ]);

        $this->postJson('/api/webhooks/trinks', $this->envelopeSns(
            'msg-inclusao',
            'Notification',
            json_encode($this->eventoAgendamento(11, 71428), JSON_THROW_ON_ERROR),
        ))->assertOk();

        $this->assertSame(1, AgendamentoModel::withTrashed()->where('trinks_agendamento_id', 71428)->count());
    }

    public function test_webhook_de_exclusao_cancela_e_ignora_integracao_desligada(): void
    {
        $this->ativarTrinks();
        $this->ignorarAssinaturaSns();
        Http::fake(fn () => Http::response(['page' => 1, 'totalPages' => 1, 'data' => []], 200));

        $this->postJson('/api/webhooks/trinks', $this->envelopeSns(
            'msg-inclusao-2',
            'Notification',
            json_encode($this->eventoAgendamento(11, 71429), JSON_THROW_ON_ERROR),
        ))->assertOk();

        $this->postJson('/api/webhooks/trinks', $this->envelopeSns(
            'msg-exclusao',
            'Notification',
            json_encode($this->eventoAgendamento(13, 71429, 'Cancelado'), JSON_THROW_ON_ERROR),
        ))->assertOk();

        $this->assertSoftDeleted('agendamentos', [
            'trinks_agendamento_id' => 71429,
        ]);

        Empresa::query()->find(EmpresaContext::id())->update(['trinks_ativo' => false]);

        $this->postJson('/api/webhooks/trinks', $this->envelopeSns(
            'msg-off',
            'Notification',
            json_encode($this->eventoAgendamento(11, 80001), JSON_THROW_ON_ERROR),
        ))->assertOk();

        $this->assertDatabaseMissing('agendamentos', [
            'trinks_agendamento_id' => 80001,
        ]);
    }

    private function ativarTrinks(): void
    {
        Empresa::query()->find(EmpresaContext::id())->update([
            'trinks_ativo' => true,
            'trinks_estabelecimento_id' => '1253',
            'trinks_api_key' => 'segredo-teste',
        ]);
    }

    private function ignorarAssinaturaSns(): void
    {
        $this->app->instance(VerifiesTrinksSnsSignature::class, new class implements VerifiesTrinksSnsSignature
        {
            public function assertValid(array $envelope): void {}
        });
    }

    private function fakeApi(int $agendamentoStatus = 201, int $agendamentoId = 71428): void
    {
        Http::fake(function (Request $request) use ($agendamentoStatus, $agendamentoId) {
            $url = $request->url();
            $metodo = $request->method();

            if (str_contains($url, '/v1/profissionais')) {
                return Http::response([
                    'page' => 1,
                    'pageSize' => 50,
                    'totalPages' => 1,
                    'totalRecords' => 1,
                    'data' => [['id' => 501, 'nome' => 'Rafael Costa', 'apelido' => null]],
                ]);
            }

            if (str_contains($url, '/v1/servicos')) {
                return Http::response([
                    'page' => 1,
                    'pageSize' => 50,
                    'totalPages' => 1,
                    'totalRecords' => 1,
                    'data' => [['id' => 301, 'nome' => 'Corte', 'duracaoEmMinutos' => 30, 'preco' => 45]],
                ]);
            }

            if (str_contains($url, '/v1/agendamentos') && $metodo === 'POST') {
                if ($agendamentoStatus !== 201) {
                    return Http::response([
                        'Message' => 'Invalid request.',
                        'Errors' => [[
                            'PropertyName' => 'ProfissionalId',
                            'ErrorMessage' => 'Profissional inativo.',
                        ]],
                    ], $agendamentoStatus);
                }

                return Http::response(['id' => $agendamentoId], 201);
            }

            if (str_contains($url, '/v1/clientes') && $metodo === 'POST') {
                return Http::response(['id' => 9001], 201);
            }

            return Http::response([
                'page' => 1,
                'pageSize' => 50,
                'totalPages' => 1,
                'totalRecords' => 0,
                'data' => [],
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadAgendamento(): array
    {
        $idEmpresa = EmpresaContext::id();

        return [
            'data' => now()->addDay()->toDateString(),
            'hora' => '16:00',
            'id_cliente' => ClienteModel::query()->where('id_empresa', $idEmpresa)->where('nome', 'Marcos Oliveira')->value('id'),
            'id_barbeiro' => BarbeiroModel::query()->where('id_empresa', $idEmpresa)->where('nome', 'Rafael Costa')->value('id'),
            'id_servico' => ServicoModel::query()->where('id_empresa', $idEmpresa)->where('nome', 'Corte')->value('id'),
            'observacoes' => 'Via teste',
            'status_agenda' => AgendamentoModel::STATUS_AGENDADO,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemTrinks(
        int $id,
        int $statusId,
        string $profissional,
        string $cliente,
        string $servico,
        string $inicio,
        int $profissionalId = 20,
        int $clienteId = 1,
        int $servicoId = 10,
        ?int $itemId = null,
    ): array {
        return [
            'id' => $itemId ?? $id,
            'status' => ['id' => $statusId, 'nome' => 'Status'],
            'cliente' => ['id' => $clienteId, 'nome' => $cliente],
            'servico' => ['id' => $servicoId, 'nome' => $servico],
            'profissional' => ['id' => $profissionalId, 'nome' => $profissional],
            'dataHoraInicio' => $inicio,
            'duracaoEmMinutos' => 30,
            'valor' => 45,
            'observacoesDoEstabelecimento' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventoAgendamento(int $tipo, int $agendamentoId, string $status = 'Confirmado'): array
    {
        return [
            'Action' => $tipo === 13 ? 3 : 1,
            'TipoDeEvento' => $tipo,
            'IdDoEstabelecimento' => 1253,
            'IdDoClienteNoEstabelecimento' => 313603,
            'IdDoProfissionalNoEstabelecimento' => 14342,
            'IdDoServicoNoEstabelecimento' => 5233,
            'NomeDoServicoNoEstabelecimento' => 'Alisamento',
            'IdDoAgendamento' => $agendamentoId,
            'PrecoDoServicoNoAgendamento' => '100,00',
            'DuracaoDoAgendamento' => 40,
            'DataHoraInicioDoAgendamento' => '2026-12-01 16:00:00',
            'Observacao' => 'Teste',
            'Status' => $status,
            'NomeDoCliente' => 'Ágata Cristina',
            'TelefoneDoCliente' => [[
                'DDD' => '21',
                'Numero' => '999999999',
                'TelefoneCompleto' => '(21) 99999-9999',
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function envelopeSns(string $messageId, string $type, string $message, ?string $subscribeUrl = null): array
    {
        return [
            'Type' => $type,
            'MessageId' => $messageId,
            'TopicArn' => 'arn:aws:sns:us-east-1:123456789012:trinks',
            'Message' => $message,
            'Timestamp' => '2026-11-05T12:00:00.000Z',
            'SignatureVersion' => '1',
            'Signature' => base64_encode('assinatura'),
            'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService.pem',
            'SubscribeURL' => $subscribeUrl,
            'Token' => 'token',
        ];
    }
}
