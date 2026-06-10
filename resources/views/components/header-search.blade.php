{{-- Accessible header search: a native GET form (no JS), submit affordance,
     and an aria-label. Shared by the desktop and mobile headers. (UX.14) --}}
<form
    method="GET"
    action="{{ route('recipes.index') }}"
    role="search"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
    <input
        type="search"
        name="q"
        value="{{ request('q') }}"
        placeholder="{{ __('recipes.search_placeholder') }}"
        aria-label="{{ __('nav.search_label') }}"
        class="block w-full rounded-lg border-slate-300 pl-9 pr-10 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
    >
    <button
        type="submit"
        aria-label="{{ __('nav.search_submit') }}"
        class="absolute right-1 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-emerald-600"
    >
        <x-heroicon-o-arrow-right class="h-4 w-4" />
    </button>
</form>
