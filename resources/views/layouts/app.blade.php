<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Habitos') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Inline script to apply dark mode class before page renders to prevent flash -->
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-habits-bg text-habits-ink selection:bg-habits-green/20">
        <div class="min-h-screen flex flex-col md:flex-row" 
             x-data="{ 
                 mobileMenuOpen: false, 
                 isDark: document.documentElement.classList.contains('dark'),
                 toggleTheme() {
                     if (this.isDark) {
                         document.documentElement.classList.remove('dark');
                         localStorage.theme = 'light';
                         this.isDark = false;
                     } else {
                         document.documentElement.classList.add('dark');
                         localStorage.theme = 'dark';
                         this.isDark = true;
                     }
                 }
             }">
             
            <!-- Sidebar Navigation -->
            <aside class="w-full md:w-64 flex-shrink-0 bg-habits-surface border-b md:border-b-0 md:border-r border-habits-line sticky top-0 md:h-screen flex flex-col justify-between z-30 shadow-xs md:shadow-none">
                <div class="flex flex-col">
                    <!-- Brand / Top Header -->
                    <div class="p-5 border-b border-habits-line flex items-center justify-between">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-habits-surface-soft text-habits-green-dark font-extrabold flex items-center justify-center text-xl shadow-xs border border-habits-green/10">
                                H
                            </div>
                            <div>
                                <strong class="text-sm font-bold tracking-tight block">Habitos</strong>
                                <span class="text-xs text-habits-muted font-medium">MVP Académico 2026</span>
                            </div>
                        </a>
                        
                        <!-- Mobile Hamburger Button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-habits-muted hover:text-habits-ink focus:outline-none cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display: none;" />
                            </svg>
                        </button>
                    </div>

                    <!-- Navigation Links -->
                    <nav :class="mobileMenuOpen ? 'block' : 'hidden md:block'" class="p-4 space-y-1">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-habits-surface-soft text-habits-green-dark border-l-4 border-habits-green' : 'text-habits-muted hover:bg-habits-hover hover:text-habits-ink' }}">
                            <span class="text-lg">▣</span> Dashboard
                        </a>
                        <a href="{{ route('habits.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('habits.create') ? 'bg-habits-surface-soft text-habits-green-dark border-l-4 border-habits-green' : 'text-habits-muted hover:bg-habits-hover hover:text-habits-ink' }}">
                            <span class="text-lg">＋</span> Nuevo hábito
                        </a>
                        <a href="{{ route('habits.statistics.view') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('habits.statistics.view') ? 'bg-habits-surface-soft text-habits-green-dark border-l-4 border-habits-green' : 'text-habits-muted hover:bg-habits-hover hover:text-habits-ink' }}">
                            <span class="text-lg">◫</span> Estadísticas
                        </a>
                        <a href="{{ route('premium.view') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold transition-all duration-200 {{ request()->routeIs('premium.view') ? 'bg-habits-surface-soft text-habits-green-dark border-l-4 border-habits-green' : 'text-habits-muted hover:bg-habits-hover hover:text-habits-ink' }}">
                            <span class="text-lg">◆</span> Premium
                        </a>
                        <div class="pt-4 border-t border-habits-line mt-4">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold text-habits-muted hover:bg-habits-hover hover:text-habits-ink transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'bg-habits-surface-soft text-habits-green-dark border-l-4 border-habits-green' : '' }}">
                                <span class="text-lg">👤</span> Mi perfil
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-semibold text-habits-muted hover:bg-red-50/15 hover:text-red-500 transition-all duration-200 text-left cursor-pointer">
                                    <span class="text-lg">↪</span> Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </nav>
                </div>

                <!-- Plan Indicator Box -->
                @auth
                    @php
                        $isPremium = Auth::user()->isPremium();
                        $activeHabitsCount = Auth::user()->habits()->where('estado', true)->count();
                        $limit = 5;
                        $percentage = $isPremium ? 0 : min(100, ($activeHabitsCount / $limit) * 100);
                    @endphp
                    <div :class="mobileMenuOpen ? 'block' : 'hidden md:block'" class="p-4 m-4 bg-[#fff8e5] dark:bg-[#1f1d18] border border-[#f0dfad] dark:border-[#524423] rounded-xl shadow-xs">
                        @if ($isPremium)
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 text-2xs font-extrabold tracking-wider bg-habits-yellow text-[#5d4309] dark:text-[#fcd97f] rounded-md uppercase">Premium Activo</span>
                            </div>
                            <strong class="text-xs text-amber-950 dark:text-amber-200 font-bold block">{{ $activeHabitsCount }} hábitos activos</strong>
                            <span class="text-3xs text-[#8a6a18] dark:text-[#fad373] font-medium block mt-1">Expira: {{ Auth::user()->plan_expires_at->format('d/m/Y') }}</span>
                        @else
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-2xs font-bold text-[#8a6a18] dark:text-[#fad373] uppercase">Plan Gratuito</span>
                                <span class="text-2xs font-extrabold text-[#5d4309] dark:text-[#fad373]">{{ $activeHabitsCount }}/{{ $limit }}</span>
                            </div>
                            <div class="w-full bg-[#f1e4b6] dark:bg-[#3d3215] rounded-full h-2 overflow-hidden mb-3">
                                <div class="bg-habits-yellow h-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                            </div>
                            <a href="{{ route('premium.view') }}" class="block text-center text-xs font-bold py-1.5 px-3 bg-white dark:bg-[#1a231e] text-[#8a6a18] dark:text-[#fad373] border border-[#ead48a] dark:border-[#524423] rounded-md hover:bg-amber-50 dark:hover:bg-[#232e27] active:scale-95 transition-all duration-200">
                                Desbloquear Premium
                            </a>
                        @endif
                    </div>
                @endauth
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Topbar header -->
                <header class="bg-habits-surface border-b border-habits-line px-6 py-4 flex items-center justify-between sticky top-0 z-20 shadow-xs">
                    <div class="text-sm font-semibold text-habits-muted">
                        <!-- Left header placeholder -->
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Theme Toggle Button -->
                        <button @click="toggleTheme()" class="p-2 mr-2 rounded-lg border border-habits-line bg-habits-surface hover:bg-habits-hover text-habits-muted hover:text-habits-ink cursor-pointer active:scale-95 transition-all duration-200 select-none" title="Cambiar tema">
                            <span x-show="!isDark" class="text-sm block">🌙</span>
                            <span x-show="isDark" class="text-sm block" style="display: none;">☀️</span>
                        </button>

                        @auth
                            <!-- User Widget -->
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-habits-surface-soft text-habits-green-dark border border-habits-green/20 font-bold flex items-center justify-center text-sm shadow-xs select-none">
                                    {{ strtoupper(substr(Auth::user()->nombre, 0, 2)) }}
                                </div>
                                <div class="text-left leading-tight hidden sm:block">
                                    <strong class="text-xs font-bold text-habits-ink block">{{ Auth::user()->nombre }}</strong>
                                    <span class="text-3xs text-habits-muted font-medium flex items-center gap-1">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-habits-green"></span>
                                        {{ Auth::user()->timezone }}
                                    </span>
                                </div>
                            </div>
                        @endauth
                    </div>
                </header>

                <!-- Page content container -->
                <main class="flex-1 p-6 md:p-8 max-w-6xl w-full mx-auto">
                    @if (session('status'))
                        <div class="mb-6 p-4 bg-habits-surface-soft border border-habits-green/30 text-habits-green-dark text-sm font-semibold rounded-lg flex items-center gap-2 shadow-xs transition-all duration-200">
                            <span>✓</span> {{ session('status') }}
                        </div>
                    @endif
                    
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
