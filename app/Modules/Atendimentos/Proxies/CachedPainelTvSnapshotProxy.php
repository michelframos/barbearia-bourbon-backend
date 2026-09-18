<?php

namespace App\Modules\Atendimentos\Proxies;

use App\Modules\Atendimentos\Contracts\PainelTvSnapshotProxyInterface;
use App\Modules\Atendimentos\UseCases\MontarPainelOperacionalUseCase;
use App\Modules\Core\Services\EmpresaConfigService;
use Illuminate\Support\Facades\Cache;

class CachedPainelTvSnapshotProxy implements PainelTvSnapshotProxyInterface
{
    public function __construct(
        private readonly MontarPainelOperacionalUseCase $painelUseCase,
        private readonly EmpresaConfigService $empresaConfig,
    ) {}

    public function obterSnapshot(int $idEmpresa, string $data): array
    {
        $empresa = $this->empresaConfig->getEmpresa();
        $intervalo = max(10, (int) ($empresa->painel_tv_intervalo_segundos ?? 30));

        if (! $empresa->painel_tv_auto_refresh) {
            return $this->montarPayload($idEmpresa, $data, $intervalo, false);
        }

        $cacheKey = $this->cacheKey($idEmpresa, $data);

        /** @var array<string, mixed> $cached */
        $cached = Cache::remember(
            $cacheKey,
            $intervalo,
            fn () => $this->montarPayload($idEmpresa, $data, $intervalo, true),
        );

        return $cached;
    }

    public function invalidar(int $idEmpresa, string $data): void
    {
        Cache::forget($this->cacheKey($idEmpresa, $data));
    }

    private function cacheKey(int $idEmpresa, string $data): string
    {
        return "painel_tv_snapshot:{$idEmpresa}:{$data}";
    }

    /**
     * @return array<string, mixed>
     */
    private function montarPayload(
        int $idEmpresa,
        string $data,
        int $intervaloSegundos,
        bool $fromCache,
    ): array {
        $snapshot = $this->painelUseCase->execute($idEmpresa, $data);

        return [
            ...$snapshot,
            'meta' => [
                'cached' => $fromCache,
                'refresh_interval_segundos' => $intervaloSegundos,
                'gerado_em' => now()->toIso8601String(),
            ],
        ];
    }
}
