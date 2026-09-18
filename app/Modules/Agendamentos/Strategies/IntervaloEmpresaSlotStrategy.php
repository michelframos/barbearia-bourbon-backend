<?php

namespace App\Modules\Agendamentos\Strategies;

use App\Modules\Agendamentos\Contracts\DisponibilidadeSlotStrategyInterface;
use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Core\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class IntervaloEmpresaSlotStrategy implements DisponibilidadeSlotStrategyInterface
{
    public function gerarSlots(Empresa $empresa): array
    {
        $abertura = $this->normalizeTime($empresa->horario_abertura ?? '08:00:00');
        $fechamento = $this->normalizeTime($empresa->horario_fechamento ?? '19:00:00');
        $intervalo = max(5, (int) ($empresa->intervalo_agenda_minutos ?? 30));

        $cursor = Carbon::createFromFormat('H:i', $abertura);
        $fim = Carbon::createFromFormat('H:i', $fechamento);

        if (! $cursor || ! $fim || $cursor->gte($fim)) {
            throw ValidationException::withMessages([
                'hora' => ['Horário de funcionamento da empresa inválido.'],
            ]);
        }

        $slots = [];

        while ($cursor->lt($fim)) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes($intervalo);
        }

        return $slots;
    }

    public function estaDisponivel(
        int $idEmpresa,
        string $data,
        string $hora,
        int $idBarbeiro,
        ?int $excetoAgendamentoId = null
    ): bool {
        $hora = $this->normalizeTime($hora);

        $query = AgendamentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->whereDate('data', $data)
            ->whereTime('hora', $hora)
            ->where('id_barbeiro', $idBarbeiro)
            ->whereIn('status_agenda', AgendamentoModel::STATUS_BLOQUEANTES);

        if ($excetoAgendamentoId !== null) {
            $query->where('id', '!=', $excetoAgendamentoId);
        }

        return ! $query->exists();
    }

    public function montarGrade(int $idEmpresa, string $data, ?int $idBarbeiro = null): array
    {
        $empresa = Empresa::query()->findOrFail($idEmpresa);
        $slots = $this->gerarSlots($empresa);

        $agendamentos = AgendamentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->whereDate('data', $data)
            ->whereIn('status_agenda', AgendamentoModel::STATUS_BLOQUEANTES)
            ->when($idBarbeiro !== null, fn ($q) => $q->where('id_barbeiro', $idBarbeiro))
            ->with([
                'cliente' => fn ($q) => $q->where('id_empresa', $idEmpresa),
                'assistente' => fn ($q) => $q->where('id_empresa', $idEmpresa),
                'barbeiro' => fn ($q) => $q->where('id_empresa', $idEmpresa),
                'servico' => fn ($q) => $q->where('id_empresa', $idEmpresa),
            ])
            ->orderBy('hora')
            ->get();

        $porHora = $idBarbeiro !== null
            ? $agendamentos->keyBy(fn (AgendamentoModel $a) => $this->normalizeTime((string) $a->hora))
            : $agendamentos->groupBy(fn (AgendamentoModel $a) => $this->normalizeTime((string) $a->hora));

        $grade = [];

        foreach ($slots as $hora) {
            if ($idBarbeiro !== null) {
                /** @var AgendamentoModel|null $agendamento */
                $agendamento = $porHora->get($hora);
                $mapped = $agendamento ? $this->mapAgendamento($agendamento) : null;
                $grade[] = [
                    'hora' => $hora,
                    'disponivel' => $agendamento === null,
                    'agendamento' => $mapped,
                    'agendamentos' => $mapped ? [$mapped] : [],
                ];
            } else {
                /** @var Collection<int, AgendamentoModel> $lista */
                $lista = $porHora->get($hora, collect());
                $itens = $lista
                    ->map(fn (AgendamentoModel $a) => $this->mapAgendamento($a))
                    ->values()
                    ->all();

                $grade[] = [
                    'hora' => $hora,
                    'disponivel' => $itens === [],
                    'agendamento' => $itens[0] ?? null,
                    'agendamentos' => $itens,
                ];
            }
        }

        return [
            'meta' => [
                'data' => $data,
                'horario_abertura' => $this->normalizeTime((string) $empresa->horario_abertura),
                'horario_fechamento' => $this->normalizeTime((string) $empresa->horario_fechamento),
                'intervalo_agenda_minutos' => (int) $empresa->intervalo_agenda_minutos,
                'id_barbeiro' => $idBarbeiro,
            ],
            'slots' => $grade,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAgendamento(AgendamentoModel $agendamento): array
    {
        return [
            'id' => $agendamento->id,
            'id_empresa' => $agendamento->id_empresa,
            'data' => $agendamento->data?->format('Y-m-d'),
            'hora' => $this->normalizeTime((string) $agendamento->hora),
            'id_cliente' => $agendamento->id_cliente,
            'id_assistente' => $agendamento->id_assistente,
            'id_barbeiro' => $agendamento->id_barbeiro,
            'id_servico' => $agendamento->id_servico,
            'observacoes' => $agendamento->observacoes,
            'status_agenda' => $agendamento->status_agenda,
            'status' => $agendamento->status,
            'cliente' => $agendamento->cliente ? [
                'id' => $agendamento->cliente->id,
                'nome' => $agendamento->cliente->nome,
            ] : null,
            'assistente' => $agendamento->assistente ? [
                'id' => $agendamento->assistente->id,
                'nome' => $agendamento->assistente->nome,
            ] : null,
            'barbeiro' => $agendamento->barbeiro ? [
                'id' => $agendamento->barbeiro->id,
                'nome' => $agendamento->barbeiro->nome,
            ] : null,
            'servico' => $agendamento->servico ? [
                'id' => $agendamento->servico->id,
                'nome' => $agendamento->servico->nome,
                'duracao_minutos' => $agendamento->servico->duracao_minutos,
            ] : null,
        ];
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        if (preg_match('/^\d{2}:\d{2}/', $time) === 1) {
            return substr($time, 0, 5);
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return $time;
        }
    }
}
