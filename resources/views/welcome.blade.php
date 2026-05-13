<x-layouts.app
    title="Recipe Hub — Your Personal Recipe & Nutrition Calculator"
    :canonical-url="url('/')"
>
    <div class="flex flex-col items-center justify-center py-16 text-center sm:py-24">
        <x-heroicon-o-fire class="h-16 w-16 text-emerald-500" />

        <h1 class="mt-6 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
            Recipe Hub
        </h1>

        <p class="mt-4 max-w-lg text-lg text-slate-600">
            {{ __('Browse curated recipes with full nutritional data. Scale ingredients to your daily calorie target with the built-in portion calculator.') }}
        </p>

        <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:gap-4">
            <a href="/recipes" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                {{ __('Browse Recipes') }}
            </a>
            <a href="/register" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                {{ __('Create Account') }}
            </a>
        </div>
    </div>
</x-layouts.app>
