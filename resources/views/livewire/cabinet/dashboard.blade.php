<div>
    {{-- Header --}}
    <div class="mb-8 flex items-center gap-4">
        @if (Auth::user()->avatar_path)
            <img src="{{ Storage::disk('public')->url(Auth::user()->avatar_path) }}" alt="" class="h-14 w-14 rounded-full object-cover ring-2 ring-white shadow">
        @else
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 ring-2 ring-white shadow">
                <x-heroicon-s-user class="h-7 w-7" />
            </div>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('cabinet.welcome', ['name' => $user->name]) }}</h1>
            <p class="text-sm text-slate-500">{{ $user->email }}</p>
        </div>
    </div>

    {{-- Navigation cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- Profile --}}
        <a href="{{ route('cabinet.profile') }}" class="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-100">
                <x-heroicon-o-user-circle class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-900">{{ __('cabinet.profile') }}</h2>
                <p class="mt-0.5 text-sm leading-snug text-slate-500">{{ __('cabinet.profile_desc') }}</p>
            </div>
            <x-heroicon-o-chevron-right class="ml-auto mt-0.5 h-5 w-5 shrink-0 text-slate-300 transition group-hover:text-emerald-500" />
        </a>

        {{-- Health profile --}}
        <a href="{{ route('cabinet.health') }}" class="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-100">
                <x-heroicon-o-heart class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-900">{{ __('cabinet.health_profile') }}</h2>
                <p class="mt-0.5 text-sm leading-snug text-slate-500">{{ __('cabinet.health_profile_desc') }}</p>
            </div>
            <x-heroicon-o-chevron-right class="ml-auto mt-0.5 h-5 w-5 shrink-0 text-slate-300 transition group-hover:text-emerald-500" />
        </a>

        {{-- Favorites --}}
        <a href="{{ route('cabinet.favorites') }}" class="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-100">
                <x-heroicon-o-heart class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-900">{{ __('cabinet.favorites') }}</h2>
                <p class="mt-0.5 text-sm leading-snug text-slate-500">{{ __('cabinet.favorites_desc') }}</p>
            </div>
            <x-heroicon-o-chevron-right class="ml-auto mt-0.5 h-5 w-5 shrink-0 text-slate-300 transition group-hover:text-emerald-500" />
        </a>

        {{-- Calculations --}}
        <a href="{{ route('cabinet.calculations') }}" class="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-100">
                <x-heroicon-o-calculator class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-900">{{ __('cabinet.calculations') }}</h2>
                <p class="mt-0.5 text-sm leading-snug text-slate-500">{{ __('cabinet.calculations_desc') }}</p>
            </div>
            <x-heroicon-o-chevron-right class="ml-auto mt-0.5 h-5 w-5 shrink-0 text-slate-300 transition group-hover:text-emerald-500" />
        </a>
    </div>
</div>
