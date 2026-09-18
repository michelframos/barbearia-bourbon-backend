<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:255'],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'endereco' => ['sometimes', 'nullable', 'string', 'max:255'],
            'horario_abertura' => ['sometimes', 'date_format:H:i'],
            'horario_fechamento' => ['sometimes', 'date_format:H:i'],
            'intervalo_agenda_minutos' => ['sometimes', 'integer', 'min:5', 'max:120'],
            'painel_tv_auto_refresh' => ['sometimes', 'boolean'],
            'painel_tv_intervalo_segundos' => ['sometimes', 'integer', 'min:10', 'max:300'],
            'notificacoes_ativas' => ['sometimes', 'boolean'],
            'som_alerta_ativo' => ['sometimes', 'boolean'],
            'modo_selecao_assistente' => [
                'sometimes',
                'string',
                Rule::in([
                    Empresa::MODO_SELECAO_MANUAL,
                    Empresa::MODO_SELECAO_AUTOMATICO_SEQUENCIAL,
                ]),
            ],
            'max_atendimentos_simultaneos_assistente' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'fonte_ordem_assistente' => [
                'sometimes',
                'string',
                Rule::in([
                    Empresa::FONTE_ORDEM_MANUAL,
                    Empresa::FONTE_ORDEM_CHECKIN,
                ]),
            ],
            'reset_cursor_assistente' => [
                'sometimes',
                'string',
                Rule::in([
                    Empresa::RESET_CURSOR_MEIA_NOITE,
                    Empresa::RESET_CURSOR_PRIMEIRO_CHECKIN,
                ]),
            ],
            'momento_atribuicao_assistente' => [
                'sometimes',
                'string',
                Rule::in([
                    Empresa::MOMENTO_ATRIBUICAO_INICIAR,
                    Empresa::MOMENTO_ATRIBUICAO_CHECKIN_CLIENTE,
                ]),
            ],
        ];
    }
}
