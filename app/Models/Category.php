<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'color_icono'])]
class Category extends Model
{
    use HasFactory;

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class, 'categoria_id');
    }
}
