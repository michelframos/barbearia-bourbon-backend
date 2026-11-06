<?php

namespace App\Modules\Trinks\UseCases;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Core\Models\Empresa;
use App\Modules\Trinks\Services\TrinksHttpClient;
use App\Modules\Trinks\Services\TrinksVinculoService;
use Illuminate\Validation\ValidationException;

class ExportarAgendamentosLocaisUseCase
{
    public function __construct(private readonly EnviarAgendamentoParaTrinksUseCase $enviar) {}

    /**
     * @return array{enviados: int, erros: array<int, string>}
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
        $idEmpresa = (int) $empresa->id;

        $agendamentos = AgendamentoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->whereNull('trinks_agendamento_id')
            ->whereIn('status_agenda', [
                AgendamentoModel::STATUS_AGENDADO,
                AgendamentoModel::STATUS_CONFIRMADO,
                AgendamentoModel::STATUS_EM_ATENDIMENTO,
            ])
            ->orderBy('data')
            ->orderBy('hora')
            ->get();

        $enviados = 0;
        $erros = [];

        foreach ($agendamentos as $agendamento) {
            try {
                $this->enviar->enviarComCliente(
                    $empresa,
                    $agendamento,
                    EnviarAgendamentoParaTrinksUseCase::ACAO_SALVAR,
                    $client,
                    $vinculo,
                );
                $enviados++;
            } catch (ValidationException $exception) {
                $mensagem = collect($exception->errors())->flatten()->first();
                $erros[] = 'Agendamento '.$agendamento->id.': '.(is_string($mensagem) ? $mensagem : 'Falha ao enviar.');
            }
        }

        return [
            'enviados' => $enviados,
            'erros' => $erros,
        ];
    }
}
