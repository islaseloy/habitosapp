<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Nombre -->
        <div>
            <x-input-label for="nombre" :value="__('Nombre Completo')" class="text-xs font-bold" />
            <x-text-input id="nombre" class="block mt-1.5 w-full bg-habits-surface focus:border-habits-green focus:ring-habits-green/20 text-sm" type="text" name="nombre" :value="old('nombre')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo Electrónico')" class="text-xs font-bold" />
            <x-text-input id="email" class="block mt-1.5 w-full bg-habits-surface focus:border-habits-green focus:ring-habits-green/20 text-sm" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Timezone -->
        <div>
            <x-input-label for="timezone" :value="__('Zona Horaria (Auto-detectada)')" class="text-xs font-bold" />
            <select id="timezone" name="timezone" class="block mt-1.5 w-full border border-habits-line p-2.5 bg-habits-surface text-habits-ink rounded-lg focus:border-habits-green focus:ring-habits-green/20 focus:outline-none transition-colors duration-200 text-sm cursor-pointer" required>
                @foreach (timezone_identifiers_list() as $tz)
                    <option value="{{ $tz }}" {{ $tz === 'America/Argentina/Buenos_Aires' ? 'selected' : '' }}>
                        {{ $tz }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Contraseña')" class="text-xs font-bold" />
            <x-text-input id="password" class="block mt-1.5 w-full bg-habits-surface focus:border-habits-green focus:ring-habits-green/20 text-sm"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" class="text-xs font-bold" />
            <x-text-input id="password_confirmation" class="block mt-1.5 w-full bg-habits-surface focus:border-habits-green focus:ring-habits-green/20 text-sm"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-4 border-t border-habits-line">
            <a class="underline text-xs text-habits-muted hover:text-habits-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-habits-green font-medium" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta?') }}
            </a>

            <x-primary-button class="ms-4 bg-habits-green hover:bg-habits-green-dark border-transparent active:scale-95 transition-all duration-200 py-2.5 px-5 text-xs font-bold rounded-lg cursor-pointer">
                {{ __('Crear Cuenta') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const select = document.getElementById('timezone');
            if (select && userTz) {
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === userTz) {
                        select.selectedIndex = i;
                        break;
                    }
                }
            }
        });
    </script>
</x-guest-layout>
