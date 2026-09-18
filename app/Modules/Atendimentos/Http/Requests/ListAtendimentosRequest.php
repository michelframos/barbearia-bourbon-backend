<?php

namespace App\Modules\Atendimentos\Http\Requests;

use App\Modules\Atendimentos\Models\AtendimentoModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAtendimentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'date_format:Y-m-d'],
            'status_atendimento' => [
                'nullable',
                'string',
                Rule::in([
                    AtendimentoModel::STATUS_AGUARDANDO,
                    AtendimentoModel::STATUS_CHAMADO,
                    AtendimentoModel::STATUS_EM_ATENDIMENTO,
                    AtendimentoModel::STATUS_CONCLUIDO,
                    AtendimentoModel::STATUS_CANCELADO,
                ]),
            ],
        ];
    }
}
