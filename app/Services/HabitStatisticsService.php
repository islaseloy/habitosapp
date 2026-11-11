<?php

namespace App\Services;

use App\Models\Habit;
use App\Models\HabitTracking;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class HabitStatisticsService
{
    public function forHabit(Habit $habit): array
    {
        $habit->loadMissing(['tracking', 'schedule', 'user']);

        $startDate = $habit->user->isPremium()
            ? $habit->fecha_inicio
            : Carbon::today($habit->user->timezone)->subDays(6);

        $records = $habit->tracking()
            ->whereDate('fecha_registro', '>=', $startDate)
            ->orderBy('fecha_registro')
            ->get();

        $completed = $records->where('estado_cumplimiento', 'completado')->count();
        $total = $records->count();

        return [
            'habit_id' => $habit->id,
            'range_start' => $startDate->toDateString(),
            'range_end' => Carbon::today($habit->user->timezone)->toDateString(),
            'completion_rate' => $total === 0 ? 0 : round(($completed / $total) * 100, 2),
            'current_streak' => $this->currentStreak($habit),
            'max_streak' => $this->maxStreak($habit),
            'records' => $records,
        ];
    }

    public function currentStreak(Habit $habit): int
    {
        $streak = 0;

        foreach ($this->scheduledOccurrences($habit)->reverse() as $date) {
            $record = $habit->tracking
                ->first(fn ($item) => $item->fecha_registro->isSameDay($date));

            if ($record?->estado_cumplimiento === 'completado') {
                $streak++;

                continue;
            }

            break;
        }

        return $streak;
    }

    public function maxStreak(Habit $habit): int
    {
        $max = 0;
        $current = 0;

        foreach ($this->scheduledOccurrences($habit) as $date) {
            $record = $habit->tracking
                ->first(fn ($item) => $item->fecha_registro->isSameDay($date));

            if ($record?->estado_cumplimiento === 'completado') {
                $current++;
                $max = max($max, $current);

                continue;
            }

            $current = 0;
        }

        return $max;
    }

    private function scheduledOccurrences(Habit $habit)
    {
        $today = Carbon::today($habit->user->timezone);
        $period = CarbonPeriod::create($habit->fecha_inicio, $today);

        return collect($period)
            ->filter(fn (\Carbon\Carbon $date) => $this->isScheduledFor($habit, $date))
            ->values();
    }

    public function isScheduledFor(Habit $habit, \Carbon\Carbon $date): bool
    {
        $schedule = $habit->schedule;

        return match ($schedule?->tipo) {
            'diario' => true,
            'semanal' => in_array($date->dayOfWeekIso, $schedule->dias_semana ?? [], true),
            'mensual' => $date->day === $schedule->dia_mes,
            default => false,
        };
    }

    /**
     * Calculate summary dashboard metrics (weekly compliance rate and max current streak).
     */
    public function dashboardSummary(User $user): array
    {
        $sevenDaysAgo = Carbon::today($user->timezone)->subDays(6);
        $habitIds = $user->habits()->pluck('id');

        $recentTrackings = HabitTracking::query()
            ->whereIn('habito_id', $habitIds)
            ->whereDate('fecha_registro', '>=', $sevenDaysAgo->toDateString())
            ->get();

        $totalRecent = $recentTrackings->count();
        $completedRecent = $recentTrackings->where('estado_cumplimiento', 'completado')->count();
        $weeklyComplianceRate = $totalRecent === 0 ? 0 : (int) round(($completedRecent / $totalRecent) * 100);

        $activeHabits = $user->habits()
            ->with(['schedule', 'tracking', 'user'])
            ->where('estado', true)
            ->get();

        $globalStreak = 0;
        foreach ($activeHabits as $activeHabit) {
            $globalStreak = max($globalStreak, $this->currentStreak($activeHabit));
        }

        return [
            'weekly_compliance_rate' => $weeklyComplianceRate,
            'global_streak' => $globalStreak,
        ];
    }
}
