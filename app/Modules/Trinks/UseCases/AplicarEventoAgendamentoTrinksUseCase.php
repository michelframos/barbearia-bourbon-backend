<?php

namespace App\Modules\Trinks\UseCases;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Trinks\Services\TrinksHttpClient;
use App\Modules\Trinks\Services\TrinksVinculoService;
use App\Modules\Trinks\Support\TrinksStatusMap;
use App\Modules\Trinks\Support\TrinksSyncContext;
use App\Modules\Trinks\Support\TrinksTexto;

class AplicarEventoAgendamentoTrinksUseCase
{
    /**
     * @param  array<string, mixed>  $evento
     */
    public function execute(array $evento): void
    {
        $tipo = (int) ($evento['TipoDeEvento'] ?? 0);

        if (! in_array($tipo, [11, 12, 13], true)) {
            return;
        }

        $empresa = Empresa::query()
            ->where('trinks_estabelecimento_id', (string) ($evento['IdDoEstabelecimento'] ?? ''))
            ->where('trinks_ativo', true)
            ->first();

        if ($empresa === null || ! $empresa->integracaoTrinksPronta()) {
            return;
        }

        $trinksId = (int) ($evento['IdDoAgendamento'] ?? 0);

        if ($trinksId <= 0) {
            return;
        }

        TrinksSyncContext::withoutOutbound(function () use ($empresa, $evento, $tipo, $trinksId): void {
            if ($tipo === 13) {
                $this->excluir((int) $empresa->id, $trinksId);

                return;
            }

            $this->salvar($empresa, $evento, $trinksId);
        });
    }

    private function excluir(int $idEmpresa, int $trinksId): void
    {
        $agendamento = AgendamentoModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('trinks_agendamento_id', $trinksId)
            ->first();

        if ($agendamento === null) {
            return;
        }

        $agendamento->status_agenda = AgendamentoModel::STATUS_CANCELADO;
        $agendamento->trinks_sync_erro = null;
        $agendamento->trinks_sincronizado_em = now();
        $agendamento->save();

        if (! $agendamento->trashed()) {
            $agendamento->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $evento
     */
    private function salvar(Empresa $empresa, array $evento, int $trinksId): void
    {
        if ((int) ($evento['IdDoClienteNoEstabelecimento'] ?? 0) <= 0
            || (int) ($evento['IdDoServicoNoEstabelecimento'] ?? 0) <= 0) {
            return;
        }

        $idEmpresa = (int) $empresa->id;
        $client = TrinksHttpClient::para($empresa);
        $vinculo = new TrinksVinculoService($client);
        [$data, $hora] = TrinksTexto::partirDataHora((string) ($evento['DataHoraInicioDoAgendamento'] ?? ''));
        $duracao = (int) ($evento['DuracaoDoAgendamento'] ?? 30);
        $preco = TrinksTexto::decimal($evento['PrecoDoServicoNoAgendamento'] ?? 0);
        $telefone = $this->telefone($evento);

        $cliente = $vinculo->clienteLocal(
            $idEmpresa,
            (int) ($evento['IdDoClienteNoEstabelecimento'] ?? 0),
            (string) ($evento['NomeDoCliente'] ?? 'Cliente'),
            $telefone,
        );
        $barbeiro = $vinculo->barbeiroLocal(
            $idEmpresa,
            isset($evento['IdDoProfissionalNoEstabelecimento'])
                ? (int) $evento['IdDoProfissionalNoEstabelecimento']
                : null,
            null,
        );
        $servico = $vinculo->servicoLocal(
            $idEmpresa,
            (int) ($evento['IdDoServicoNoEstabelecimento'] ?? 0),
            (string) ($evento['NomeDoServicoNoEstabelecimento'] ?? 'Serviço'),
            $duracao,
            $preco,
            true,
        );

        $vinculo->garantirBarbeiroServico($idEmpresa, $barbeiro?->id, (int) $servico->id);

        $agendamento = AgendamentoModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('trinks_agendamento_id', $trinksId)
            ->first();

        $dados = [
            'data' => $data,
            'hora' => $hora,
            'id_cliente' => $cliente->id,
            'id_barbeiro' => $barbeiro?->id,
            'id_servico' => $servico->id,
            'observacoes' => ($evento['Observacao'] ?? '') !== '' ? $evento['Observacao'] : null,
            'status_agenda' => TrinksStatusMap::deNome(isset($evento['Status']) ? (string) $evento['Status'] : null),
            'trinks_agendamento_id' => $trinksId,
            'trinks_sync_erro' => null,
            'trinks_sincronizado_em' => now(),
        ];

        if ($agendamento === null) {
            AgendamentoModel::query()->create($dados + [
                'id_empresa' => $idEmpresa,
                'id_assistente' => null,
                'status' => 'a',
            ]);

            return;
        }

        if ($agendamento->trashed() && $dados['status_agenda'] !== AgendamentoModel::STATUS_CANCELADO) {
            $agendamento->restore();
        }

        $agendamento->update($dados);

        if ($dados['status_agenda'] === AgendamentoModel::STATUS_CANCELADO && ! $agendamento->trashed()) {
            $agendamento->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $evento
     */
    private function telefone(array $evento): ?string
    {
        $telefones = $evento['TelefoneDoCliente'] ?? [];

        if (! is_array($telefones) || $telefones === []) {
            return null;
        }

        $primeiro = $telefones[0] ?? null;

        if (! is_array($primeiro)) {
            return null;
        }

        return TrinksTexto::telefoneExibicao(
            isset($primeiro['TelefoneCompleto']) ? (string) $primeiro['TelefoneCompleto'] : null,
            isset($primeiro['DDD']) ? (string) $primeiro['DDD'] : null,
            isset($primeiro['Numero']) ? (string) $primeiro['Numero'] : null,
        );
    }
}
