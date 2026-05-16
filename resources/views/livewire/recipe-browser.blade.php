<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('recipes.catalog') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('recipes.catalog_desc') }}</p>
    </div>

    <div class="lg:flex lg:gap-8">
        {{-- Filters sidebar --}}
        <aside class="mb-6 shrink-0 lg:mb-0 lg:w-64">
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('recipes.filters') }}</h2>

                    <div class="mt-4 space-y-4">
                        {{-- Category --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <label class="block text-sm font-medium text-slate-700">{{ __('recipes.category') }}</label>
                            <button
                                type="button"
                                @click="open = !open"
                                class="mt-1 flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <span class="truncate {{ $category_ids === [] ? 'text-slate-400' : 'text-slate-700' }}">
                                    @if ($category_ids === [])
                                        {{ __('recipes.all_categories') }}
                                    @elseif (count($category_ids) === 1)
                                        {{ $categories->firstWhere('id', $category_ids[0])?->name ?? __('recipes.all_categories') }}
                                    @else
                                        {{ trans_choice('recipes.selected_count', count($category_ids), ['count' => count($category_ids)]) }}
                                    @endif
                                </span>
                                <x-heroicon-m-chevron-down class="h-4 w-4 text-slate-400" />
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-2 shadow-lg"
                            >
                                @forelse ($categories as $category)
                                    @php($selected = in_array($category->id, $category_ids))
                                    <button
                                        type="button"
                                        @click.stop
                                        wire:click="toggleCategory({{ $category->id }})"
                                        wire:key="category-{{ $category->id }}"
                                        class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-emerald-50"
                                    >
                                        <span @class([
                                            'flex h-4 w-4 shrink-0 items-center justify-center rounded border',
                                            'border-emerald-600 bg-emerald-600 text-white' => $selected,
                                            'border-slate-300 bg-white' => ! $selected,
                                        ])>
                                            @if ($selected)
                                                <x-heroicon-m-check class="h-3 w-3" />
                                            @endif
                                        </span>
                                        <span class="text-slate-700">{{ $category->name }}</span>
                                    </button>
                                @empty
                                    <p class="px-2 py-1.5 text-sm text-slate-400">{{ __('recipes.all_categories') }}</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Cuisine --}}
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <label class="block text-sm font-medium text-slate-700">{{ __('recipes.cuisine') }}</label>
                            <button
                                type="button"
                                @click="open = !open"
                                class="mt-1 flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <span class="truncate {{ $cuisine_ids === [] ? 'text-slate-400' : 'text-slate-700' }}">
                                    @if ($cuisine_ids === [])
                                        {{ __('recipes.all_cuisines') }}
                                    @elseif (count($cuisine_ids) === 1)
                                        {{ $cuisines->firstWhere('id', $cuisine_ids[0])?->name ?? __('recipes.all_cuisines') }}
                                    @else
                                        {{ trans_choice('recipes.selected_count', count($cuisine_ids), ['count' => count($cuisine_ids)]) }}
                                    @endif
                                </span>
                                <x-heroicon-m-chevron-down class="h-4 w-4 text-slate-400" />
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-2 shadow-lg"
                            >
                                @forelse ($cuisines as $cuisine)
                                    @php($selected = in_array($cuisine->id, $cuisine_ids))
                                    <button
                                        type="button"
                                        @click.stop
                                        wire:click="toggleCuisine({{ $cuisine->id }})"
                                        wire:key="cuisine-{{ $cuisine->id }}"
                                        class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-emerald-50"
                                    >
                                        <span @class([
                                            'flex h-4 w-4 shrink-0 items-center justify-center rounded border',
                                            'border-emerald-600 bg-emerald-600 text-white' => $selected,
                                            'border-slate-300 bg-white' => ! $selected,
                                        ])>
                                            @if ($selected)
                                                <x-heroicon-m-check class="h-3 w-3" />
                                            @endif
                                        </span>
                                        <span class="text-slate-700">{{ $cuisine->name }}</span>
                                    </button>
                                @empty
                                    <p class="px-2 py-1.5 text-sm text-slate-400">{{ __('recipes.all_cuisines') }}</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Max kcal --}}
                        <div>
                            <label for="max_kcal" class="block text-sm font-medium text-slate-700">{{ __('recipes.max_kcal') }}</label>
                            <input type="number" id="max_kcal" wire:model.live.debounce.500ms="max_kcal" min="0" placeholder="{{ __('recipes.any') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        {{-- Max prep time --}}
                        <div>
                            <label for="max_prep_time" class="block text-sm font-medium text-slate-700">{{ __('recipes.max_prep_time') }}</label>
                            <input type="number" id="max_prep_time" wire:model.live.debounce.500ms="max_prep_time" min="0" placeholder="{{ __('recipes.any') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                {{-- Ingredient filters --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('recipes.ingredients') }}</h3>
                    <div class="mt-3 space-y-4">
                        <livewire:ingredient-autocomplete mode="include" />
                        <livewire:ingredient-autocomplete mode="exclude" />
                    </div>
                </div>

                {{-- Diet tags --}}
                @if ($dietTags->isNotEmpty())
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('recipes.diet_tags') }}</h3>
                        <div class="mt-3 space-y-2">
                            @foreach ($dietTags as $tag)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model.live="diet_tags" value="{{ $tag->id }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-slate-700">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Exclude allergens --}}
                @if ($allergens->isNotEmpty())
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('recipes.exclude_allergens') }}</h3>
                        <div class="mt-3 space-y-2">
                            @foreach ($allergens as $allergen)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model.live="exclude_allergens" value="{{ $allergen->id }}" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    <span class="text-slate-700">{{ $allergen->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->hasActiveFilters())
                    <button wire:click="clearFilters" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-emerald-600 shadow-sm transition hover:bg-emerald-50">
                        {{ __('recipes.clear_filters') }}
                    </button>
                @endif
            </div>
        </aside>

        {{-- Recipe grid --}}
        <div class="mx-auto w-full max-w-4xl">
            {{-- Sort bar --}}
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    {{ trans_choice('recipes.results_count', $recipes->total(), ['count' => $recipes->total()]) }}
                </p>
                <select wire:model.live="sort" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="newest">{{ __('recipes.sort_newest') }}</option>
                    <option value="lowest_kcal">{{ __('recipes.sort_lowest_kcal') }}</option>
                    <option value="shortest_prep">{{ __('recipes.sort_shortest_prep') }}</option>
                </select>
            </div>

            @if ($recipes->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 py-16 text-center">
                    <x-heroicon-o-book-open class="h-12 w-12 text-slate-300" />
                    <p class="mt-4 text-sm font-medium text-slate-500">{{ __('recipes.no_recipes') }}</p>
                </div>
            @else
                <div class="flex flex-col gap-6">
                    @foreach ($recipes as $recipe)
                        <a href="{{ route('recipes.show', $recipe->slug) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-lg sm:flex-row">
                            {{-- Plate image --}}
                            <div class="flex h-64 shrink-0 items-center justify-center bg-gradient-to-br from-amber-50 to-orange-50 p-4 sm:h-72 sm:w-72 lg:h-80 lg:w-80">
                                @if ($recipe->getFirstMediaUrl('hero', 'card'))
                                    <img
                                        src="{{ $recipe->getFirstMediaUrl('hero', 'card') }}"
                                        alt="{{ $recipe->title }}"
                                        class="h-full w-full object-contain transition-transform duration-500 group-hover:scale-110"
                                        loading="lazy"
                                    >
                                @else
                                    <x-heroicon-o-photo class="h-20 w-20 text-slate-300" />
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex flex-1 flex-col justify-center p-6 sm:p-8">
                                <h3 class="text-2xl font-bold text-slate-900 group-hover:text-emerald-700">{{ $recipe->title }}</h3>

                                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                    @if ($recipe->display_kcal_per_serving)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-amber-700">
                                            <x-heroicon-o-fire class="h-4 w-4" />
                                            {{ number_format((float) $recipe->display_kcal_per_serving, 0) }} {{ __('recipes.kcal_serving') }}
                                        </span>
                                    @endif
                                    @if ($recipe->prep_time_min)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                                            <x-heroicon-o-clock class="h-4 w-4" />
                                            {{ $recipe->prep_time_min }} {{ __('recipes.min') }}
                                        </span>
                                    @endif
                                    @if ($recipe->difficulty)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                                            {{ ucfirst($recipe->difficulty) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($recipes->hasMorePages())
                    <div
                        x-data="{
                            init() {
                                const io = new IntersectionObserver((entries) => {
                                    if (entries[0].isIntersecting) {
                                        $wire.loadMore();
                                    }
                                }, { rootMargin: '300px 0px' });
                                io.observe(this.$el);
                                this.$cleanup = () => io.disconnect();
                            },
                        }"
                        wire:loading.remove
                        wire:target="loadMore"
                        class="mt-10 flex justify-center"
                    >
                        <button
                            type="button"
                            wire:click="loadMore"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700"
                        >
                            <x-heroicon-o-arrow-down-circle class="h-4 w-4" />
                            {{ __('recipes.load_more') }}
                        </button>
                    </div>

                    <div
                        wire:loading.flex
                        wire:target="loadMore"
                        class="mt-10 hidden items-center justify-center gap-2 text-sm text-slate-500"
                    >
                        <svg class="h-4 w-4 animate-spin text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        {{ __('recipes.loading_more') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
