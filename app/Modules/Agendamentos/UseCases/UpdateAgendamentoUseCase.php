<?php

namespace App\Modules\Agendamentos\UseCases;

use App\Modules\Agendamentos\Contracts\AgendamentoRepositoryInterface;
use App\Modules\Agendamentos\Contracts\DisponibilidadeSlotStrategyInterface;
use App\Modules\Agendamentos\DTOs\AgendamentoDTO;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Servicos\Models\ServicoModel;
use App\Modules\Trinks\UseCases\EnviarAgendamentoParaTrinksUseCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repository,
        private readonly DisponibilidadeSlotStrategyInterface $slotStrategy,
        private readonly EnviarAgendamentoParaTrinksUseCase $enviarTrinks,
    ) {}

    public function execute(AgendamentoModel $model, AgendamentoDTO $dto): AgendamentoModel
    {
        $data = $dto->data;
        unset($data['id_empresa']);

        if (isset($data['hora'])) {
            $data['hora'] = substr((string) $data['hora'], 0, 5);
        }

        if (array_key_exists('id_assistente', $data)) {
            $data['id_assistente'] = filled($data['id_assistente'])
                ? (int) $data['id_assistente']
                : null;
        }

        if (array_key_exists('id_barbeiro', $data)) {
            $data['id_barbeiro'] = filled($data['id_barbeiro'])
                ? (int) $data['id_barbeiro']
                : null;
        }

        $merged = array_merge([
            'data' => $model->data?->format('Y-m-d'),
            'hora' => substr((string) $model->hora, 0, 5),
            'id_cliente' => $model->id_cliente,
            'id_assistente' => $model->id_assistente,
            'id_barbeiro' => $model->id_barbeiro,
            'id_servico' => $model->id_servico,
            'observacoes' => $model->observacoes,
            'status_agenda' => $model->status_agenda,
            'status' => $model->status,
        ], $data);

        $idEmpresa = (int) $model->id_empresa;

        $this->assertEntidadesAtivas($idEmpresa, $merged);
        $this->assertSlotValido($idEmpresa, (string) $merged['data'], (string) $merged['hora']);

        if ($merged['id_barbeiro'] !== null) {
            $this->assertBarbeiroOfereceServico(
                $idEmpresa,
                (int) $merged['id_barbeiro'],
                (int) $merged['id_servico']
            );
            $this->assertDisponibilidade(
                $idEmpresa,
                (string) $merged['data'],
                (string) $merged['hora'],
                (int) $merged['id_barbeiro'],
                (int) $model->id
            );
        }

        $updated = $this->repository->update($model, AgendamentoDTO::fromRequest($data));
        $acao = $updated->status_agenda === AgendamentoModel::STATUS_CANCELADO
            ? EnviarAgendamentoParaTrinksUseCase::ACAO_CANCELAR
            : EnviarAgendamentoParaTrinksUseCase::ACAO_SALVAR;
        $this->enviarTrinks->execute($updated, $acao);

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertEntidadesAtivas(int $idEmpresa, array $data): void
    {
        $clienteOk = ClienteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $data['id_cliente'])
            ->where('status', 'a')
            ->exists();

        if (! $clienteOk) {
            throw ValidationException::withMessages([
                'id_cliente' => ['Cliente inválido ou inativo.'],
            ]);
        }

        $servicoOk = ServicoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $data['id_servico'])
            ->where('status', 'a')
            ->exists();

        if (! $servicoOk) {
            throw ValidationException::withMessages([
                'id_servico' => ['Serviço inválido ou inativo.'],
            ]);
        }

        if ($data['id_assistente'] !== null) {
            $assistenteOk = AssistenteModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id', $data['id_assistente'])
                ->where('status', 'a')
                ->exists();

            if (! $assistenteOk) {
                throw ValidationException::withMessages([
                    'id_assistente' => ['Assistente inválida ou inativa.'],
                ]);
            }
        }

        if ($data['id_barbeiro'] !== null) {
            $barbeiroOk = BarbeiroModel::query()
                ->where('id_empresa', $idEmpresa)
                ->where('id', $data['id_barbeiro'])
                ->where('status', 'a')
                ->exists();

            if (! $barbeiroOk) {
                throw ValidationException::withMessages([
                    'id_barbeiro' => ['Barbeiro inválido ou inativo.'],
                ]);
            }
        }
    }

    private function assertBarbeiroOfereceServico(int $idEmpresa, int $idBarbeiro, int $idServico): void
    {
        $oferece = DB::table('barbeiro_servico')
            ->where('id_empresa', $idEmpresa)
            ->where('id_barbeiro', $idBarbeiro)
            ->where('id_servico', $idServico)
            ->exists();

        if (! $oferece) {
            throw ValidationException::withMessages([
                'id_servico' => ['Este barbeiro não oferece o serviço selecionado.'],
            ]);
        }
    }

    private function assertSlotValido(int $idEmpresa, string $data, string $hora): void
    {
        $empresa = Empresa::query()->findOrFail($idEmpresa);
        $slots = $this->slotStrategy->gerarSlots($empresa);

        if (! in_array($hora, $slots, true)) {
            throw ValidationException::withMessages([
                'hora' => ['Horário fora da grade de funcionamento da barbearia.'],
            ]);
        }
    }

    private function assertDisponibilidade(
        int $idEmpresa,
        string $data,
        string $hora,
        int $idBarbeiro,
        ?int $excetoId = null
    ): void {
        if (! $this->slotStrategy->estaDisponivel($idEmpresa, $data, $hora, $idBarbeiro, $excetoId)) {
            throw ValidationException::withMessages([
                'hora' => ['Este horário já está ocupado para o barbeiro selecionado.'],
            ]);
        }
    }
}
