<?php

namespace App\Http\Controllers;

use App\Http\Requests\HabitRequest;
use App\Models\Habit;
use App\Services\HabitStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HabitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $habits = $request->user()
            ->habits()
            ->with(['category', 'schedule'])
            ->latest()
            ->get();

        return response()->json($habits);
    }

    public function store(HabitRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isPremium() && $user->habits()->where('estado', true)->count() >= 5) {
            return response()->json([
                'error' => 'plan_limit_reached',
                'message' => 'Alcanzaste el limite de habitos de tu plan gratuito.',
                'action' => 'upgrade',
            ], 422);
        }

        $habit = DB::transaction(function () use ($request, $user) {
            $habit = $user->habits()->create($request->safe()->except('schedule'));
            $habit->schedule()->create($request->validated('schedule'));

            return $habit->load(['category', 'schedule']);
        });

        return response()->json($habit, 201);
    }

    public function show(Request $request, Habit $habit): JsonResponse
    {
        $this->authorizeHabit($request, $habit);

        return response()->json($habit->load(['category', 'schedule', 'tracking']));
    }

    public function destroy(Request $request, Habit $habit): JsonResponse
    {
        $this->authorizeHabit($request, $habit);
        $habit->delete();

        return response()->json(status: 204);
    }

    public function statistics(Request $request, Habit $habit, HabitStatisticsService $statistics): JsonResponse
    {
        $this->authorizeHabit($request, $habit);

        return response()->json($statistics->forHabit($habit));
    }

    private function authorizeHabit(Request $request, Habit $habit): void
    {
        abort_if($habit->usuario_id !== $request->user()->id, 403);
    }
}
