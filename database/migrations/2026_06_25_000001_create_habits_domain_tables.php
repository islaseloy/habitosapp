<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('color_icono');
            $table->timestamps();
        });

        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('titulo', 120);
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->date('fecha_inicio');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('habit_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habito_id')->constrained('habits')->cascadeOnDelete();
            $table->enum('tipo', ['diario', 'semanal', 'mensual']);
            $table->json('dias_semana')->nullable();
            $table->unsignedTinyInteger('dia_mes')->nullable();
            $table->timestamps();
        });

        Schema::create('habit_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habito_id')->constrained('habits')->cascadeOnDelete();
            $table->date('fecha_registro');
            $table->enum('estado_cumplimiento', ['completado', 'fallado', 'omitido']);
            $table->text('notas_opcionales')->nullable();
            $table->boolean('registrado_por_sistema')->default(false);
            $table->timestamps();

            $table->unique(['habito_id', 'fecha_registro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_tracking');
        Schema::dropIfExists('habit_schedule');
        Schema::dropIfExists('habits');
        Schema::dropIfExists('categories');
    }
};
