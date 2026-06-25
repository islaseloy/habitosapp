<?php

namespace App\Http\Controllers;

use App\Http\Requests\HabitTrackingRequest;
use App\Models\Habit;
use App\Models\HabitTracking;
use Illuminate\Http\JsonResponse;

class HabitTrackingController extends Controller
{
    public function store(HabitTrackingRequest $request): JsonResponse
    {
        $habit = Habit::query()->findOrFail($request->validated('habito_id'));
        abort_if($habit->usuario_id !== $request->user()->id, 403);

        $tracking = HabitTracking::query()->updateOrCreate(
            [
                'habito_id' => $habit->id,
                'fecha_registro' => $request->validated('fecha_registro'),
            ],
            [
                'estado_cumplimiento' => $request->validated('estado_cumplimiento'),
                'notas_opcionales' => $request->validated('notas_opcionales'),
                'registrado_por_sistema' => false,
            ],
        );

        return response()->json($tracking, $tracking->wasRecentlyCreated ? 201 : 200);
    }
}
