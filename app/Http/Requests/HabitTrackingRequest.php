<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HabitTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'habito_id' => ['required', 'integer', 'exists:habits,id'],
            'fecha_registro' => ['required', 'date', 'before_or_equal:today'],
            'estado_cumplimiento' => ['required', Rule::in(['completado', 'fallado', 'omitido'])],
            'notas_opcionales' => ['nullable', 'string'],
        ];
    }
}
