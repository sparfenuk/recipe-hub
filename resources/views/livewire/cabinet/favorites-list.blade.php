<div>
    {{-- Breadcrumb (UX.8) --}}
    <x-ui.breadcrumb :items="[
        ['label' => __('cabinet.dashboard'), 'url' => route('cabinet')],
        ['label' => __('cabinet.favorites')],
    ]" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('cabinet.favorites') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('cabinet.favorites_desc') }}</p>
    </div>

    {{-- Search + Sort bar --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative sm:w-72">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="{{ __('recipes.search_placeholder') }}"
                class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >
        </div>

        <select wire:model.live="sort" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="newest">{{ __('cabinet.sort_newest_saved') }}</option>
            <option value="oldest">{{ __('cabinet.sort_oldest_saved') }}</option>
            <option value="alpha">{{ __('cabinet.sort_alpha') }}</option>
            <option value="lowest_kcal">{{ __('recipes.sort_lowest_kcal') }}</option>
        </select>
    </div>

    {{-- Undo affordance for a just-removed favorite (UX.17) --}}
    @if ($recentlyRemovedId)
        <div wire:key="undo-banner" class="mb-6 flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm">
            <span class="text-slate-600">{{ __('cabinet.favorite_removed') }}</span>
            <div class="flex items-center gap-2">
                <button wire:click="undoUnfavorite" type="button" class="font-semibold text-emerald-600 transition-colors hover:text-emerald-700">
                    {{ __('cabinet.undo') }}
                </button>
                <button wire:click="dismissUndo" type="button" class="rounded p-1 text-slate-400 transition-colors hover:text-slate-600" aria-label="{{ __('recipes.gallery_close') }}">
                    <x-heroicon-m-x-mark class="h-4 w-4" />
                </button>
            </div>
        </div>
    @endif

    @if ($recipes->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 py-16 text-center">
            <x-heroicon-o-heart class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-medium text-slate-500">{{ __('cabinet.no_favorites') }}</p>
            <a href="{{ route('recipes.index') }}" class="mt-2 text-sm font-medium text-emerald-600 hover:text-emerald-700">
                {{ __('cabinet.browse_recipes') }}
            </a>
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($recipes as $recipe)
                <x-recipe-card :recipe="$recipe" variant="compact" wire:key="fav-{{ $recipe->id }}">
                    <x-slot:overlay>
                        {{-- Unfavorite (UX.17: no confirm dialog, undo instead) --}}
                        <button
                            wire:click="unfavorite({{ $recipe->id }})"
                            type="button"
                            class="absolute right-2 top-2 z-10 rounded-full bg-white/90 p-1.5 text-red-500 shadow-sm backdrop-blur transition hover:bg-red-50"
                            title="{{ __('recipes.remove_favorite') }}"
                            aria-label="{{ __('recipes.remove_favorite') }}"
                        >
                            <x-heroicon-s-heart class="h-5 w-5" />
                        </button>
                    </x-slot:overlay>
                </x-recipe-card>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $recipes->links() }}
        </div>
    @endif
</div>
