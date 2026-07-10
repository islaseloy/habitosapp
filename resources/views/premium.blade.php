<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header -->
        <div class="text-center space-y-2">
            <h1 class="text-3xl font-extrabold text-habits-ink tracking-tight">Elige tu camino hacia la consistencia</h1>
            <p class="text-sm text-habits-muted max-w-lg mx-auto font-medium">Simple, sin gamificación distractiva. Elige el plan que se adapte a tus necesidades de desarrollo personal.</p>
        </div>

        <!-- Simulation Card -->
        <div class="bg-habits-surface border border-habits-line shadow-habits rounded-2xl p-6 md:p-8 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-3 flex-1 text-center sm:text-left">
                <span class="px-2 py-0.5 text-3xs font-extrabold bg-habits-surface-soft text-habits-green-dark border border-habits-green/10 rounded-md uppercase">Simulador Académico</span>
                @if($user->isPremium())
                    <h2 class="text-xl font-bold tracking-tight text-habits-ink">¡Eres Usuario Premium!</h2>
                    <p class="text-xs text-habits-muted leading-relaxed">
                        Tu suscripción de prueba académica está activa. Disfrutas de hábitos activos ilimitados e historial de estadísticas completo sin restricciones.
                    </p>
                    <span class="text-xs text-habits-green-dark font-bold block">
                        Vence en: {{ $user->plan_expires_at->format('d/m/Y H:i') }} ({{ round(now()->diffInDays($user->plan_expires_at)) }} días restantes)
                    </span>
                @else
                    <h2 class="text-xl font-bold tracking-tight text-habits-ink">Estás en el Plan Gratuito</h2>
                    <p class="text-xs text-habits-muted leading-relaxed">
                        Tienes acceso a las funciones básicas y seguimiento de hasta 5 hábitos simultáneos. Activa la simulación Premium para testear el sistema completo.
                    </p>
                @endif
            </div>

            <!-- Action Button Form -->
            <form method="POST" action="{{ route('premium.toggle') }}" class="flex-shrink-0">
                @csrf
                @if($user->isPremium())
                    <button type="submit" class="py-3 px-5 border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 hover:text-red-800 text-xs font-bold rounded-lg transition-all duration-200 active:scale-95 cursor-pointer">
                        Cancelar prueba (Downgrade a Gratis)
                    </button>
                @else
                    <button type="submit" class="py-3 px-5 bg-habits-green text-white hover:bg-habits-green-dark text-xs font-bold rounded-lg shadow-sm transition-all duration-200 active:scale-95 cursor-pointer flex items-center gap-1.5">
                        ◆ Activar 14 días gratis de Premium
                    </button>
                @endif
            </form>
        </div>

        <!-- Plans Comparison Table/Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Free Plan -->
            <div class="bg-habits-surface border border-habits-line shadow-xs rounded-2xl p-6 md:p-8 flex flex-col justify-between relative overflow-hidden">
                @if(!$user->isPremium())
                    <div class="absolute top-0 right-0 w-24 h-24 flex items-center justify-center bg-habits-green/10 text-habits-green-dark text-2xs font-extrabold uppercase rotate-45 translate-x-7 -translate-y-4">Actual</div>
                @endif
                
                <div>
                    <h3 class="text-lg font-bold text-habits-ink tracking-tight mb-2">Plan Gratuito</h3>
                    <div class="mb-6 flex items-baseline gap-1">
                        <strong class="text-3xl font-extrabold text-habits-ink">USD 0</strong>
                        <span class="text-xs text-habits-muted font-medium">/ siempre gratis</span>
                    </div>
                    <ul class="space-y-3.5 text-xs text-habits-ink">
                        <li class="flex items-start gap-2.5">
                            <span class="text-habits-green-dark font-bold">✓</span>
                            <span>Seguimiento de hasta <strong>5 hábitos activos</strong></span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-habits-green-dark font-bold">✓</span>
                            <span>Visualización de estadísticas de los <strong>últimos 7 días</strong></span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-habits-green-dark font-bold">✓</span>
                            <span>Categorías estándar y personalizadas</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Premium Plan -->
            <div class="bg-habits-surface border-2 border-[#ead48a] shadow-habits rounded-2xl p-6 md:p-8 flex flex-col justify-between relative overflow-hidden">
                @if($user->isPremium())
                    <div class="absolute top-0 right-0 w-24 h-24 flex items-center justify-center bg-habits-yellow text-[#5d4309] text-2xs font-extrabold uppercase rotate-45 translate-x-7 -translate-y-4 font-sans">Actual</div>
                @endif

                <div>
                    <h3 class="text-lg font-bold text-[#5d4309] tracking-tight mb-2 flex items-center gap-1.5">
                        <span>Plan Premium</span>
                        <span class="px-2 py-0.5 text-4xs bg-habits-yellow text-[#5d4309] rounded-md font-extrabold uppercase tracking-wide">Destacado</span>
                    </h3>
                    <div class="mb-6 flex items-baseline gap-1">
                        <strong class="text-3xl font-extrabold text-habits-ink">USD 4</strong>
                        <span class="text-xs text-habits-muted font-medium">/ al mes</span>
                    </div>
                    <ul class="space-y-3.5 text-xs text-habits-ink">
                        <li class="flex items-start gap-2.5">
                            <span class="text-[#805f10] font-bold">★</span>
                            <span>Seguimiento de <strong>hábitos activos ILIMITADOS</strong></span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-[#805f10] font-bold">★</span>
                            <span><strong>Historial de estadísticas completo</strong> desde la creación del hábito</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-[#805f10] font-bold">★</span>
                            <span>Exportación de datos de progreso a PDF/CSV (próximamente)</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-[#805f10] font-bold">★</span>
                            <span>Soporte prioritario y resumen mensual detallado</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
