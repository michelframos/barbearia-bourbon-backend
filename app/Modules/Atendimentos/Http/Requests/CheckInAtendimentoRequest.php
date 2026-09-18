<?php

namespace App\Modules\Atendimentos\Http\Requests;

use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInAtendimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idEmpresa = EmpresaContext::id();

        return [
            'data' => ['sometimes', 'date_format:Y-m-d'],
            'hora_chegada' => ['sometimes', 'date_format:H:i'],
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
            'id_servico' => [
                'required',
                'integer',
                Rule::exists('servicos', 'id')->where(
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
            'id_agendamento' => [
                'nullable',
                'integer',
                Rule::exists('agendamentos', 'id')->where(
                    fn ($query) => $query->where('id_empresa', $idEmpresa)->whereNull('deleted_at')
                ),
            ],
            'posicao_fila' => [
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $max = app(\App\Modules\Core\Services\EmpresaConfigService::class)
                        ->maxAtendimentosSimultaneosAssistente();

                    if ((int) $value > $max) {
                        $fail("A posição da fila não pode ser maior que {$max}.");
                    }
                },
            ],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
