<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['habito_id', 'tipo', 'dias_semana', 'dia_mes'])]
class HabitSchedule extends Model
{
    protected $table = 'habit_schedule';

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class, 'habito_id');
    }

    protected function casts(): array
    {
        return [
            'dias_semana' => 'array',
            'dia_mes' => 'integer',
        ];
    }
}
