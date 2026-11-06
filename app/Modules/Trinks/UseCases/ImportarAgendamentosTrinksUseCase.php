<?php

namespace App\Modules\Trinks\UseCases;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Trinks\Exceptions\TrinksApiException;
use App\Modules\Trinks\Services\TrinksHttpClient;
use App\Modules\Trinks\Services\TrinksVinculoService;
use App\Modules\Trinks\Support\TrinksStatusMap;
use App\Modules\Trinks\Support\TrinksSyncContext;
use App\Modules\Trinks\Support\TrinksTexto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportarAgendamentosTrinksUseCase
{
    /**
     * @return array{criados: int, vinculados: int, ignorados: int, erros: array<int, string>}
     */
    public function execute(Empresa $empresa): array
    {
        if (! $empresa->trinks_ativo || ! $empresa->integracaoTrinksPronta()) {
            throw ValidationException::withMessages([
                'trinks' => ['Ative a integração e informe a chave e o ID do estabelecimento da Trinks.'],
            ]);
        }

        $client = TrinksHttpClient::para($empresa);
        $vinculo = new TrinksVinculoService($client);
        $dataInicio = now()->startOfDay()->format('Y-m-d\TH:i:s');
        $page = 1;
        $resultado = [
            'criados' => 0,
            'vinculados' => 0,
            'ignorados' => 0,
            'erros' => [],
        ];

        do {
            $lista = $client->listarAgendamentos($page, $dataInicio);
            foreach ($lista['data'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $this->importarItem($empresa, $vinculo, $item, $resultado);
            }

            $totalPages = max(1, (int) ($lista['totalPages'] ?? 1));
            $page++;
        } while ($page <= $totalPages && $page <= 200);

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array{criados: int, vinculados: int, ignorados: int, erros: array<int, string>}  $resultado
     */
    private function importarItem(Empresa $empresa, TrinksVinculoService $vinculo, array $item, array &$resultado): void
    {
        $trinksId = (int) ($item['id'] ?? 0);
        $statusId = (int) ($item['status']['id'] ?? 0);

        if ($trinksId <= 0 || ! in_array($statusId, TrinksStatusMap::ABERTOS, true)) {
            $resultado['ignorados']++;

            return;
        }

        $idEmpresa = (int) $empresa->id;
        $clientePayload = is_array($item['cliente'] ?? null) ? $item['cliente'] : [];
        $servicoPayload = is_array($item['servico'] ?? null) ? $item['servico'] : [];

        if ((int) ($clientePayload['id'] ?? 0) <= 0 || (int) ($servicoPayload['id'] ?? 0) <= 0) {
            $resultado['erros'][] = 'Agendamento '.$trinksId.': Agendamento sem cliente ou serviço.';

            return;
        }

        $jaExiste = AgendamentoModel::withTrashed()
            ->where('id_empresa', $idEmpresa)
            ->where('trinks_agendamento_id', $trinksId)
            ->exists();

        if ($jaExiste) {
            $resultado['ignorados']++;

            return;
        }

        try {
            TrinksSyncContext::withoutOutbound(function () use ($vinculo, $item, $trinksId, $statusId, $idEmpresa, $clientePayload, $servicoPayload, &$resultado): void {
                [$data, $hora] = TrinksTexto::partirDataHora((string) ($item['dataHoraInicio'] ?? ''));
                $profissional = is_array($item['profissional'] ?? null) ? $item['profissional'] : [];

                $barbeiro = $vinculo->barbeiroLocal(
                    $idEmpresa,
                    isset($profissional['id']) ? (int) $profissional['id'] : null,
                    isset($profissional['nome']) ? (string) $profissional['nome'] : null,
                );
                $clienteLocal = $vinculo->clienteLocal(
                    $idEmpresa,
                    (int) $clientePayload['id'],
                    (string) ($clientePayload['nome'] ?? 'Cliente'),
                    null,
                );
                $servicoLocal = $vinculo->servicoLocal(
                    $idEmpresa,
                    (int) $servicoPayload['id'],
                    (string) ($servicoPayload['nome'] ?? 'Serviço'),
                    (int) ($item['duracaoEmMinutos'] ?? 30),
                    TrinksTexto::decimal($item['valor'] ?? 0),
                );

                $slot = null;

                if ($barbeiro !== null) {
                    $slot = AgendamentoModel::withTrashed()
                        ->where('id_empresa', $idEmpresa)
                        ->whereDate('data', $data)
                        ->whereTime('hora', $hora)
                        ->where('id_barbeiro', $barbeiro->id)
                        ->first();
                }

                if ($slot !== null) {
                    if (filled($slot->trinks_agendamento_id) && (int) $slot->trinks_agendamento_id !== $trinksId) {
                        throw new TrinksApiException('O horário local já está vinculado a outro agendamento da Trinks.');
                    }

                    $slot->trinks_agendamento_id = $trinksId;
                    $slot->trinks_sync_erro = null;
                    $slot->trinks_sincronizado_em = now();

                    if ($slot->trashed()) {
                        $slot->status_agenda = TrinksStatusMap::deId($statusId) ?? AgendamentoModel::STATUS_AGENDADO;
                        $slot->restore();
                    }

                    $slot->save();
                    $resultado['vinculados']++;

                    return;
                }

                $vinculo->garantirBarbeiroServico($idEmpresa, $barbeiro?->id, (int) $servicoLocal->id);

                DB::transaction(function () use ($idEmpresa, $data, $hora, $clienteLocal, $barbeiro, $servicoLocal, $item, $statusId, $trinksId): void {
                    AgendamentoModel::query()->create([
                        'id_empresa' => $idEmpresa,
                        'data' => $data,
                        'hora' => $hora,
                        'id_cliente' => $clienteLocal->id,
                        'id_assistente' => null,
                        'id_barbeiro' => $barbeiro?->id,
                        'id_servico' => $servicoLocal->id,
                        'observacoes' => $item['observacoesDoEstabelecimento'] ?? null,
                        'status_agenda' => TrinksStatusMap::deId($statusId) ?? AgendamentoModel::STATUS_AGENDADO,
                        'status' => 'a',
                        'trinks_agendamento_id' => $trinksId,
                        'trinks_sync_erro' => null,
                        'trinks_sincronizado_em' => now(),
                    ]);
                });

                $resultado['criados']++;
            });
        } catch (\Throwable $exception) {
            $resultado['erros'][] = 'Agendamento '.$trinksId.': '.$exception->getMessage();
        }
    }
}
