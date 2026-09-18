<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registro único da barbearia (tabela empresas — migration na Fase 2).
 */
class Empresa extends Model
{
    use SoftDeletes;

    public const MODO_SELECAO_MANUAL = 'manual';

    public const MODO_SELECAO_AUTOMATICO_SEQUENCIAL = 'automatico_sequencial';

    public const FONTE_ORDEM_MANUAL = 'manual';

    public const FONTE_ORDEM_CHECKIN = 'checkin';

    public const RESET_CURSOR_MEIA_NOITE = 'meia_noite';

    public const RESET_CURSOR_PRIMEIRO_CHECKIN = 'primeiro_checkin_dia';

    public const MOMENTO_ATRIBUICAO_INICIAR = 'iniciar_atendimento';

    public const MOMENTO_ATRIBUICAO_CHECKIN_CLIENTE = 'checkin_cliente';

    protected $table = 'empresas';

    protected $fillable = [
        'nome',
        'telefone',
        'endereco',
        'horario_abertura',
        'horario_fechamento',
        'intervalo_agenda_minutos',
        'painel_tv_auto_refresh',
        'painel_tv_intervalo_segundos',
        'notificacoes_ativas',
        'som_alerta_ativo',
        'modo_selecao_assistente',
        'max_atendimentos_simultaneos_assistente',
        'ultima_assistente_sequencial_id',
        'fonte_ordem_assistente',
        'reset_cursor_assistente',
        'momento_atribuicao_assistente',
        'cursor_sequencial_data',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'painel_tv_auto_refresh' => 'boolean',
            'intervalo_agenda_minutos' => 'integer',
            'painel_tv_intervalo_segundos' => 'integer',
            'notificacoes_ativas' => 'boolean',
            'som_alerta_ativo' => 'boolean',
            'max_atendimentos_simultaneos_assistente' => 'integer',
            'ultima_assistente_sequencial_id' => 'integer',
            'cursor_sequencial_data' => 'date',
        ];
    }
}
