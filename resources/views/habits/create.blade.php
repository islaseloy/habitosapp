<x-app-layout>
    <div class="max-w-2xl mx-auto" x-data="createHabitForm({{ $limitReached ? 'true' : 'false' }})">
        <!-- Upgrade Block if Limit Reached initially -->
        <template x-if="limitReached">
            <div class="bg-habits-surface border border-habits-line shadow-habits rounded-2xl p-6 md:p-8 text-center md:text-left flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all duration-300">
                <div class="space-y-3 flex-1">
                    <span class="px-2 py-0.5 text-3xs font-extrabold bg-[#fbf0cc] text-[#77550c] border border-[#ead48a] rounded-md uppercase">Gate Freemium</span>
                    <h2 class="text-xl font-bold tracking-tight text-habits-ink">Límite de hábitos activos alcanzado</h2>
                    <p class="text-xs text-habits-muted leading-relaxed">
                        Los usuarios del plan gratuito pueden tener un máximo de <strong>5 hábitos activos</strong>. 
                        Para crear hábitos ilimitados, acceder a estadísticas extendidas e historial completo, suscríbete al plan premium.
                    </p>
                    <div class="flex flex-wrap gap-3 pt-2 justify-center md:justify-start">
                        <form method="POST" action="{{ route('premium.toggle') }}">
                            @csrf
                            <button type="submit" class="py-2 px-4 bg-habits-green text-white text-xs font-bold rounded-lg shadow-sm hover:bg-habits-green-dark transition-all duration-200 active:scale-95 cursor-pointer">
                                ◆ Activar Premium Trial (14 días gratis)
                            </button>
                        </form>
                        <a href="{{ route('habits.statistics.view') }}" class="py-2 px-4 border border-habits-line bg-habits-surface hover:bg-habits-hover text-habits-muted hover:text-habits-ink text-xs font-bold rounded-lg transition-all duration-200">
                            Comparar planes
                        </a>
                    </div>
                </div>
                <div class="bg-[#fff8e5] border border-[#ead48a] p-5 rounded-xl text-center flex-shrink-0 min-w-[140px] shadow-2xs">
                    <span class="text-3xs text-[#8a6a18] font-bold block uppercase mb-1">Desde</span>
                    <strong class="text-3xl font-extrabold text-[#5d4309] tracking-tight block">USD 4</strong>
                    <span class="text-3xs text-[#8a6a18] font-semibold block mt-1">al mes</span>
                </div>
            </div>
        </template>

        <!-- Creation Form -->
        <template x-if="!limitReached">
            <div class="bg-habits-surface border border-habits-line shadow-xs rounded-2xl p-6 md:p-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-xl font-bold text-habits-ink tracking-tight">Crear hábito</h1>
                    <p class="text-xs text-habits-muted font-medium mt-1">Sencillo y directo: operativo en 3 pasos.</p>
                </div>

                <!-- Step Indicators -->
                <div class="flex items-center justify-between mb-8 relative">
                    <div class="absolute h-0.5 bg-habits-line left-4 right-4 top-1/2 -translate-y-1/2 z-0"></div>
                    <div class="absolute h-0.5 bg-habits-green left-4 top-1/2 -translate-y-1/2 z-0 transition-all duration-300" :style="'width: ' + ((step - 1) * 50) + '%'"></div>

                    <!-- Step 1 -->
                    <div class="z-10 flex flex-col items-center gap-1.5">
                        <div class="w-8 h-8 rounded-full border-2 font-bold flex items-center justify-center text-xs transition-all duration-300"
                             :class="step >= 1 ? (step > 1 ? 'bg-habits-green border-habits-green text-white' : 'bg-habits-surface border-habits-green text-habits-green-dark ring-4 ring-habits-green/10') : 'bg-habits-surface border-habits-line text-habits-muted'">
                            <span x-show="step <= 1">1</span>
                            <span x-show="step > 1" style="display: none;">✓</span>
                        </div>
                        <span class="text-4xs font-extrabold tracking-wider uppercase" :class="step >= 1 ? 'text-habits-ink' : 'text-habits-muted'">Definición</span>
                    </div>

                    <!-- Step 2 -->
                    <div class="z-10 flex flex-col items-center gap-1.5">
                        <div class="w-8 h-8 rounded-full border-2 font-bold flex items-center justify-center text-xs transition-all duration-300"
                             :class="step >= 2 ? (step > 2 ? 'bg-habits-green border-habits-green text-white' : 'bg-habits-surface border-habits-green text-habits-green-dark ring-4 ring-habits-green/10') : 'bg-habits-surface border-habits-line text-habits-muted'">
                            <span x-show="step <= 2">2</span>
                            <span x-show="step > 2" style="display: none;">✓</span>
                        </div>
                        <span class="text-4xs font-extrabold tracking-wider uppercase" :class="step >= 2 ? 'text-habits-ink' : 'text-habits-muted'">Frecuencia</span>
                    </div>

                    <!-- Step 3 -->
                    <div class="z-10 flex flex-col items-center gap-1.5">
                        <div class="w-8 h-8 rounded-full border-2 font-bold flex items-center justify-center text-xs transition-all duration-300"
                             :class="step >= 3 ? 'bg-habits-surface border-habits-green text-habits-green-dark ring-4 ring-habits-green/10' : 'bg-habits-surface border-habits-line text-habits-muted'">
                            3
                        </div>
                        <span class="text-4xs font-extrabold tracking-wider uppercase" :class="step >= 3 ? 'text-habits-ink' : 'text-habits-muted'">Inicio</span>
                    </div>
                </div>

                <!-- Server Errors -->
                <div x-show="errors.general" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-lg" x-text="errors.general" style="display: none;"></div>

                <!-- Form Body -->
                <form @submit.prevent="submitForm">
                    <!-- Step 1: Definition -->
                    <div x-show="step === 1" class="space-y-6">
                        <div>
                            <label for="titulo" class="block text-xs font-bold text-habits-ink mb-2">¿Cómo se llamará el hábito?</label>
                            <input type="text" id="titulo" x-model="form.titulo" placeholder="Ej: Meditar, Beber agua, Leer libros" 
                                   class="w-full text-sm p-3 border border-habits-line rounded-lg focus:outline-none focus:border-habits-green bg-habits-bg focus:ring-1 focus:ring-habits-green/20"
                                   :class="errors.titulo ? 'border-red-500 bg-red-50/20' : ''" required>
                            <span x-show="errors.titulo" class="text-3xs text-red-600 font-semibold mt-1 block" x-text="errors.titulo" style="display: none;"></span>
                        </div>

                        <div>
                            <label for="categoria_id" class="block text-xs font-bold text-habits-ink mb-2">Categoría</label>
                            <select id="categoria_id" x-model="form.categoria_id" 
                                    class="w-full text-sm p-3 border border-habits-line rounded-lg focus:outline-none focus:border-habits-green bg-habits-surface focus:ring-1 focus:ring-habits-green/20 text-habits-ink">
                                <option value="">Sin categoría (General)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                @endforeach
                            </select>
                            <span x-show="errors.categoria_id" class="text-3xs text-red-600 font-semibold mt-1 block" x-text="errors.categoria_id" style="display: none;"></span>
                        </div>

                        <div>
                            <label for="descripcion" class="block text-xs font-bold text-habits-ink mb-2">Descripción (opcional)</label>
                            <textarea id="descripcion" x-model="form.descripcion" placeholder="Ej: 10 minutos al despertar para arrancar el día enfocado." 
                                      class="w-full text-sm p-3 border border-habits-line rounded-lg focus:outline-none focus:border-habits-green bg-habits-bg focus:ring-1 focus:ring-habits-green/20 h-24 resize-y"></textarea>
                            <span x-show="errors.descripcion" class="text-3xs text-red-600 font-semibold mt-1 block" x-text="errors.descripcion" style="display: none;"></span>
                        </div>
                    </div>

                    <!-- Step 2: Frequency -->
                    <div x-show="step === 2" class="space-y-6" style="display: none;">
                        <div>
                            <label for="schedule_tipo" class="block text-xs font-bold text-habits-ink mb-2">¿Con qué frecuencia lo realizarás?</label>
                            <select id="schedule_tipo" x-model="form.schedule.tipo" @change="onScheduleTypeChange"
                                    class="w-full text-sm p-3 border border-habits-line rounded-lg focus:outline-none focus:border-habits-green bg-habits-surface focus:ring-1 focus:ring-habits-green/20 text-habits-ink">
                                <option value="diario">Diaria</option>
                                <option value="semanal">Semanal con días específicos</option>
                                <option value="mensual">Mensual (Un día fijo al mes)</option>
                            </select>
                        </div>

                        <!-- Weekly Days Selector -->
                        <div x-show="form.schedule.tipo === 'semanal'" style="display: none;">
                            <label class="block text-xs font-bold text-habits-ink mb-3">Selecciona los días de la semana</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(dayName, index) in ['L', 'M', 'X', 'J', 'V', 'S', 'D']">
                                    <button type="button" @click="toggleDay(index + 1)"
                                            :class="isDaySelected(index + 1) ? 'bg-habits-green border-habits-green text-white shadow-2xs' : 'bg-habits-surface border-habits-line text-habits-muted hover:bg-habits-hover hover:text-habits-ink'"
                                            class="w-10 h-10 border rounded-lg font-bold text-xs transition-all duration-200 flex items-center justify-center select-none cursor-pointer"
                                            x-text="dayName"></button>
                                </template>
                            </div>
                            <span x-show="errors['schedule.dias_semana']" class="text-3xs text-red-600 font-semibold mt-1 block" x-text="errors['schedule.dias_semana']" style="display: none;"></span>
                        </div>

                        <!-- Monthly Day Selector -->
                        <div x-show="form.schedule.tipo === 'mensual'" style="display: none;">
                            <label for="schedule_dia_mes" class="block text-xs font-bold text-habits-ink mb-2">Día del mes (1 al 31)</label>
                            <input type="number" id="schedule_dia_mes" min="1" max="31" x-model.number="form.schedule.dia_mes" 
                                   class="w-32 text-sm p-3 border border-habits-line rounded-lg focus:outline-none focus:border-habits-green bg-habits-bg focus:ring-1 focus:ring-habits-green/20"
                                   :class="errors['schedule.dia_mes'] ? 'border-red-500 bg-red-50/20' : ''">
                            <span x-show="errors['schedule.dia_mes']" class="text-3xs text-red-600 font-semibold mt-1 block" x-text="errors['schedule.dia_mes']" style="display: none;"></span>
                        </div>
                    </div>

                    <!-- Step 3: Start Date -->
                    <div x-show="step === 3" class="space-y-6" style="display: none;">
                        <div>
                            <label for="fecha_inicio" class="block text-xs font-bold text-habits-ink mb-2">¿A partir de qué fecha inicias?</label>
                            <input type="date" id="fecha_inicio" x-model="form.fecha_inicio" 
                                   :max="todayStr"
                                   class="w-full text-sm p-3 border border-habits-line rounded-lg focus:outline-none focus:border-habits-green bg-habits-bg focus:ring-1 focus:ring-habits-green/20 text-habits-ink"
                                   :class="errors.fecha_inicio ? 'border-red-500 bg-red-50/20' : ''" required>
                            <p class="text-4xs text-habits-muted font-medium mt-1">Por consistencia, no se permiten registrar fechas futuras.</p>
                            <span x-show="errors.fecha_inicio" class="text-3xs text-red-600 font-semibold mt-1 block" x-text="errors.fecha_inicio" style="display: none;"></span>
                        </div>
                    </div>

                    <!-- Navigation Footer -->
                    <div class="mt-8 pt-6 border-t border-habits-line flex items-center justify-between">
                        <button type="button" @click="prevStep" x-show="step > 1" 
                                class="py-2.5 px-4 text-xs font-bold text-habits-muted hover:text-habits-ink select-none cursor-pointer"
                                style="display: none;">
                            ← Atrás
                        </button>
                        <div x-show="step === 1"></div> <!-- spacer -->

                        <button type="button" @click="nextStep" x-show="step < 3"
                                class="py-2.5 px-5 bg-habits-ink dark:bg-habits-surface-soft hover:bg-black dark:hover:bg-habits-hover text-white text-xs font-bold rounded-lg transition-all duration-200 select-none cursor-pointer"
                                :disabled="step === 1 && !form.titulo.trim()">
                            Siguiente →
                        </button>

                        <button type="submit" x-show="step === 3" :disabled="submitting"
                                class="py-2.5 px-5 bg-habits-green hover:bg-habits-green-dark text-white text-xs font-bold rounded-lg transition-all duration-200 select-none cursor-pointer disabled:opacity-50"
                                style="display: none;">
                            <span x-show="!submitting">Guardar Hábito</span>
                            <span x-show="submitting" style="display: none;">Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </div>

    <!-- Script to handle Multistep and API submit -->
    <script>
        function createHabitForm(initialLimitReached) {
            const today = new Date();
            // Format today as YYYY-MM-DD
            const offset = today.getTimezoneOffset() * 60000;
            const todayStr = (new Date(today.getTime() - offset)).toISOString().slice(0, 10);

            return {
                step: 1,
                limitReached: initialLimitReached,
                submitting: false,
                todayStr: todayStr,
                form: {
                    titulo: '',
                    categoria_id: '',
                    descripcion: '',
                    fecha_inicio: todayStr,
                    schedule: {
                        tipo: 'diario',
                        dias_semana: [],
                        dia_mes: 1
                    }
                },
                errors: {
                    general: '',
                    titulo: '',
                    categoria_id: '',
                    descripcion: '',
                    fecha_inicio: '',
                    'schedule.dias_semana': '',
                    'schedule.dia_mes': ''
                },
                
                toggleDay(dayNumber) {
                    const index = this.form.schedule.dias_semana.indexOf(dayNumber);
                    if (index > -1) {
                        this.form.schedule.dias_semana.splice(index, 1);
                    } else {
                        this.form.schedule.dias_semana.push(dayNumber);
                    }
                },
                
                isDaySelected(dayNumber) {
                    return this.form.schedule.dias_semana.includes(dayNumber);
                },
                
                prevStep() {
                    if (this.step > 1) this.step--;
                },
                
                nextStep() {
                    // Basic validation before going to next step
                    if (this.step === 1) {
                        if (!this.form.titulo.trim()) {
                            this.errors.titulo = 'El título es obligatorio.';
                            return;
                        }
                        this.errors.titulo = '';
                    } else if (this.step === 2) {
                        if (this.form.schedule.tipo === 'semanal' && this.form.schedule.dias_semana.length === 0) {
                            this.errors['schedule.dias_semana'] = 'Selecciona al menos un día.';
                            return;
                        }
                        if (this.form.schedule.tipo === 'mensual') {
                            const d = parseInt(this.form.schedule.dia_mes);
                            if (isNaN(d) || d < 1 || d > 31) {
                                this.errors['schedule.dia_mes'] = 'Debe ser un número entre 1 y 31.';
                                    return;
                            }
                        }
                        this.errors['schedule.dias_semana'] = '';
                        this.errors['schedule.dia_mes'] = '';
                    }
                    if (this.step < 3) this.step++;
                },
                
                submitForm() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    this.submitting = true;
                    this.errors = {
                        general: '',
                        titulo: '',
                        categoria_id: '',
                        descripcion: '',
                        fecha_inicio: '',
                        'schedule.dias_semana': '',
                        'schedule.dia_mes': ''
                    };
                    
                    // Build payload
                    const payload = {
                        titulo: this.form.titulo,
                        categoria_id: this.form.categoria_id ? parseInt(this.form.categoria_id) : null,
                        descripcion: this.form.descripcion,
                        fecha_inicio: this.form.fecha_inicio,
                        schedule: {
                            tipo: this.form.schedule.tipo
                        }
                    };
                    
                    if (payload.schedule.tipo === 'semanal') {
                        payload.schedule.dias_semana = this.form.schedule.dias_semana;
                    } else if (payload.schedule.tipo === 'mensual') {
                        payload.schedule.dia_mes = parseInt(this.form.schedule.dia_mes);
                    }
                    
                    fetch('/api/habits', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => {
                        return response.json().then(data => {
                            if (!response.ok) {
                                return Promise.reject({ status: response.status, data });
                            }
                            return data;
                        });
                    })
                    .then(data => {
                        console.log('Hábito creado con éxito:', data);
                        // Redirect to dashboard with status
                        window.location.href = '/dashboard';
                    })
                    .catch(error => {
                        this.submitting = false;
                        console.error('Error al crear hábito:', error);
                        
                        if (error.status === 422) {
                            const data = error.data;
                            if (data.error === 'plan_limit_reached') {
                                // Convert dynamically to upgrade screen (TC-01)
                                this.limitReached = true;
                            } else if (data.errors) {
                                // Bind Laravel validation errors to fields
                                Object.keys(data.errors).forEach(key => {
                                    this.errors[key] = data.errors[key][0];
                                });
                            } else {
                                this.errors.general = data.message || 'Error de validación.';
                            }
                        } else {
                            this.errors.general = 'Ocurrió un error inesperado en el servidor. Inténtalo de nuevo.';
                        }
                    });
                }
            };
        }
    </script>
</x-app-layout>
