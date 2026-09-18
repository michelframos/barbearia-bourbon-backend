<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $id = EmpresaContext::id();

        Empresa::query()->updateOrCreate(
            ['id' => $id],
            [
                'nome' => 'Bourbon Barbearia',
                'telefone' => '(11) 99999-0000',
                'endereco' => null,
                'horario_abertura' => '08:00:00',
                'horario_fechamento' => '19:00:00',
                'intervalo_agenda_minutos' => 30,
                'painel_tv_auto_refresh' => true,
                'painel_tv_intervalo_segundos' => 30,
                'notificacoes_ativas' => true,
                'som_alerta_ativo' => false,
                'modo_selecao_assistente' => Empresa::MODO_SELECAO_MANUAL,
                'max_atendimentos_simultaneos_assistente' => 2,
                'ultima_assistente_sequencial_id' => null,
                'status' => 'a',
            ]
        );
    }
}
