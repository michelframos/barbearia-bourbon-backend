<?php

namespace App\Modules\Barbeiros\Http\Requests;

use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBarbeiroRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'status_operacional' => [
                'sometimes',
                'string',
                Rule::in(['disponivel', 'em_atendimento', 'offline', 'aguardando']),
            ],
            'hora_entrada' => ['nullable', 'date_format:H:i'],
            'status' => ['sometimes', 'string', Rule::in(['a', 'i'])],
            'servico_ids' => ['sometimes', 'array'],
            'servico_ids.*' => [
                'integer',
                Rule::exists('servicos', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)->whereNull('deleted_at')
                ),
            ],
        ];
    }
}
