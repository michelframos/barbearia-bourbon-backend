<?php

namespace App\Modules\Clientes\Http\Requests;

use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
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
            'id_assistente_preferida' => [
                'nullable',
                'integer',
                Rule::exists('assistentes', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)->whereNull('deleted_at')
                ),
            ],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'string', Rule::in(['a', 'i'])],
        ];
    }
}
