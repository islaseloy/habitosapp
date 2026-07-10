<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Seeded Users Info Tip -->
    <div class="mb-8 p-5 bg-habits-surface-soft border border-habits-green/20 rounded-xl text-xs leading-relaxed transition-colors duration-200">
        <strong class="text-habits-green-dark block mb-2 font-bold text-sm">💡 Demo de prueba (Seeded Users):</strong>
        <ul class="space-y-2 text-habits-muted font-semibold">
            <li class="flex flex-wrap items-center gap-1">
                <span>• Gratis:</span>
                <code class="bg-habits-surface px-2 py-0.5 rounded border border-habits-line text-habits-ink select-all font-mono text-[11px] transition-colors duration-200">gratis@example.com</code> 
                <span>/</span>
                <code class="bg-habits-surface px-2 py-0.5 rounded border border-habits-line text-habits-ink select-all font-mono text-[11px] transition-colors duration-200">password</code>
            </li>
            <li class="flex flex-wrap items-center gap-1">
                <span>• Premium:</span>
                <code class="bg-habits-surface px-2 py-0.5 rounded border border-habits-line text-habits-ink select-all font-mono text-[11px] transition-colors duration-200">premium@example.com</code> 
                <span>/</span>
                <code class="bg-habits-surface px-2 py-0.5 rounded border border-habits-line text-habits-ink select-all font-mono text-[11px] transition-colors duration-200">password</code>
            </li>
        </ul>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo Electrónico')" class="text-xs font-bold" />
            <x-text-input id="email" class="block mt-1.5 w-full bg-habits-surface focus:border-habits-green focus:ring-habits-green/20 text-sm" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Contraseña')" class="text-xs font-bold" />
            <x-text-input id="password" class="block mt-1.5 w-full bg-habits-surface focus:border-habits-green focus:ring-habits-green/20 text-sm"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember_me" class="inline-flex items-center select-none cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-habits-line text-habits-green shadow-xs focus:ring-habits-green/20 focus:border-habits-green" name="remember">
                <span class="ms-2.5 text-xs text-habits-muted font-semibold">{{ __('Recordarme en este equipo') }}</span>
            </label>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-4 border-t border-habits-line">
            @if (Route::has('password.request'))
                <a class="underline text-xs text-habits-muted hover:text-habits-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-habits-green font-medium" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <x-primary-button class="ms-3 bg-habits-green hover:bg-habits-green-dark border-transparent active:scale-95 transition-all duration-200 py-2.5 px-5 text-xs font-bold rounded-lg cursor-pointer">
                {{ __('Iniciar Sesión') }}
            </x-primary-button>
        </div>
        
        <!-- Registration Redirect -->
        <div class="text-center pt-2">
            <span class="text-xs text-habits-muted font-semibold">¿No tienes cuenta?</span>
            <a class="text-xs text-habits-green-dark hover:text-habits-green hover:underline font-bold" href="{{ route('register') }}">
                Registrarse gratis
            </a>
        </div>
    </form>
</x-guest-layout>
