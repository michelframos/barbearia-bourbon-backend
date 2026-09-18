<?php

namespace App\Modules\Barbeiros\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBarbeiroFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Selecione uma foto.',
            'foto.image' => 'O arquivo deve ser uma imagem.',
            'foto.mimes' => 'Use JPEG, PNG ou WebP.',
            'foto.max' => 'A foto deve ter no máximo 2 MB.',
        ];
    }
}
