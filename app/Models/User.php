<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nombre', 'email', 'password', 'timezone', 'plan_expires_at', 'fecha_registro'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class, 'usuario_id');
    }

    public function isPremium(): bool
    {
        return $this->plan_expires_at !== null && $this->plan_expires_at->isFuture();
    }

    protected function timezone(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => filled($value) ? $value : config('app.timezone'),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_expires_at' => 'datetime',
            'fecha_registro' => 'datetime',
        ];
    }
}
