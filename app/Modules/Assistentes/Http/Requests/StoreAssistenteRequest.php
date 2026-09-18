<?php

namespace App\Modules\Assistentes\Http\Requests;

use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssistenteRequest extends FormRequest
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
            'turno' => ['nullable', 'string', 'max:20'],
            'ordem_sequencial' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'hora_checkin' => ['sometimes', 'nullable', 'date'],
            'status_operacional' => ['sometimes', 'string', Rule::in(['livre', 'exclusiva', 'offline'])],
            'status' => ['sometimes', 'string', Rule::in(['a', 'i'])],
            'vinculos_barbeiro_dia' => ['sometimes', 'array'],
            'vinculos_barbeiro_dia.*.id_barbeiro' => [
                'required_with:vinculos_barbeiro_dia',
                'integer',
                Rule::exists('barbeiros', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)
                ),
            ],
            'vinculos_barbeiro_dia.*.dia_semana' => [
                'required_with:vinculos_barbeiro_dia',
                'integer',
                'min:0',
                'max:6',
            ],
        ];
    }
}
