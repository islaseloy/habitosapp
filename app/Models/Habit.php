<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['usuario_id', 'categoria_id', 'titulo', 'descripcion', 'estado', 'fecha_inicio'])]
class Habit extends Model
{
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(HabitSchedule::class, 'habito_id');
    }

    public function tracking(): HasMany
    {
        return $this->hasMany(HabitTracking::class, 'habito_id');
    }

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_inicio' => 'date',
        ];
    }
}
