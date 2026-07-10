<section>
    <header>
        <h2 class="text-lg font-medium text-habits-ink">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-habits-muted">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="nombre" :value="__('Nombre')" />
            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $user->nombre)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Zona Horaria')" />
            <select id="timezone" name="timezone" class="mt-1 block w-full border-habits-line focus:border-habits-green focus:ring-habits-green/20 rounded-md shadow-xs bg-habits-surface text-habits-ink" required>
                @foreach (timezone_identifiers_list() as $tz)
                    <option value="{{ $tz }}" {{ old('timezone', $user->timezone) === $tz ? 'selected' : '' }}>
                        {{ $tz }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-habits-ink">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-habits-muted hover:text-habits-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-habits-green">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-habits-muted"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
