<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HabitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['nullable', 'integer', 'exists:categories,id'],
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['sometimes', 'boolean'],
            'fecha_inicio' => ['required', 'date', 'before_or_equal:today'],
            'schedule.tipo' => ['required', Rule::in(['diario', 'semanal', 'mensual'])],
            'schedule.dias_semana' => ['nullable', 'array', 'required_if:schedule.tipo,semanal'],
            'schedule.dias_semana.*' => ['integer', 'between:1,7'],
            'schedule.dia_mes' => ['nullable', 'integer', 'between:1,31', 'required_if:schedule.tipo,mensual'],
        ];
    }
}
