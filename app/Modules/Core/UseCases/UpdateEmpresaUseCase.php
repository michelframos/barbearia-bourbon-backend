<?php

namespace App\Modules\Core\UseCases;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Services\AuditLogService;
use App\Modules\Core\Services\EmpresaConfigService;
use App\Modules\Core\Support\AuditAcao;

class UpdateEmpresaUseCase
{
    public function __construct(
        private readonly EmpresaConfigService $empresaConfig,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Empresa
    {
        if (array_key_exists('trinks_api_key', $data) && ! filled($data['trinks_api_key'])) {
            unset($data['trinks_api_key']);
        }

        $empresa = $this->empresaConfig->getEmpresa();
        $empresa->update($data);
        $fresh = $empresa->fresh();

        $this->auditLog->registrar(
            AuditAcao::CONFIG,
            'empresa',
            (int) $fresh->id,
            'Configurações da barbearia atualizadas.',
            ['campos' => array_keys($data)],
        );

        return $fresh;
    }
}
