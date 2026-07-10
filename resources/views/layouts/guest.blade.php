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
        <!-- Split Layout container -->
        <div class="min-h-screen flex flex-col md:flex-row relative" 
             x-data="{ 
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

            <!-- Theme Toggle Button in top corner -->
            <div class="absolute top-6 right-6 z-40">
                <button @click="toggleTheme()" class="p-3 rounded-xl border border-habits-line bg-habits-surface hover:bg-habits-hover text-habits-muted hover:text-habits-ink cursor-pointer active:scale-95 transition-all duration-200 select-none shadow-sm" title="Cambiar tema">
                    <span x-show="!isDark" class="text-base block">🌙</span>
                    <span x-show="isDark" class="text-base block" style="display: none;">☀️</span>
                </button>
            </div>

            <!-- Left Panel: Marketing/Product Overview (Desktop only) -->
            <div class="hidden md:flex md:w-1/2 lg:w-7/12 bg-gradient-to-br from-habits-surface-soft via-habits-bg to-habits-surface border-r border-habits-line p-12 lg:p-20 flex-col justify-between relative overflow-hidden select-none">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-habits-green/5 blur-3xl -translate-y-12 translate-x-12"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 rounded-full bg-habits-teal/5 blur-3xl translate-y-24 -translate-x-24"></div>

                <!-- Brand logotype -->
                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-12 h-12 rounded-full bg-habits-surface text-habits-green-dark font-extrabold flex items-center justify-center text-2xl shadow-xs border border-habits-green/10">
                        H
                    </div>
                    <div>
                        <strong class="text-lg font-bold tracking-tight block">Habitos</strong>
                        <span class="text-xs text-habits-muted font-semibold">Desarrollo personal real</span>
                    </div>
                </div>

                <!-- Hero copy -->
                <div class="space-y-8 max-w-xl my-auto relative z-10">
                    <h1 class="text-4xl lg:text-5xl font-extrabold text-habits-ink tracking-tight leading-tight">
                        Toma el control de tu consistencia diaria.
                    </h1>
                    <p class="text-sm lg:text-base text-habits-muted leading-relaxed font-medium">
                        La única plataforma de hábitos diseñada con una estética minimalista, libre de gamificación distractiva y enfocada 100% en tu desarrollo real.
                    </p>

                    <!-- Core Features bullets -->
                    <ul class="space-y-6 pt-6 text-sm font-semibold text-habits-ink">
                        <li class="flex items-start gap-4">
                            <span class="w-6 h-6 rounded-full bg-[#e2f1e8] dark:bg-[#1a2d23] text-habits-green-dark border border-habits-green/15 flex items-center justify-center text-xs flex-shrink-0">✓</span>
                            <div>
                                <span class="block text-sm lg:text-base">Tracking Interactivo Asíncrono</span>
                                <span class="text-xs text-habits-muted font-medium mt-1 block">Marca tareas y comentarios al instante desde tu dashboard diario.</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <span class="w-6 h-6 rounded-full bg-[#e2f1e8] dark:bg-[#1a2d23] text-habits-green-dark border border-habits-green/15 flex items-center justify-center text-xs flex-shrink-0">✓</span>
                            <div>
                                <span class="block text-sm lg:text-base">Estadísticas de Consistencia Extendidas</span>
                                <span class="text-xs text-habits-muted font-medium mt-1 block">Analiza calendarios de cumplimiento y consistencia por categorías.</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <span class="w-6 h-6 rounded-full bg-[#e2f1e8] dark:bg-[#1a2d23] text-habits-green-dark border border-habits-green/15 flex items-center justify-center text-xs flex-shrink-0">✓</span>
                            <div>
                                <span class="block text-sm lg:text-base">Soporte Multi-Timezone Inteligente</span>
                                <span class="text-xs text-habits-muted font-medium mt-1 block">Tus cierres se adaptan a tu hora local sin romper tus rachas.</span>
                            </div>
                        </li>
                    </ul>

                    <!-- Mock Habit card element -->
                    <div class="pt-8">
                        <div class="p-5 bg-habits-surface/90 border border-habits-line/80 rounded-xl shadow-habits max-w-sm backdrop-blur-xs flex items-center justify-between">
                            <div class="min-w-0">
                                <span class="px-2 py-0.5 text-3xs font-extrabold bg-[#e2f1e8] dark:bg-[#1a2d23] text-habits-green-dark rounded-md border border-habits-green/15 uppercase">Salud</span>
                                <strong class="text-sm font-bold block mt-1.5 text-habits-ink">Caminar 30 min</strong>
                            </div>
                            <span class="text-xs font-extrabold bg-[#fff7e2] dark:bg-[#2c2415] text-[#805f10] dark:text-[#fcd97f] border border-[#f0dfad] dark:border-[#544423] rounded-md px-3 py-1">🔥 12 días</span>
                        </div>
                    </div>
                </div>

                <!-- Footer disclaimer -->
                <p class="text-xs text-habits-muted font-semibold relative z-10">
                    MVP Académico 2026 — Metodología de Pruebas
                </p>
            </div>

            <!-- Right Panel: Login / Register card container -->
            <div class="w-full md:w-1/2 lg:w-5/12 min-h-screen flex flex-col justify-center items-center p-8 bg-habits-surface md:bg-habits-bg transition-colors duration-200">
                <!-- Mobile brand logo (shown on mobile, hidden on desktop) -->
                <div class="md:hidden flex flex-col items-center mb-8 text-center select-none">
                    <div class="w-16 h-16 rounded-full bg-habits-surface-soft text-habits-green-dark font-extrabold flex items-center justify-center text-xl shadow-xs border border-habits-green/10">
                        H
                    </div>
                    <strong class="text-base font-bold tracking-tight block mt-2">Habitos</strong>
                    <span class="text-xs text-habits-muted font-medium mt-0.5">Consistencia real, día a día</span>
                </div>

                <!-- Centralized Form Card -->
                <div class="w-full sm:max-w-md bg-habits-surface border border-habits-line shadow-habits rounded-2xl p-6 sm:p-10">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
