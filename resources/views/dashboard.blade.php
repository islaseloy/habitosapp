<x-app-layout>
    <div x-data="habitTracker('{{ Carbon\Carbon::today(Auth::user()->timezone)->toDateString() }}')">
        <!-- Page Header Summary -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-habits-ink">¡Hola, {{ Auth::user()->nombre }}!</h1>
            <p class="text-sm text-habits-muted font-medium mt-1">Hoy es <span class="capitalize text-habits-ink font-semibold">{{ $todayDateName }}</span></p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('habits.create') }}" class="py-2.5 px-4 bg-habits-green text-white text-xs font-bold rounded-lg shadow-sm hover:bg-habits-green-dark transition-all duration-200 active:scale-95">
                ＋ Crear nuevo hábito
            </a>
        </div>
    </div>

    <!-- Overview Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <!-- Global Streak Card -->
        <div class="bg-habits-surface p-5 rounded-xl border border-habits-line shadow-xs flex items-center justify-between">
            <div>
                <span class="text-3xs font-bold text-habits-muted uppercase tracking-wider block mb-1">Racha Máxima Hoy</span>
                <strong class="text-3xl font-extrabold text-habits-ink tracking-tight block">{{ $globalStreak }}</strong>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#fff7e2] text-[#805f10] flex items-center justify-center text-xl font-bold border border-[#f0dfad]">
                🔥
            </div>
        </div>

        <!-- Weekly Compliance Card -->
        <div class="bg-habits-surface p-5 rounded-xl border border-habits-line shadow-xs flex items-center justify-between">
            <div>
                <span class="text-3xs font-bold text-habits-muted uppercase tracking-wider block mb-1">Cumplimiento (Últimos 7 días)</span>
                <strong class="text-3xl font-extrabold text-habits-ink tracking-tight block">{{ $weeklyComplianceRate }}%</strong>
            </div>
            <div class="w-12 h-12 rounded-full bg-habits-surface-soft text-habits-green-dark flex items-center justify-center text-xl font-bold border border-habits-green/10">
                📈
            </div>
        </div>

        <!-- Habits of Today Summary Card -->
        <div class="bg-habits-surface p-5 rounded-xl border border-habits-line shadow-xs flex items-center justify-between">
            <div>
                <span class="text-3xs font-bold text-habits-muted uppercase tracking-wider block mb-1">Hábitos de Hoy</span>
                <strong class="text-3xl font-extrabold text-habits-ink tracking-tight block"><span x-text="completedCount"></span>/{{ $todaysHabits->count() }}</strong>
            </div>
            <div class="w-12 h-12 rounded-full bg-[#f2f8fb] text-[#4b9aaa] flex items-center justify-center text-xl font-bold border border-[#d2e7f0]">
                📅
            </div>
        </div>
    </div>

    <!-- Habits List Section -->
    <div class="bg-habits-surface rounded-2xl border border-habits-line shadow-xs p-6 md:p-8">
        <h2 class="text-lg font-bold text-habits-ink tracking-tight mb-6">Hábitos programados para hoy</h2>

        @if($todaysHabits->isEmpty())
            <!-- Empty State (TC-07) -->
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-16 h-16 bg-habits-surface-soft border border-habits-green/10 text-habits-green-dark rounded-full flex items-center justify-center text-2xl mb-4">
                    ✓
                </div>
                <h3 class="text-base font-bold text-habits-ink">No tienes hábitos programados para hoy</h3>
                <p class="text-xs text-habits-muted max-w-sm mt-2 mb-6">Buen momento para revisar tus estadísticas de consistencia o crear un nuevo hábito de rutina.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('habits.statistics.view') }}" class="py-2 px-4 bg-habits-surface-soft text-habits-green-dark text-xs font-bold rounded-lg border border-habits-green/20 hover:bg-[#e2f1e8] active:scale-95 transition-all duration-200">
                        ◫ Ir a estadísticas
                    </a>
                    <a href="{{ route('habits.create') }}" class="py-2 px-4 bg-habits-green text-white text-xs font-bold rounded-lg hover:bg-habits-green-dark active:scale-95 transition-all duration-200">
                        ＋ Crear hábito
                    </a>
                </div>
            </div>
        @else
            <!-- Habits List -->
            <div class="space-y-4">
                @foreach($todaysHabits as $habit)
                    @php
                        $status = $habit->today_tracking ? $habit->today_tracking->estado_cumplimiento : null;
                        $notes = $habit->today_tracking ? $habit->today_tracking->notas_opcionales : '';
                        $categoryName = $habit->category ? $habit->category->nombre : 'General';
                        $categoryColor = $habit->category ? $habit->category->color_icono : '#6f7b73';
                    @endphp
                    <div class="p-5 border border-habits-line rounded-xl hover:shadow-xs transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4" id="habit-row-{{ $habit->id }}">
                        <!-- Habit metadata -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                <span class="px-2 py-0.5 text-3xs font-extrabold rounded-md border" style="background-color: {{ $categoryColor }}15; color: {{ $categoryColor }}; border-color: {{ $categoryColor }}25">
                                    {{ $categoryName }}
                                </span>
                                <span class="text-3xs font-bold text-habits-muted">
                                    {{ ucfirst($habit->schedule->tipo) }} 
                                    @if($habit->schedule->tipo === 'semanal')
                                        ({{ collect($habit->schedule->dias_semana)->map(fn($d) => ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'][$d - 1])->join(', ') }})
                                    @elseif($habit->schedule->tipo === 'mensual')
                                        (Día {{ $habit->schedule->dia_mes }})
                                    @endif
                                </span>
                                @if($habit->current_streak > 0)
                                    <span class="px-2 py-0.5 text-3xs font-extrabold bg-[#fff7e2] text-[#805f10] border border-[#f0dfad] rounded-md flex items-center gap-0.5">
                                        🔥 Racha: {{ $habit->current_streak }}
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-sm font-bold text-habits-ink tracking-tight">{{ $habit->titulo }}</h3>
                            @if($habit->descripcion)
                                <p class="text-xs text-habits-muted mt-1 leading-normal max-w-xl">{{ $habit->descripcion }}</p>
                            @endif
                        </div>

                        <!-- Tracking Actions & Notes -->
                        <div class="flex flex-col sm:flex-row md:items-center gap-4">
                            <!-- Button group -->
                            <div class="flex items-center gap-2 border border-habits-line p-1 bg-habits-hover/50 rounded-lg">
                                <button @click="track({{ $habit->id }}, 'completado')" 
                                        :class="getStatusClass({{ $habit->id }}, 'completado')"
                                        class="px-3 py-1.5 text-xs font-bold rounded-md border transition-all duration-200 text-center flex items-center justify-center gap-1 select-none active:scale-95 cursor-pointer"
                                        id="btn-completado-{{ $habit->id }}">
                                    ✓ Completado
                                </button>
                                <button @click="track({{ $habit->id }}, 'fallado')" 
                                        :class="getStatusClass({{ $habit->id }}, 'fallado')"
                                        class="px-3 py-1.5 text-xs font-bold rounded-md border transition-all duration-200 text-center flex items-center justify-center gap-1 select-none active:scale-95 cursor-pointer"
                                        id="btn-fallado-{{ $habit->id }}">
                                    × Fallado
                                </button>
                                <button @click="track({{ $habit->id }}, 'omitido')" 
                                        :class="getStatusClass({{ $habit->id }}, 'omitido')"
                                        class="px-3 py-1.5 text-xs font-bold rounded-md border transition-all duration-200 text-center flex items-center justify-center gap-1 select-none active:scale-95 cursor-pointer"
                                        id="btn-omitido-{{ $habit->id }}">
                                    – Omitido
                                </button>
                            </div>

                            <!-- Notes Trigger -->
                             <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        :class="hasNotes({{ $habit->id }}) ? 'border-habits-green/30 bg-habits-surface-soft text-habits-green-dark' : 'border-habits-line hover:bg-habits-hover text-habits-muted'"
                                        class="w-9 h-9 border rounded-lg flex items-center justify-center text-sm font-semibold transition-all duration-200 active:scale-95 cursor-pointer" 
                                        title="Agregar notas/comentarios">
                                    ✍
                                </button>
                                                                <!-- Notes Dropdown -->
                                 <div x-show="open" @click.outside="open = false" 
                                      class="absolute right-0 bottom-12 sm:bottom-auto sm:top-12 bg-habits-surface border border-habits-line shadow-lg rounded-xl p-4 w-72 z-10 transition-all duration-200" 
                                     style="display: none;">
                                    <h4 class="text-xs font-bold text-habits-ink mb-2">Comentarios del día</h4>
                                    <textarea x-model="notes[{{ $habit->id }}]" 
                                              placeholder="Escribe algo sobre tu progreso de hoy..." 
                                              class="w-full text-xs p-2 border border-habits-line rounded-lg focus:outline-none focus:border-habits-green bg-habits-bg focus:ring-1 focus:ring-habits-green/20 mb-3 h-16 resize-none"></textarea>
                                    <div class="flex justify-between items-center">
                                        <button @click="open = false" class="text-3xs text-habits-muted hover:text-habits-ink font-semibold">Cerrar</button>
                                        <button @click="saveNote({{ $habit->id }}); open = false" class="py-1 px-3 bg-habits-green text-white text-3xs font-bold rounded-md hover:bg-habits-green-dark">Guardar nota</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    </div> <!-- Close AlpineJS x-data wrapper -->

    <!-- AJAX Tracking Logic script -->
    <script>
        function habitTracker(todayStr) {
            return {
                statuses: {
                    @foreach($todaysHabits as $habit)
                        {{ $habit->id }}: '{{ $habit->today_tracking ? $habit->today_tracking->estado_cumplimiento : '' }}',
                    @endforeach
                },
                notes: {
                    @foreach($todaysHabits as $habit)
                        {{ $habit->id }}: '{{ $habit->today_tracking ? str_replace("'", "\\'", $habit->today_tracking->notes_opcionales) : '' }}',
                    @endforeach
                },
                date: todayStr,
                
                get completedCount() {
                    return Object.values(this.statuses).filter(s => s === 'completado').length;
                },
                
                getStatusClass(habitId, targetStatus) {
                    const current = this.statuses[habitId];
                    if (current !== targetStatus) {
                        return 'bg-habits-surface border-habits-line hover:bg-habits-hover text-habits-muted hover:text-habits-ink';
                    }
                    
                    if (targetStatus === 'completado') {
                        return 'bg-[#dff3e5] text-habits-green-dark border-[#bde2c8]';
                    } else if (targetStatus === 'fallado') {
                        return 'bg-[#fae5e1] text-[#9c3d31] border-[#f0c7bf]';
                    } else if (targetStatus === 'omitido') {
                        return 'bg-[#fbf0cc] text-[#77550c] border-[#ead48a]';
                    }
                },
                
                hasNotes(habitId) {
                    return this.notes[habitId] && this.notes[habitId].trim().length > 0;
                },
                
                track(habitId, status) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Optimistic update
                    const prevStatus = this.statuses[habitId];
                    this.statuses[habitId] = status;
                    
                    fetch('/api/habit-tracking', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            habito_id: habitId,
                            fecha_registro: this.date,
                            estado_cumplimiento: status,
                            notas_opcionales: this.notes[habitId]
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al registrar tracking');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Success, we can optionally trigger a recalculation or refresh stats count
                        console.log('Habit tracked successfully:', data);
                    })
                    .catch(error => {
                        console.error('Error tracking habit:', error);
                        // Revert on error
                        this.statuses[habitId] = prevStatus;
                        alert('No se pudo registrar el hábito. Inténtalo de nuevo.');
                    });
                },
                
                saveNote(habitId) {
                    const currentStatus = this.statuses[habitId];
                    if (!currentStatus) {
                        // If no status is checked, default track as completed or omitido?
                        // Usually they add note to a status. Let's not post yet until they select status, or update if exists
                        alert('Por favor selecciona un estado (Completado, Fallado u Omitido) para poder guardar la nota.');
                        return;
                    }
                    
                    this.track(habitId, currentStatus);
                }
            }
        }
    </script>
</x-app-layout>
