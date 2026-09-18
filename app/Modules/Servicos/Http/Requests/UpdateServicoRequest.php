<?php

namespace App\Modules\Servicos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_empresa' => ['prohibited'],
            'nome' => ['sometimes', 'string', 'max:255'],
            'duracao_minutos' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'preco' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['a', 'i'])],
        ];
    }
}
