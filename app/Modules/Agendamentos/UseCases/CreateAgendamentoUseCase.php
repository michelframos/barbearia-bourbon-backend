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

class CreateAgendamentoUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $repository,
        private readonly DisponibilidadeSlotStrategyInterface $slotStrategy,
        private readonly EnviarAgendamentoParaTrinksUseCase $enviarTrinks,
    ) {}

    public function execute(AgendamentoDTO $dto): AgendamentoModel
    {
        $data = $dto->data;
        $idEmpresa = (int) $data['id_empresa'];
        $data['hora'] = substr((string) $data['hora'], 0, 5);
        $data['id_assistente'] = filled($data['id_assistente'] ?? null)
            ? (int) $data['id_assistente']
            : null;
        $data['id_barbeiro'] = filled($data['id_barbeiro'] ?? null)
            ? (int) $data['id_barbeiro']
            : null;
        $data['status_agenda'] = $data['status_agenda'] ?? AgendamentoModel::STATUS_AGENDADO;
        $data['status'] = $data['status'] ?? 'a';

        $this->assertEntidadesAtivas($idEmpresa, $data);
        $this->assertSlotValido($idEmpresa, (string) $data['data'], (string) $data['hora']);

        if ($data['id_barbeiro'] !== null) {
            $this->assertBarbeiroOfereceServico($idEmpresa, (int) $data['id_barbeiro'], (int) $data['id_servico']);
            $this->assertDisponibilidade(
                $idEmpresa,
                (string) $data['data'],
                (string) $data['hora'],
                (int) $data['id_barbeiro']
            );
        }

        $model = $this->repository->create(AgendamentoDTO::fromRequest($data));
        $this->enviarTrinks->execute($model);

        return $this->repository->findById($idEmpresa, $model->id);
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
