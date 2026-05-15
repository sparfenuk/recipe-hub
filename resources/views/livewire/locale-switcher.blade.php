<div x-data="{ open: false }" class="relative flex items-center">
    <button
        x-on:click="open = !open"
        x-on:click.outside="open = false"
        type="button"
        class="inline-flex items-center gap-1 p-0 text-sm font-medium leading-5 text-slate-600 transition-colors hover:text-emerald-600"
    >
        <x-heroicon-o-language class="h-4 w-4" />
        <span>{{ $locales[$currentLocale] }}</span>
        <x-heroicon-o-chevron-down class="h-4 w-4" />
    </button>

    <div
        x-show="open"
        x-transition
        x-cloak
        class="absolute right-0 top-full z-50 mt-2 w-40 rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
        style="top: 100%"
    >
        @foreach ($locales as $code => $label)
            @if ($code !== $currentLocale)
                <a
                    href="?locale={{ $code }}"
                    class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                >
                    {{ $label }}
                </a>
            @else
                <span class="block px-4 py-2 text-sm font-medium text-emerald-600">
                    {{ $label }}
                </span>
            @endif
        @endforeach
    </div>
</div>
