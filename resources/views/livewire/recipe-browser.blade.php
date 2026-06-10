<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('recipes.catalog') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('recipes.catalog_desc') }}</p>
    </div>

    <div class="lg:flex lg:gap-8">
        {{-- Filters sidebar (collapsible below lg — UX.4) --}}
        <aside x-data="{ filtersOpen: false }" class="mb-6 shrink-0 lg:mb-0 lg:w-64">
            {{-- Mobile toggle --}}
            <button
                type="button"
                @click="filtersOpen = ! filtersOpen"
                :aria-expanded="filtersOpen"
                class="mb-3 flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 lg:hidden"
            >
                <span class="inline-flex items-center gap-2">
                    <x-heroicon-o-adjustments-horizontal class="h-4 w-4 text-slate-400" />
                    {{ __('recipes.filters') }}
                    @if ($this->activeFilterCount() > 0)
                        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-emerald-600 px-1.5 text-xs font-semibold text-white">{{ $this->activeFilterCount() }}</span>
                    @endif
                </span>
                <x-heroicon-m-chevron-down class="h-4 w-4 text-slate-400" />
            </button>

            {{-- Panel: hidden below lg until toggled, always shown on lg+ --}}
            <div x-show="filtersOpen" style="display: none" class="space-y-4 lg:!block">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('recipes.filters') }}</h2>

                    <div class="mt-4 space-y-4">
                        {{-- Category --}}
                        <div x-data="listNav()" @click.outside="close()" class="relative">
                            <label id="cat-label" class="block text-sm font-medium text-slate-700">{{ __('recipes.category') }}</label>
                            <button
                                type="button"
                                @click="toggle()"
                                @keydown="onKeydown($event)"
                                :aria-expanded="open"
                                aria-haspopup="listbox"
                                aria-labelledby="cat-label"
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
                            <ul
                                x-ref="list"
                                x-show="open"
                                x-cloak
                                role="listbox"
                                aria-multiselectable="true"
                                aria-labelledby="cat-label"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-2 shadow-lg"
                            >
                                @forelse ($categories as $i => $category)
                                    @php($selected = in_array($category->id, $category_ids))
                                    <li wire:key="category-{{ $category->id }}">
                                        <button
                                            type="button"
                                            role="option"
                                            aria-selected="{{ $selected ? 'true' : 'false' }}"
                                            @click.stop
                                            wire:click="toggleCategory({{ $category->id }})"
                                            @mouseenter="activeIndex = {{ $i }}"
                                            class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-emerald-50"
                                            :class="{ 'bg-emerald-50': activeIndex === {{ $i }} }"
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
                                    </li>
                                @empty
                                    <li class="px-2 py-1.5 text-sm text-slate-400">{{ __('recipes.all_categories') }}</li>
                                @endforelse
                            </ul>
                        </div>

                        {{-- Cuisine --}}
                        <div x-data="listNav()" @click.outside="close()" class="relative">
                            <label id="cuisine-label" class="block text-sm font-medium text-slate-700">{{ __('recipes.cuisine') }}</label>
                            <button
                                type="button"
                                @click="toggle()"
                                @keydown="onKeydown($event)"
                                :aria-expanded="open"
                                aria-haspopup="listbox"
                                aria-labelledby="cuisine-label"
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
                            <ul
                                x-ref="list"
                                x-show="open"
                                x-cloak
                                role="listbox"
                                aria-multiselectable="true"
                                aria-labelledby="cuisine-label"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-2 shadow-lg"
                            >
                                @forelse ($cuisines as $i => $cuisine)
                                    @php($selected = in_array($cuisine->id, $cuisine_ids))
                                    <li wire:key="cuisine-{{ $cuisine->id }}">
                                        <button
                                            type="button"
                                            role="option"
                                            aria-selected="{{ $selected ? 'true' : 'false' }}"
                                            @click.stop
                                            wire:click="toggleCuisine({{ $cuisine->id }})"
                                            @mouseenter="activeIndex = {{ $i }}"
                                            class="flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-emerald-50"
                                            :class="{ 'bg-emerald-50': activeIndex === {{ $i }} }"
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
                                    </li>
                                @empty
                                    <li class="px-2 py-1.5 text-sm text-slate-400">{{ __('recipes.all_cuisines') }}</li>
                                @endforelse
                            </ul>
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
        <div class="mx-auto w-full max-w-4xl" x-data="{ view: (window.localStorage.getItem('catalogView') || 'list') }">
            {{-- Active-filter chips (UX.4) --}}
            @if ($activeFilters !== [])
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    @foreach ($activeFilters as $chip)
                        <button
                            wire:click="{{ $chip['action'] }}"
                            type="button"
                            aria-label="{{ __('recipes.remove_filter') }}: {{ $chip['label'] }}"
                            class="group inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50"
                        >
                            {{ $chip['label'] }}
                            <x-heroicon-m-x-mark class="h-3.5 w-3.5 text-slate-400 group-hover:text-emerald-600" />
                        </button>
                    @endforeach
                    @if (count($activeFilters) >= 2)
                        <button wire:click="clearFilters" type="button" class="ml-1 text-xs font-semibold text-emerald-600 transition hover:text-emerald-700">
                            {{ __('recipes.clear_all') }}
                        </button>
                    @endif
                </div>
            @endif

            {{-- Sort bar + view toggle --}}
            <div class="mb-4 flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">
                    {{ trans_choice('recipes.results_count', $recipes->total(), ['count' => $recipes->total()]) }}
                </p>
                <div class="flex items-center gap-2">
                    {{-- Grid/list toggle (UX.22) --}}
                    <div class="hidden items-center rounded-lg border border-slate-300 bg-white p-0.5 shadow-sm sm:flex">
                        <button
                            type="button"
                            @click="view = 'list'; window.localStorage.setItem('catalogView', 'list')"
                            :class="view === 'list' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-400 hover:text-slate-600'"
                            class="rounded-md p-1.5 transition"
                            aria-label="{{ __('recipes.view_list') }}"
                        >
                            <x-heroicon-o-list-bullet class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="view = 'grid'; window.localStorage.setItem('catalogView', 'grid')"
                            :class="view === 'grid' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-400 hover:text-slate-600'"
                            class="rounded-md p-1.5 transition"
                            aria-label="{{ __('recipes.view_grid') }}"
                        >
                            <x-heroicon-o-squares-2x2 class="h-4 w-4" />
                        </button>
                    </div>

                    <select wire:model.live="sort" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="newest">{{ __('recipes.sort_newest') }}</option>
                        <option value="lowest_kcal">{{ __('recipes.sort_lowest_kcal') }}</option>
                        <option value="shortest_prep">{{ __('recipes.sort_shortest_prep') }}</option>
                    </select>
                </div>
            </div>

            @if ($recipes->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 py-16 text-center">
                    <x-heroicon-o-book-open class="h-12 w-12 text-slate-300" />
                    <p class="mt-4 text-sm font-medium text-slate-500">{{ __('recipes.no_recipes') }}</p>
                </div>
            @else
                {{-- Dim the grid during a filter round-trip (UX.5) --}}
                <div
                    wire:loading.class="opacity-50 pointer-events-none"
                    wire:target="toggleCategory, toggleCuisine, max_kcal, max_prep_time, diet_tags, exclude_allergens, sort, clearFilters, removeDietTag, removeAllergen, removeIngredient, clearMaxKcal, clearMaxPrepTime, clearSearch, search"
                    class="transition-opacity"
                >
                    {{-- List view (horizontal plate cards) --}}
                    <div x-show="view === 'list'" class="flex flex-col gap-6">
                        @foreach ($recipes as $recipe)
                            <x-recipe-card :recipe="$recipe" variant="horizontal" wire:key="list-{{ $recipe->id }}" />
                        @endforeach
                    </div>

                    {{-- Grid view (compact cards) --}}
                    <div x-show="view === 'grid'" x-cloak class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($recipes as $recipe)
                            <x-recipe-card :recipe="$recipe" variant="compact" wire:key="grid-{{ $recipe->id }}" />
                        @endforeach
                    </div>
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
