<?php

namespace App\Modules\Agendamentos\Http\Requests;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idEmpresa = EmpresaContext::id();

        return [
            'id_empresa' => ['sometimes', 'integer', 'exists:empresas,id'],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'id_cliente' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)->whereNull('deleted_at')
                ),
            ],
            'id_assistente' => [
                'nullable',
                'integer',
                Rule::exists('assistentes', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)->whereNull('deleted_at')
                ),
            ],
            'id_barbeiro' => [
                'nullable',
                'integer',
                Rule::exists('barbeiros', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)->whereNull('deleted_at')
                ),
            ],
            'id_servico' => [
                'required',
                'integer',
                Rule::exists('servicos', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)->whereNull('deleted_at')
                ),
            ],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'status_agenda' => [
                'sometimes',
                'string',
                Rule::in([
                    AgendamentoModel::STATUS_AGENDADO,
                    AgendamentoModel::STATUS_CONFIRMADO,
                ]),
            ],
            'status' => ['sometimes', 'string', Rule::in(['a', 'i'])],
        ];
    }
}
