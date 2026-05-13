<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <label class="block text-sm font-medium text-slate-700">
        {{ $mode === 'include' ? __('recipes.include_ingredients') : __('recipes.exclude_ingredients') }}
    </label>

    {{-- Selected chips --}}
    @if (count($selected))
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($selected as $id => $name)
                <span @class([
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-emerald-50 text-emerald-700' => $mode === 'include',
                    'bg-red-50 text-red-700' => $mode === 'exclude',
                ])>
                    {{ $name }}
                    <button
                        type="button"
                        wire:click="removeIngredient({{ $id }})"
                        @class([
                            'rounded-full p-0.5 transition-colors',
                            'hover:bg-emerald-200' => $mode === 'include',
                            'hover:bg-red-200' => $mode === 'exclude',
                        ])
                    >
                        <x-heroicon-m-x-mark class="h-3 w-3" />
                    </button>
                </span>
            @endforeach
        </div>
    @endif

    {{-- Search input --}}
    <div class="relative mt-1">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-slate-400" />
        </div>
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            @keydown.escape="open = false"
            placeholder="{{ __('recipes.ingredient_search_placeholder') }}"
            autocomplete="off"
            class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
        >
        <div wire:loading wire:target="query" class="absolute inset-y-0 right-0 flex items-center pr-3">
            <svg class="h-4 w-4 animate-spin text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    {{-- Dropdown results --}}
    @if (mb_strlen($query) >= 2)
        <ul
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg"
        >
            @forelse ($results as $ingredient)
                <li wire:key="result-{{ $ingredient->id }}">
                    <button
                        type="button"
                        wire:click="selectIngredient({{ $ingredient->id }}, {{ Js::from($ingredient->name) }})"
                        @click="open = false"
                        class="flex w-full items-center px-3 py-2 text-left text-sm text-slate-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700"
                    >
                        {{ $ingredient->name }}
                    </button>
                </li>
            @empty
                <li class="px-3 py-2 text-sm text-slate-400">
                    {{ __('recipes.no_ingredients_found') }}
                </li>
            @endforelse
        </ul>
    @endif
</div>
