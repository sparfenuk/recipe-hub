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
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-slate-700">{{ __('recipes.category') }}</label>
                            <select id="category_id" wire:model.live="category_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">{{ __('recipes.all_categories') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Cuisine --}}
                        <div>
                            <label for="cuisine_id" class="block text-sm font-medium text-slate-700">{{ __('recipes.cuisine') }}</label>
                            <select id="cuisine_id" wire:model.live="cuisine_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">{{ __('recipes.all_cuisines') }}</option>
                                @foreach ($cuisines as $cuisine)
                                    <option value="{{ $cuisine->id }}">{{ $cuisine->name }}</option>
                                @endforeach
                            </select>
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
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($recipes as $recipe)
                        <a href="{{ route('recipes.show', $recipe->slug) }}" class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-md">
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
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $recipes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
