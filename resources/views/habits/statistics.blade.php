<x-app-layout>
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-habits-ink">Estadísticas y Consistencia</h1>
        <p class="text-sm text-habits-muted font-medium mt-1">
            Visualiza tus rachas y evolución.
            @if(Auth::user()->isPremium())
                <span class="text-habits-green-dark font-semibold">★ Acceso Premium: Historial completo habilitado.</span>
            @else
                <span class="text-habits-muted">Plan Gratuito: Historial limitado a los últimos 7 días.</span>
            @endif
        </p>
    </div>

    @if($habits->isEmpty())
        <!-- Empty State if no habits exist -->
        <div class="bg-habits-surface rounded-2xl border border-habits-line shadow-xs p-12 text-center">
            <div class="w-16 h-16 bg-[#f2f8fb] text-[#4b9aaa] rounded-full flex items-center justify-center text-2xl mb-4 mx-auto border border-[#d2e7f0]">
                ◫
            </div>
            <h3 class="text-base font-bold text-habits-ink">No hay datos de estadísticas</h3>
            <p class="text-xs text-habits-muted max-w-sm mt-2 mb-6 mx-auto">Para ver estadísticas de racha y porcentajes de cumplimiento primero debes crear al menos un hábito.</p>
            <a href="{{ route('habits.create') }}" class="inline-block py-2.5 px-4 bg-habits-green text-white text-xs font-bold rounded-lg shadow-sm hover:bg-habits-green-dark transition-all duration-200">
                ＋ Crear primer hábito
            </a>
        </div>
    @else
        <!-- Grid layout: left column is habit list, right is statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left panel: habits list -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Habits list -->
                <div class="bg-habits-surface rounded-2xl border border-habits-line shadow-xs p-5">
                    <h3 class="text-xs font-bold text-habits-ink uppercase tracking-wider mb-4">Seleccionar Hábito</h3>
                    <div class="space-y-2">
                        @foreach($habits as $h)
                            <a href="?habit_id={{ $h->id }}" 
                               class="block p-4 border rounded-xl transition-all duration-200 text-left select-none {{ $selectedHabit && $selectedHabit->id === $h->id ? 'border-habits-green bg-habits-surface-soft text-habits-green-dark shadow-2xs font-semibold' : 'border-habits-line hover:bg-habits-hover text-habits-muted hover:text-habits-ink' }}">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-bold tracking-tight truncate block">{{ $h->titulo }}</span>
                                    <span class="text-3xs uppercase px-1.5 py-0.5 rounded-md" style="background-color: {{ $h->category ? $h->category->color_icono : '#e6e3da' }}15; color: {{ $h->category ? $h->category->color_icono : '#6f7b73' }}">
                                        {{ $h->category ? $h->category->nombre : 'General' }}
                                    </span>
                                </div>
                                <span class="text-3xs block opacity-80">{{ ucfirst($h->schedule->tipo) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Categories Breakdown -->
                <div class="bg-habits-surface rounded-2xl border border-habits-line shadow-xs p-5">
                    <h3 class="text-xs font-bold text-habits-ink uppercase tracking-wider mb-4">Cumplimiento por Categoría</h3>
                    @if(empty($categoryStats))
                        <span class="text-3xs text-habits-muted font-semibold block">Sin datos de categorías en el rango actual.</span>
                    @else
                        <div class="space-y-4">
                            @foreach($categoryStats as $cStat)
                                @php
                                    $catColor = $cStat['category']->color_icono;
                                @endphp
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span class="text-habits-ink">{{ $cStat['category']->nombre }}</span>
                                        <span style="color: {{ $catColor }}">{{ $cStat['compliance_rate'] }}%</span>
                                    </div>
                                    <div class="w-full bg-habits-hover rounded-full h-2.5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500" 
                                             style="width: {{ $cStat['compliance_rate'] }}%; background-color: {{ $catColor }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right panel: metrics and calendar grid -->
            <div class="lg:col-span-2 space-y-6">
                @if($selectedHabit && $stats)
                    <!-- Metrics Summary -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <!-- Current Streak -->
                        <div class="bg-habits-surface border border-habits-line shadow-xs p-5 rounded-2xl text-center">
                            <span class="text-3xs font-bold text-habits-muted uppercase tracking-wider block mb-1">Racha Actual</span>
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="text-3xl font-extrabold text-habits-ink tracking-tight">{{ $stats['current_streak'] }}</span>
                                <span class="text-xl">🔥</span>
                            </div>
                            <span class="text-4xs font-medium text-habits-muted block mt-1">Ocurrencias seguidas</span>
                        </div>

                        <!-- Max Streak -->
                        <div class="bg-habits-surface border border-habits-line shadow-xs p-5 rounded-2xl text-center">
                            <span class="text-3xs font-bold text-habits-muted uppercase tracking-wider block mb-1">Racha Máxima</span>
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="text-3xl font-extrabold text-habits-ink tracking-tight">{{ $stats['max_streak'] }}</span>
                                <span class="text-xl">🏆</span>
                            </div>
                            <span class="text-4xs font-medium text-habits-muted block mt-1">Récord personal</span>
                        </div>

                        <!-- Completion Rate -->
                        <div class="bg-habits-surface border border-habits-line shadow-xs p-5 rounded-2xl text-center">
                            <span class="text-3xs font-bold text-habits-muted uppercase tracking-wider block mb-1">Tasa de Éxito</span>
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="text-3xl font-extrabold text-habits-ink tracking-tight">{{ $stats['completion_rate'] }}%</span>
                                <span class="text-xl">🎯</span>
                            </div>
                            <span class="text-4xs font-medium text-habits-muted block mt-1">En el rango evaluado</span>
                        </div>
                    </div>

                    <!-- Compliance Calendar Grid -->
                    <div class="bg-habits-surface border border-habits-line shadow-xs p-6 md:p-8 rounded-2xl">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-sm font-bold text-habits-ink tracking-tight">Historial de Cumplimiento</h3>
                                <p class="text-4xs text-habits-muted mt-0.5">Desde: {{ Carbon\Carbon::parse($stats['range_start'])->format('d/m/Y') }} hasta hoy.</p>
                            </div>
                            <!-- Legend -->
                            <div class="flex items-center gap-3 text-3xs font-semibold text-habits-muted">
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-[#dff3e5] border border-[#bde2c8]"></span> Hecho</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-[#fae5e1] border border-[#f0c7bf]"></span> Fallado</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-[#fbf0cc] border border-[#ead48a]"></span> Omitido</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-habits-surface border border-habits-line"></span> Vacío</span>
                            </div>
                        </div>

                        @php
                            $startDate = Carbon\Carbon::parse($stats['range_start']);
                            $endDate = Carbon\Carbon::parse($stats['range_end']);
                            $period = Carbon\CarbonPeriod::create($startDate, $endDate);
                            $statisticsService = app(\App\Services\HabitStatisticsService::class);
                            
                            // Build dates scheduled
                            $scheduledDates = [];
                            foreach ($period as $date) {
                                if ($statisticsService->isScheduledFor($selectedHabit, $date)) {
                                    $scheduledDates[] = $date->copy();
                                }
                            }
                        @endphp

                        @if(empty($scheduledDates))
                            <div class="py-12 border border-dashed border-habits-line rounded-xl text-center text-xs text-habits-muted">
                                No hay ocurrencias programadas para el hábito en este rango.
                            </div>
                        @else
                            <!-- Calendar cell grid layout -->
                            <div class="grid grid-cols-4 sm:grid-cols-7 gap-3">
                                @foreach($scheduledDates as $date)
                                    @php
                                        $dateStr = $date->toDateString();
                                        $record = $stats['records']->first(fn($t) => $t->fecha_registro->isSameDay($date));
                                        
                                        $statusClass = 'bg-habits-surface border-habits-line text-habits-muted';
                                        $statusName = 'Vacío';
                                        
                                        if ($record) {
                                            if ($record->estado_cumplimiento === 'completado') {
                                                $statusClass = 'bg-[#dff3e5] text-habits-green-dark border-[#bde2c8]';
                                                $statusName = 'Completado';
                                            } elseif ($record->estado_cumplimiento === 'fallado') {
                                                $statusClass = 'bg-[#fae5e1] text-[#9c3d31] border-[#f0c7bf]';
                                                $statusName = 'Fallado';
                                            } elseif ($record->estado_cumplimiento === 'omitido') {
                                                $statusClass = 'bg-[#fbf0cc] text-[#77550c] border-[#ead48a]';
                                                $statusName = 'Omitido' . ($record->registrado_por_sistema ? ' (Cron)' : '');
                                            }
                                        }
                                    @endphp
                                    <div class="border rounded-lg aspect-square p-2 flex flex-col justify-between shadow-2xs {{ $statusClass }}"
                                         title="{{ $date->translatedFormat('l d \d\e F') }}: {{ $statusName }}">
                                        <span class="text-3xs font-extrabold leading-none">{{ $date->day }}</span>
                                        <span class="text-4xs font-bold opacity-80 text-right leading-none uppercase">{{ $date->translatedFormat('D') }}</span>
                                        
                                        @if($record && $record->notas_opcionales)
                                            <span class="text-4xs italic block truncate mt-1 opacity-70" title="Nota: {{ $record->notas_opcionales }}">
                                                💬
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Upgrade banner block for free users viewing stats -->
                            @if(!Auth::user()->isPremium())
                                <div class="mt-8 p-4 bg-[#fff8e5] border border-[#ead48a] rounded-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div class="text-center sm:text-left">
                                        <strong class="text-xs text-amber-950 font-bold block">¿Quieres ver más de 7 días de historial?</strong>
                                        <span class="text-3xs text-[#8a6a18] font-medium block mt-0.5">El plan premium desbloquea el calendario completo desde la fecha de inicio del hábito.</span>
                                    </div>
                                    <a href="{{ route('premium.view') }}" class="py-1.5 px-4 bg-habits-yellow text-[#5d4309] text-xs font-bold rounded-lg border border-[#ead48a] shadow-sm hover:bg-[#ebd59b] active:scale-95 transition-all duration-200">
                                        ◆ Activar Premium
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
