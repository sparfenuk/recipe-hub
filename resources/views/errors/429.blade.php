<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Too Many Requests') }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-slate-50 text-slate-900 antialiased">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-emerald-600">429</h1>
        <p class="mt-4 text-xl font-semibold text-slate-700">{{ __('Too Many Requests') }}</p>
        <p class="mt-2 text-slate-500">{{ __('You have made too many requests. Please wait a moment and try again.') }}</p>
        <a href="{{ url()->previous() }}" class="mt-6 inline-block rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
            {{ __('Go Back') }}
        </a>
    </div>
</body>
</html>
