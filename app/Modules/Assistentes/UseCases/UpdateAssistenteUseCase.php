<?php

namespace App\Modules\Assistentes\UseCases;

use App\Modules\Assistentes\Contracts\AssistenteRepositoryInterface;
use App\Modules\Assistentes\DTOs\AssistenteDTO;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Services\EmpresaConfigService;
use App\Modules\Core\Support\AuditAcao;

class UpdateAssistenteUseCase
{
    public function __construct(
        private readonly AssistenteRepositoryInterface $repository,
        private readonly AuditLogService $auditLog,
        private readonly EmpresaConfigService $empresaConfig,
    ) {}

    public function execute(AssistenteModel $model, AssistenteDTO $dto): AssistenteModel
    {
        $statusAnterior = $model->status_operacional;
        $data = $dto->data;
        $statusNovo = $data['status_operacional'] ?? null;

        $fazendoCheckin = $statusNovo !== null
            && $statusNovo !== 'offline'
            && $statusAnterior === 'offline';

        $fazendoCheckout = $statusNovo === 'offline'
            && $statusAnterior !== 'offline';

        if ($fazendoCheckin) {
            $this->empresaConfig->resetCursorNoPrimeiroCheckin();
            if (! array_key_exists('hora_checkin', $data) || blank($data['hora_checkin'])) {
                $data['hora_checkin'] = now();
            }
        }

        if ($fazendoCheckout) {
            $data['hora_checkin'] = null;
        }

        $updated = $this->repository->update($model, AssistenteDTO::fromRequest($data));

        if ($statusNovo !== null && $statusNovo !== $statusAnterior) {
            $acao = $fazendoCheckout ? AuditAcao::CHECK_OUT : (
                $fazendoCheckin ? AuditAcao::CHECK_IN : AuditAcao::STATUS
            );

            $this->auditLog->registrar(
                $acao,
                'assistente',
                (int) $updated->id,
                sprintf(
                    'Status operacional de %s: %s → %s',
                    $updated->nome,
                    $statusAnterior,
                    $statusNovo,
                ),
                [
                    'status_operacional_anterior' => $statusAnterior,
                    'status_operacional_novo' => $statusNovo,
                    'hora_checkin' => $updated->hora_checkin?->toDateTimeString(),
                ],
            );
        }

        return $updated;
    }
}
