<?php

namespace App\Modules\Atendimentos\Http\Requests;

use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IniciarAtendimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idEmpresa = EmpresaContext::id();

        return [
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
        ];
    }
}
