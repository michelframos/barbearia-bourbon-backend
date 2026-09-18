<?php

namespace App\Modules\Atendimentos\Strategies;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Atendimentos\Contracts\StatusAtendimentoStrategyInterface;
use App\Modules\Atendimentos\Models\AtendimentoModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Core\Services\EmpresaConfigService;
use Illuminate\Validation\ValidationException;

class FluxoStatusAtendimentoStrategy implements StatusAtendimentoStrategyInterface
{
    /** @var array<string, list<string>> */
    private const TRANSICOES = [
        AtendimentoModel::STATUS_AGUARDANDO => [
            AtendimentoModel::STATUS_CHAMADO,
            AtendimentoModel::STATUS_EM_ATENDIMENTO,
            AtendimentoModel::STATUS_CANCELADO,
        ],
        AtendimentoModel::STATUS_CHAMADO => [
            AtendimentoModel::STATUS_EM_ATENDIMENTO,
            AtendimentoModel::STATUS_CANCELADO,
        ],
        AtendimentoModel::STATUS_EM_ATENDIMENTO => [
            AtendimentoModel::STATUS_CONCLUIDO,
            AtendimentoModel::STATUS_CANCELADO,
        ],
        AtendimentoModel::STATUS_CONCLUIDO => [],
        AtendimentoModel::STATUS_CANCELADO => [],
    ];

    public function __construct(
        private readonly EmpresaConfigService $empresaConfig,
    ) {}

    public function assertPodeTransicionar(string $de, string $para): void
    {
        $permitidos = self::TRANSICOES[$de] ?? [];

        if (! in_array($para, $permitidos, true)) {
            throw ValidationException::withMessages([
                'status_atendimento' => ["Transição inválida: {$de} → {$para}."],
            ]);
        }
    }

    public function proximaPosicaoFila(int $idEmpresa, int $idAssistente, string $data, ?int $maxCapacidade = null): int
    {
        $max = $maxCapacidade ?? $this->empresaConfig->maxAtendimentosSimultaneosAssistente();

        $ocupadas = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_assistente', $idAssistente)
            ->whereDate('data', $data)
            ->whereIn('status_atendimento', AtendimentoModel::STATUS_ABERTOS)
            ->pluck('posicao_fila')
            ->map(fn ($p) => (int) $p)
            ->all();

        for ($posicao = 1; $posicao <= $max; $posicao++) {
            if (! in_array($posicao, $ocupadas, true)) {
                return $posicao;
            }
        }

        throw ValidationException::withMessages([
            'id_assistente' => ["Esta assistente já possui {$max} clientes na fila."],
        ]);
    }

    public function sincronizarOperacional(AtendimentoModel $atendimento): void
    {
        $idEmpresa = (int) $atendimento->id_empresa;
        $data = $atendimento->data?->format('Y-m-d') ?? now()->toDateString();

        if ($atendimento->id_assistente) {
            $this->syncAssistente($idEmpresa, (int) $atendimento->id_assistente, $data);
        }

        if ($atendimento->id_barbeiro) {
            $this->syncBarbeiro($idEmpresa, (int) $atendimento->id_barbeiro, $data);
        }
    }

    private function syncAssistente(int $idEmpresa, int $idAssistente, string $data): void
    {
        $assistente = AssistenteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $idAssistente)
            ->first();

        if (! $assistente || $assistente->status_operacional === 'offline') {
            return;
        }

        $abertos = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_assistente', $idAssistente)
            ->whereDate('data', $data)
            ->whereIn('status_atendimento', AtendimentoModel::STATUS_ABERTOS)
            ->count();

        $assistente->update([
            'status_operacional' => $abertos > 0 ? 'exclusiva' : 'livre',
        ]);
    }

    private function syncBarbeiro(int $idEmpresa, int $idBarbeiro, string $data): void
    {
        $barbeiro = BarbeiroModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id', $idBarbeiro)
            ->first();

        if (! $barbeiro || $barbeiro->status_operacional === 'offline') {
            return;
        }

        $emAtendimento = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_barbeiro', $idBarbeiro)
            ->whereDate('data', $data)
            ->where('status_atendimento', AtendimentoModel::STATUS_EM_ATENDIMENTO)
            ->exists();

        if ($emAtendimento) {
            $barbeiro->update(['status_operacional' => 'em_atendimento']);

            return;
        }

        $aguardando = AtendimentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('id_barbeiro', $idBarbeiro)
            ->whereDate('data', $data)
            ->whereIn('status_atendimento', [
                AtendimentoModel::STATUS_AGUARDANDO,
                AtendimentoModel::STATUS_CHAMADO,
            ])
            ->exists();

        $barbeiro->update([
            'status_operacional' => $aguardando ? 'aguardando' : 'disponivel',
        ]);
    }
}
