<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Habit;
use App\Models\HabitTracking;
use App\Services\HabitStatisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the main habit dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today($user->timezone);

        // Fetch all active habits that started on or before today
        $activeHabits = $user->habits()
            ->with(['category', 'schedule', 'tracking'])
            ->where('estado', true)
            ->whereDate('fecha_inicio', '<=', $today->toDateString())
            ->get();

        $statisticsService = app(HabitStatisticsService::class);

        // Filter habits that are scheduled for today
        $todaysHabits = $activeHabits->filter(function ($habit) use ($statisticsService, $today) {
            return $statisticsService->isScheduledFor($habit, $today);
        });

        // Attach today's tracking status and current streak
        foreach ($todaysHabits as $habit) {
            $habit->today_tracking = $habit->tracking->first(function ($t) use ($today) {
                return $t->fecha_registro->isSameDay($today);
            });
            $habit->current_streak = $statisticsService->currentStreak($habit);
        }

        // Calculate summary counters
        $completedToday = $todaysHabits->filter(function ($h) {
            return $h->today_tracking && $h->today_tracking->estado_cumplimiento === 'completado';
        })->count();

        // Calculate a weekly general completion rate (past 7 days)
        $sevenDaysAgo = Carbon::today($user->timezone)->subDays(6);
        $recentTrackings = HabitTracking::whereIn('habito_id', $user->habits()->pluck('id'))
            ->whereDate('fecha_registro', '>=', $sevenDaysAgo->toDateString())
            ->get();
        $totalRecent = $recentTrackings->count();
        $completedRecent = $recentTrackings->where('estado_cumplimiento', 'completado')->count();
        $weeklyComplianceRate = $totalRecent === 0 ? 0 : round(($completedRecent / $totalRecent) * 100);

        // Calculate global current streak (max of all streaks)
        $globalStreak = 0;
        foreach ($user->habits()->where('estado', true)->get() as $habit) {
            $globalStreak = max($globalStreak, $statisticsService->currentStreak($habit));
        }

        return view('dashboard', [
            'todaysHabits' => $todaysHabits,
            'completedToday' => $completedToday,
            'weeklyComplianceRate' => $weeklyComplianceRate,
            'globalStreak' => $globalStreak,
            'todayDateName' => $today->translatedFormat('l d \d\e F'),
        ]);
    }

    /**
     * Show the habit creation form.
     */
    public function createHabit(Request $request): View
    {
        $categories = Category::all();
        $user = $request->user();

        // Check if plan limit is reached (only relevant for free users)
        $limitReached = !$user->isPremium() && $user->habits()->where('estado', true)->count() >= 5;

        return view('habits.create', [
            'categories' => $categories,
            'limitReached' => $limitReached,
        ]);
    }

    /**
     * Show the statistics page.
     */
    public function statistics(Request $request): View
    {
        $user = $request->user();
        $habits = $user->habits()->with(['category', 'schedule'])->get();
        $statisticsService = app(HabitStatisticsService::class);

        $selectedHabit = null;
        $stats = null;

        if ($habits->isNotEmpty()) {
            $habitId = $request->query('habit_id', $habits->first()->id);
            $selectedHabit = $habits->firstWhere('id', $habitId);
            if ($selectedHabit && $selectedHabit->usuario_id === $user->id) {
                $stats = $statisticsService->forHabit($selectedHabit);
            }
        }

        // Calculate compliance by category
        // Free user = last 7 days range, Premium = full range
        $categories = Category::all();
        $categoryStats = [];

        foreach ($categories as $cat) {
            $catHabitIds = $user->habits()
                ->where('categoria_id', $cat->id)
                ->pluck('id');

            if ($catHabitIds->isEmpty()) {
                continue;
            }

            $query = HabitTracking::whereIn('habito_id', $catHabitIds);

            if (!$user->isPremium()) {
                $sevenDaysAgo = Carbon::today($user->timezone)->subDays(6);
                $query->whereDate('fecha_registro', '>=', $sevenDaysAgo->toDateString());
            }

            $trackings = $query->get();
            $total = $trackings->count();
            $completed = $trackings->where('estado_cumplimiento', 'completado')->count();
            $rate = $total === 0 ? 0 : round(($completed / $total) * 100);

            $categoryStats[] = [
                'category' => $cat,
                'compliance_rate' => $rate,
            ];
        }

        return view('habits.statistics', [
            'habits' => $habits,
            'selectedHabit' => $selectedHabit,
            'stats' => $stats,
            'categoryStats' => $categoryStats,
        ]);
    }

    /**
     * Show the premium simulation page.
     */
    public function premium(Request $request): View
    {
        return view('premium', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Toggle the premium status of the user (simulation).
     */
    public function togglePremium(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isPremium()) {
            // Degrade to free plan
            $user->update(['plan_expires_at' => null]);
            $status = 'Plan premium desactivado. Ahora eres usuario del plan gratuito.';
        } else {
            // Activate premium trial for 14 days
            $user->update(['plan_expires_at' => now()->addDays(14)]);
            $status = 'Plan premium activado por 14 días (Trial académico)!';
        }

        return redirect()->route('premium.view')->with('status', $status);
    }
}
