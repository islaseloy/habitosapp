<?php

namespace App\Http\Controllers;

use App\Http\Requests\HabitTrackingRequest;
use App\Models\Habit;
use App\Models\HabitTracking;
use App\Services\HabitStatisticsService;
use Illuminate\Http\JsonResponse;

class HabitTrackingController extends Controller
{
    public function store(HabitTrackingRequest $request, HabitStatisticsService $statistics): JsonResponse
    {
        $habit = Habit::query()->findOrFail($request->validated('habito_id'));
        abort_if($habit->usuario_id !== $request->user()->id, 403);

        $fecha = \Illuminate\Support\Carbon::parse($request->validated('fecha_registro'))->startOfDay();

        $tracking = HabitTracking::query()->updateOrCreate(
            [
                'habito_id' => $habit->id,
                'fecha_registro' => $fecha,
            ],
            [
                'estado_cumplimiento' => $request->validated('estado_cumplimiento'),
                'notas_opcionales' => $request->validated('notas_opcionales'),
                'registrado_por_sistema' => false,
            ],
        );

        $summary = $statistics->dashboardSummary($request->user());
        $habitStreak = $summary['habit_streaks'][$habit->id]
            ?? $statistics->currentStreak($habit->load(['schedule', 'tracking', 'user']));

        return response()->json(array_merge($tracking->toArray(), [
            'habit_streak' => $habitStreak,
            'global_streak' => $summary['global_streak'],
            'weekly_compliance_rate' => $summary['weekly_compliance_rate'],
        ]), $tracking->wasRecentlyCreated ? 201 : 200);
    }
}
