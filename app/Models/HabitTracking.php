<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['habito_id', 'fecha_registro', 'estado_cumplimiento', 'notas_opcionales', 'registrado_por_sistema'])]
class HabitTracking extends Model
{
    protected $table = 'habit_tracking';

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class, 'habito_id');
    }

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'date',
            'registrado_por_sistema' => 'boolean',
        ];
    }
}
