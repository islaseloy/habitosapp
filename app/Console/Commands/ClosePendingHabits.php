<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\HabitTracking;
use App\Services\HabitStatisticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ClosePendingHabits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'habits:close-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closes all pending habits for users whose local time has passed midnight, marking them as omitted by the system.';

    /**
     * Execute the console command.
     */
    public function handle(HabitStatisticsService $statisticsService): int
    {
        $users = User::all();
        $processedCount = 0;

        foreach ($users as $user) {
            $userTz = $user->timezone;
            $nowInUserTz = Carbon::now($userTz);

            // Run only if it is the midnight hour (00:00 to 00:59) in the user's timezone
            if ($nowInUserTz->hour === 0) {
                // The day to close is the calendar day that just ended (yesterday)
                $yesterday = Carbon::yesterday($userTz);
                $yesterdayStr = $yesterday->toDateString();

                // Get all active habits for this user that started on or before yesterday
                $activeHabits = $user->habits()
                    ->where('estado', true)
                    ->whereDate('fecha_inicio', '<=', $yesterdayStr)
                    ->get();

                foreach ($activeHabits as $habit) {
                    // Check if the habit is scheduled for yesterday
                    if ($statisticsService->isScheduledFor($habit, $yesterday)) {
                        // Check if it already has a tracking entry for yesterday
                        $alreadyTracked = $habit->tracking()
                            ->whereDate('fecha_registro', $yesterdayStr)
                            ->exists();

                        if (!$alreadyTracked) {
                            $habit->tracking()->create([
                                'fecha_registro' => $yesterdayStr,
                                'estado_cumplimiento' => 'omitido',
                                'registrado_por_sistema' => true,
                            ]);
                            $processedCount++;
                        }
                    }
                }
            }
        }

        $this->info("Successfully closed {$processedCount} pending habit occurrences.");

        return 0;
    }
}
