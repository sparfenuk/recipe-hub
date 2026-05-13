<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Recipe Hub') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2 text-xl font-bold tracking-tight text-emerald-600">
                <x-heroicon-o-fire class="h-7 w-7" />
                <span>Recipe Hub</span>
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex">
                {{ $nav ?? '' }}
            </nav>

            {{-- Header search --}}
            <div
                class="hidden md:block"
                x-data="{ q: new URLSearchParams(window.location.search).get('q') || '' }"
                x-on:keydown.enter.prevent="if (q.trim()) window.location.href = '{{ route('recipes.index') }}?q=' + encodeURIComponent(q.trim()); else window.location.href = '{{ route('recipes.index') }}';"
            >
                <div class="relative w-64">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        x-model="q"
                        placeholder="{{ __('recipes.search_placeholder') }}"
                        class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>
            </div>

            {{-- Desktop right side: locale switcher + auth --}}
            <div class="hidden items-center gap-3 md:flex">
                <livewire:locale-switcher />

                @auth
                    <a href="/cabinet" class="text-sm font-medium text-slate-600 transition-colors hover:text-emerald-600">
                        {{ Auth::user()->name }}
                    </a>
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-500 transition-colors hover:text-slate-700">
                            {{ __('Log out') }}
                        </button>
                    </form>
                @else
                    <a href="/login" class="text-sm font-medium text-slate-600 transition-colors hover:text-emerald-600">
                        {{ __('Log in') }}
                    </a>
                    <a href="/register" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700">
                        {{ __('Register') }}
                    </a>
                @endauth
            </div>

            {{-- Mobile menu button --}}
            <button
                x-on:click="mobileOpen = !mobileOpen"
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 md:hidden"
                :aria-expanded="mobileOpen"
                aria-label="Toggle navigation"
            >
                <x-heroicon-o-bars-3 x-show="!mobileOpen" class="h-6 w-6" />
                <x-heroicon-o-x-mark x-show="mobileOpen" x-cloak class="h-6 w-6" />
            </button>
        </div>

        {{-- Mobile menu --}}
        <div
            x-show="mobileOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            x-cloak
            class="border-t border-slate-200 bg-white md:hidden"
        >
            <div
                class="px-4 pt-3"
                x-data="{ q: new URLSearchParams(window.location.search).get('q') || '' }"
                x-on:keydown.enter.prevent="if (q.trim()) window.location.href = '{{ route('recipes.index') }}?q=' + encodeURIComponent(q.trim()); else window.location.href = '{{ route('recipes.index') }}';"
            >
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        x-model="q"
                        placeholder="{{ __('recipes.search_placeholder') }}"
                        class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>
            </div>

            <nav class="space-y-1 px-4 py-3 text-sm font-medium text-slate-600">
                {{ $nav ?? '' }}
            </nav>

            <div class="border-t border-slate-200 px-4 py-3">
                <livewire:locale-switcher />

                @auth
                    <a href="/cabinet" class="block py-2 text-sm font-medium text-slate-600">
                        {{ Auth::user()->name }}
                    </a>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" class="block w-full py-2 text-left text-sm font-medium text-slate-500">
                            {{ __('Log out') }}
                        </button>
                    </form>
                @else
                    <a href="/login" class="block py-2 text-sm font-medium text-slate-600">
                        {{ __('Log in') }}
                    </a>
                    <a href="/register" class="block py-2 text-sm font-medium text-emerald-600">
                        {{ __('Register') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <a href="/" class="flex items-center gap-1.5 text-sm font-semibold text-emerald-600">
                    <x-heroicon-o-fire class="h-5 w-5" />
                    Recipe Hub
                </a>
                <p class="text-sm text-slate-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
