<?php

namespace App\Modules\Trinks\UseCases;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Trinks\Exceptions\TrinksApiException;
use App\Modules\Trinks\Services\TrinksHttpClient;
use App\Modules\Trinks\Services\TrinksVinculoService;
use App\Modules\Trinks\Support\TrinksSyncContext;
use Illuminate\Validation\ValidationException;

class EnviarAgendamentoParaTrinksUseCase
{
    public const ACAO_SALVAR = 'salvar';

    public const ACAO_CONFIRMAR = 'confirmar';

    public const ACAO_CANCELAR = 'cancelar';

    public function execute(AgendamentoModel $agendamento, string $acao = self::ACAO_SALVAR): void
    {
        if (TrinksSyncContext::isInbound()) {
            return;
        }

        $empresa = Empresa::query()->find($agendamento->id_empresa);

        if ($empresa === null || ! $empresa->trinks_ativo) {
            return;
        }

        if (! $empresa->integracaoTrinksPronta()) {
            throw ValidationException::withMessages([
                'trinks' => ['Informe a chave e o ID do estabelecimento da Trinks.'],
            ]);
        }

        $client = TrinksHttpClient::para($empresa);
        $this->enviarComCliente($empresa, $agendamento, $acao, $client, new TrinksVinculoService($client));
    }

    public function enviarComCliente(
        Empresa $empresa,
        AgendamentoModel $agendamento,
        string $acao,
        TrinksHttpClient $client,
        TrinksVinculoService $vinculo,
    ): void {
        if (! $empresa->trinks_ativo || TrinksSyncContext::isInbound()) {
            return;
        }

        $model = $this->recarregar($agendamento);

        try {
            if ($acao === self::ACAO_CANCELAR || $model->status_agenda === AgendamentoModel::STATUS_CANCELADO) {
                $this->cancelar($model, $client);
            } elseif ($acao === self::ACAO_CONFIRMAR) {
                $this->confirmar($model, $client, $vinculo);
            } else {
                $this->salvar($model, $client, $vinculo);
            }

            $model->update([
                'trinks_sync_erro' => null,
                'trinks_sincronizado_em' => now(),
            ]);
        } catch (TrinksApiException $exception) {
            $model->update([
                'trinks_sync_erro' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw ValidationException::withMessages([
                'trinks' => [$exception->getMessage()],
            ]);
        }
    }

    private function salvar(AgendamentoModel $model, TrinksHttpClient $client, TrinksVinculoService $vinculo): void
    {
        if (filled($model->trinks_agendamento_id)) {
            $assistenteId = $this->assistenteId($model, $vinculo, $this->profissionalId($model, $vinculo));
            $client->editarAgendamento((int) $model->trinks_agendamento_id, $this->payload($model, $vinculo, false));

            if ($assistenteId === null) {
                $client->removerAssistente((int) $model->trinks_agendamento_id);
            }

            if ($model->status_agenda === AgendamentoModel::STATUS_CONFIRMADO) {
                $client->confirmarAgendamento((int) $model->trinks_agendamento_id);
            }

            return;
        }

        $id = $client->criarAgendamento($this->payload($model, $vinculo, true));
        $model->update(['trinks_agendamento_id' => $id]);
    }

    private function confirmar(AgendamentoModel $model, TrinksHttpClient $client, TrinksVinculoService $vinculo): void
    {
        if (! filled($model->trinks_agendamento_id)) {
            $id = $client->criarAgendamento($this->payload($model, $vinculo, true));
            $model->update(['trinks_agendamento_id' => $id]);

            return;
        }

        $client->confirmarAgendamento((int) $model->trinks_agendamento_id);
    }

    private function cancelar(AgendamentoModel $model, TrinksHttpClient $client): void
    {
        if (! filled($model->trinks_agendamento_id)) {
            return;
        }

        $client->cancelarAgendamento((int) $model->trinks_agendamento_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AgendamentoModel $model, TrinksVinculoService $vinculo, bool $incluirConfirmado): array
    {
        $cliente = $model->cliente;
        $servico = $model->servico;

        if ($cliente === null || $servico === null) {
            throw new TrinksApiException('Agendamento sem cliente ou serviço para enviar à Trinks.');
        }

        $profissionalId = $this->profissionalId($model, $vinculo);
        $assistenteId = $this->assistenteId($model, $vinculo, $profissionalId);
        $data = $model->data?->format('Y-m-d');
        $hora = substr((string) $model->hora, 0, 5);

        $corpo = [
            'servicoId' => $vinculo->servicoIdParaEnvio($servico),
            'clienteId' => $vinculo->clienteIdParaEnvio($cliente),
            'dataHoraInicio' => $data.'T'.$hora.':00',
            'duracaoEmMinutos' => max(1, min(9999, (int) $servico->duracao_minutos)),
            'valor' => max(0, (float) $servico->preco),
            'observacoes' => (string) ($model->observacoes ?? ''),
        ];

        if ($profissionalId !== null) {
            $corpo['profissionalId'] = $profissionalId;
        }

        if ($assistenteId !== null) {
            $corpo['assistenteId'] = $assistenteId;
        }

        if ($incluirConfirmado) {
            $corpo['confirmado'] = in_array($model->status_agenda, [
                AgendamentoModel::STATUS_CONFIRMADO,
                AgendamentoModel::STATUS_EM_ATENDIMENTO,
            ], true);
        }

        return $corpo;
    }

    private function profissionalId(AgendamentoModel $model, TrinksVinculoService $vinculo): ?int
    {
        return $vinculo->profissionalIdParaEnvio($model->barbeiro);
    }

    private function assistenteId(AgendamentoModel $model, TrinksVinculoService $vinculo, ?int $profissionalId): ?int
    {
        $assistenteId = $vinculo->assistenteIdParaEnvio($model->assistente);

        if ($assistenteId !== null && $assistenteId === $profissionalId) {
            return null;
        }

        return $assistenteId;
    }

    private function recarregar(AgendamentoModel $agendamento): AgendamentoModel
    {
        $idEmpresa = (int) $agendamento->id_empresa;

        return AgendamentoModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $agendamento->id)
            ->with([
                'cliente' => fn ($query) => $query->where('id_empresa', $idEmpresa),
                'assistente' => fn ($query) => $query->where('id_empresa', $idEmpresa),
                'barbeiro' => fn ($query) => $query->where('id_empresa', $idEmpresa),
                'servico' => fn ($query) => $query->where('id_empresa', $idEmpresa),
            ])
            ->firstOrFail();
    }
}
