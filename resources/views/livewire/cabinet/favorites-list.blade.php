<div>
    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-slate-500">
        <a href="{{ route('cabinet') }}" class="transition-colors hover:text-emerald-600">{{ __('cabinet.dashboard') }}</a>
        <span class="mx-2">/</span>
        <span class="text-slate-900">{{ __('cabinet.favorites') }}</span>
    </nav>

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
                <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                    {{-- Unfavorite button --}}
                    <button
                        wire:click="unfavorite({{ $recipe->id }})"
                        wire:confirm="{{ __('cabinet.unfavorite_confirm') }}"
                        type="button"
                        class="absolute right-2 top-2 z-10 rounded-full bg-white/90 p-1.5 text-red-500 shadow-sm backdrop-blur transition hover:bg-red-50"
                        title="{{ __('recipes.remove_favorite') }}"
                    >
                        <x-heroicon-s-heart class="h-5 w-5" />
                    </button>

                    <a href="{{ route('recipes.show', $recipe->slug) }}">
                        {{-- Hero image --}}
                        <div class="aspect-[3/2] overflow-hidden bg-slate-100">
                            @if ($recipe->getFirstMediaUrl('hero', 'card'))
                                <img
                                    src="{{ $recipe->getFirstMediaUrl('hero', 'card') }}"
                                    alt="{{ $recipe->title }}"
                                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full items-center justify-center">
                                    <x-heroicon-o-photo class="h-12 w-12 text-slate-300" />
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="p-4">
                            <h3 class="font-semibold text-slate-900 group-hover:text-emerald-700">{{ $recipe->title }}</h3>

                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                @if ($recipe->kcal_per_serving)
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-o-fire class="h-3.5 w-3.5" />
                                        {{ number_format((float) $recipe->kcal_per_serving, 0) }} {{ __('recipes.kcal_serving') }}
                                    </span>
                                @endif
                                @if ($recipe->prep_time_min)
                                    <span class="inline-flex items-center gap-1">
                                        <x-heroicon-o-clock class="h-3.5 w-3.5" />
                                        {{ $recipe->prep_time_min }} {{ __('recipes.min') }}
                                    </span>
                                @endif
                                @if ($recipe->difficulty)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5">
                                        {{ ucfirst($recipe->difficulty) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $recipes->links() }}
        </div>
    @endif
</div>
