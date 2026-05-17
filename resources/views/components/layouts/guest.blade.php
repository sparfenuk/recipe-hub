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
<body class="flex min-h-screen flex-col items-center bg-slate-50 pt-8 text-slate-900 antialiased sm:justify-center sm:pt-0">
    <div class="mb-6">
        <a href="/" class="flex items-center gap-2 text-2xl font-bold tracking-tight text-emerald-600">
            <x-heroicon-o-fire class="h-8 w-8" />
            <span>Recipe Hub</span>
        </a>
    </div>

    <div class="w-full overflow-hidden rounded-xl bg-white px-6 py-8 shadow-md sm:max-w-md">
        {{ $slot }}
    </div>

    <p class="mt-8 pb-8 text-center text-sm text-slate-500">
        &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
    </p>

    @livewireScriptConfig
</body>
</html>
