<div>
    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-slate-500 print:hidden">
        <a href="{{ route('recipes.index') }}" class="transition-colors hover:text-emerald-600">{{ __('recipes.catalog') }}</a>
        <span class="mx-2">/</span>
        <span class="text-slate-900">{{ $recipe->title }}</span>
    </nav>

    {{-- Hero --}}
    <div class="flex items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 p-6 sm:p-8">
        @if ($recipe->getFirstMediaUrl('hero', 'full'))
            <img
                src="{{ $recipe->getFirstMediaUrl('hero', 'full') }}"
                alt="{{ $recipe->title }}"
                class="h-80 w-auto max-w-full object-contain sm:h-96 lg:h-[32rem]"
            >
        @else
            <div class="flex h-80 items-center justify-center sm:h-96 lg:h-[32rem]">
                <x-heroicon-o-photo class="h-16 w-16 text-slate-300" />
            </div>
        @endif
    </div>

    {{-- Title + Meta --}}
    <div class="mt-8">
        <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ $recipe->title }}</h1>

        @if ($recipe->summary)
            <p class="mt-3 text-lg text-slate-600">{{ $recipe->summary }}</p>
        @endif

        {{-- Meta badges --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-500">
            @if ($recipe->prep_time_min)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1">
                    <x-heroicon-o-clock class="h-4 w-4 text-slate-400" />
                    {{ __('recipes.prep') }}: {{ $recipe->prep_time_min }} {{ __('recipes.min') }}
                </span>
            @endif
            @if ($recipe->cook_time_min)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1">
                    <x-heroicon-o-fire class="h-4 w-4 text-slate-400" />
                    {{ __('recipes.cook') }}: {{ $recipe->cook_time_min }} {{ __('recipes.min') }}
                </span>
            @endif
            @if ($recipe->total_time_min)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                    <x-heroicon-o-clock class="h-4 w-4" />
                    {{ __('recipes.total_time') }}: {{ $recipe->total_time_min }} {{ __('recipes.min') }}
                </span>
            @endif
            @if ($recipe->servings)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1">
                    <x-heroicon-o-users class="h-4 w-4 text-slate-400" />
                    {{ $recipe->servings }} {{ __('recipes.servings') }}
                </span>
            @endif
            @if ($recipe->difficulty)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1">
                    {{ __('recipes.difficulty_' . $recipe->difficulty) }}
                </span>
            @endif
            @if ($recipe->cuisine)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1">
                    {{ $recipe->cuisine->name }}
                </span>
            @endif
            @if ($recipe->category)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1">
                    {{ $recipe->category->name }}
                </span>
            @endif
        </div>

        {{-- Tags --}}
        @if ($recipe->tags->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($recipe->tags as $tag)
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Main content grid --}}
    <div class="mt-10 grid gap-10 lg:grid-cols-3">
        {{-- Calculator + Actions: first in DOM so mobile shows it right after the
             title/meta block; sticky beside the content on desktop (UX.2) --}}
        <aside class="space-y-6 lg:order-2 lg:sticky lg:top-24 lg:self-start">
            {{-- Portion calculator (replaces static nutrition panel) --}}
            <div class="print:hidden">
                <livewire:portion-calculator :recipe="$recipe" />
            </div>

            {{-- Favorite button --}}
            <div class="print:hidden">
                <livewire:favorite-button :recipe-id="$recipe->id" />
            </div>

            {{-- Print / PDF buttons --}}
            <div class="flex gap-2 print:hidden">
                <button
                    type="button"
                    onclick="window.print()"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <x-heroicon-o-printer class="h-4 w-4" />
                    {{ __('recipes.print') }}
                </button>
                <a
                    href="{{ route('recipes.pdf', $recipe->slug) }}"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                    {{ __('recipes.download_pdf') }}
                </a>
            </div>

            {{-- Author --}}
            @if ($recipe->author)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ __('recipes.author') }}</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $recipe->author->name }}</p>
                </div>
            @endif
        </aside>

        {{-- Ingredients + Steps --}}
        <div class="lg:order-1 lg:col-span-2 space-y-10">
            {{-- Description --}}
            @if ($recipe->description)
                <div class="prose prose-slate max-w-none">
                    {!! $recipe->description !!}
                </div>
            @endif

            {{-- Ingredients --}}
            <section>
                <h2 class="flex items-center gap-2 text-xl font-bold text-slate-900">
                    <x-heroicon-o-clipboard-document-list class="h-6 w-6 text-emerald-600" />
                    {{ __('recipes.ingredients') }}
                </h2>

                {{-- Scaled-amount banner: the portion calculator drives these numbers,
                     so this list is the single source of truth, print included (UX.3) --}}
                @if ($portionScaled)
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm">
                        <span class="font-medium text-emerald-800">
                            @if ($portionMode === 'servings' && $portionServings)
                                {{ __('recipes.amounts_for_servings', ['servings' => $portionServings]) }}
                            @else
                                {{ __('recipes.amounts_scaled') }}
                            @endif
                        </span>
                        <button wire:click="resetPortion" type="button" class="font-semibold text-emerald-700 underline-offset-2 transition hover:underline print:hidden">
                            {{ __('recipes.show_original_amounts') }}
                        </button>
                    </div>
                @endif

                <div class="mt-4">
                    @php
                        $grouped = $recipe->recipeIngredients->groupBy('group_label');
                    @endphp

                    @foreach ($grouped as $label => $ingredients)
                        @if ($label)
                            <h3 class="mt-4 mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</h3>
                        @endif

                        <ul class="divide-y divide-slate-100">
                            @foreach ($ingredients as $ri)
                                @php $scaledAmount = (float) $ri->amount * $portionFactor; @endphp
                                {{-- Tickable cook-mode row: Alpine-only, no persistence (UX.19) --}}
                                <li wire:key="ing-{{ $ri->id }}" x-data="{ done: false }" class="flex items-start gap-3 py-2.5">
                                    <button
                                        type="button"
                                        @click="done = ! done"
                                        :aria-pressed="done"
                                        aria-label="{{ __('recipes.tick_ingredient') }}"
                                        class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded border border-slate-300 transition hover:border-emerald-400 print:hidden"
                                        :class="{ 'border-emerald-500 bg-emerald-500 text-white': done }"
                                    >
                                        <x-heroicon-m-check class="h-3.5 w-3.5" x-show="done" x-cloak />
                                    </button>
                                    {{-- Static bullet for print --}}
                                    <span class="mt-1.5 hidden h-2 w-2 flex-shrink-0 rounded-full bg-emerald-500 print:block"></span>
                                    <div class="flex-1" :class="{ 'line-through opacity-60': done }">
                                        <span class="font-medium text-slate-900">
                                            @if ($scaledAmount > 0)
                                                {{ rtrim(rtrim(number_format($scaledAmount, 3), '0'), '.') }}
                                            @endif
                                            @if ($ri->unit)
                                                {{ $ri->unit->name }}
                                            @endif
                                            {{ $ri->ingredient?->name }}
                                        </span>
                                        @if ($ri->note)
                                            <span class="text-sm text-slate-500"> — {{ $ri->note }}</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </section>

            {{-- Steps --}}
            <section>
                <h2 class="flex items-center gap-2 text-xl font-bold text-slate-900">
                    <x-heroicon-o-list-bullet class="h-6 w-6 text-emerald-600" />
                    {{ __('recipes.instructions') }}
                </h2>

                <ol class="mt-4 space-y-6">
                    @foreach ($recipe->steps as $step)
                        <li class="flex gap-4">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
                                {{ $step->position }}
                            </div>
                            <div class="flex-1 pt-1">
                                <div class="prose prose-slate prose-sm max-w-none">
                                    {!! nl2br(e($step->body)) !!}
                                </div>
                                @if ($step->getFirstMediaUrl('step_photo', 'card'))
                                    <img
                                        src="{{ $step->getFirstMediaUrl('step_photo', 'card') }}"
                                        alt="{{ __('recipes.step_photo', ['number' => $step->position]) }}"
                                        width="600"
                                        height="400"
                                        class="mt-3 rounded-lg"
                                        loading="lazy"
                                    >
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Gallery with lightbox (UX.20) --}}
            @php
                $galleryMedia = $recipe->getMedia('gallery');
            @endphp
            @if ($galleryMedia->isNotEmpty())
                <section
                    x-data="{
                        open: false,
                        index: 0,
                        images: {{ Js::from($galleryMedia->map(fn ($m) => $m->getUrl('full'))->values()) }},
                        show(i) { this.index = i; this.open = true; this.$refs.dialog.showModal(); },
                        close() { this.$refs.dialog.close(); },
                        prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; },
                        next() { this.index = (this.index + 1) % this.images.length; },
                    }"
                >
                    <h2 class="flex items-center gap-2 text-xl font-bold text-slate-900">
                        <x-heroicon-o-camera class="h-6 w-6 text-emerald-600" />
                        {{ __('recipes.gallery') }}
                    </h2>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($galleryMedia as $i => $media)
                            <button type="button" @click="show({{ $i }})" class="group/g overflow-hidden rounded-lg">
                                <img
                                    src="{{ $media->getUrl('card') }}"
                                    alt="{{ $recipe->title }}"
                                    width="600"
                                    height="400"
                                    class="h-full w-full object-cover transition-transform group-hover/g:scale-105"
                                    loading="lazy"
                                >
                            </button>
                        @endforeach
                    </div>

                    {{-- Native <dialog> lightbox: Escape + backdrop close, no dependency --}}
                    <dialog
                        x-ref="dialog"
                        @close="open = false"
                        class="fixed inset-0 m-0 h-full max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-black/80 print:hidden"
                    >
                        <div class="flex min-h-full items-center justify-center p-4" @click.self="close()">
                            <div class="relative">
                                <img :src="images[index]" alt="" class="max-h-[85vh] w-auto max-w-full rounded-lg object-contain shadow-2xl">

                                <button
                                    type="button"
                                    @click="close()"
                                    aria-label="{{ __('recipes.gallery_close') }}"
                                    class="absolute right-2 top-2 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow transition hover:bg-white"
                                >
                                    <x-heroicon-o-x-mark class="h-5 w-5" />
                                </button>

                                <button
                                    type="button"
                                    x-show="images.length > 1"
                                    @click="prev()"
                                    aria-label="{{ __('recipes.gallery_prev') }}"
                                    class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow transition hover:bg-white"
                                >
                                    <x-heroicon-o-chevron-left class="h-5 w-5" />
                                </button>

                                <button
                                    type="button"
                                    x-show="images.length > 1"
                                    @click="next()"
                                    aria-label="{{ __('recipes.gallery_next') }}"
                                    class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow transition hover:bg-white"
                                >
                                    <x-heroicon-o-chevron-right class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </dialog>
                </section>
            @endif
        </div>

    </div>

    {{-- More from this category (UX.18) --}}
    @if ($relatedRecipes->isNotEmpty())
        <section class="mt-16 border-t border-slate-200 pt-10 print:hidden">
            <h2 class="text-xl font-bold text-slate-900">
                {{ __('recipes.more_from_category', ['category' => $recipe->category->name]) }}
            </h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedRecipes as $related)
                    <x-recipe-card :recipe="$related" variant="compact" wire:key="related-{{ $related->id }}" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- JSON-LD structured data --}}
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'url' => route('recipes.show', $recipe->slug),
        ];
        if ($recipe->summary) {
            $jsonLd['description'] = $recipe->summary;
        }
        $heroUrl = $recipe->getFirstMediaUrl('hero', 'full');
        if ($heroUrl) {
            $jsonLd['image'] = $heroUrl;
        }
        if ($recipe->author) {
            $jsonLd['author'] = ['@type' => 'Person', 'name' => $recipe->author->name];
        }
        if ($recipe->published_at) {
            $jsonLd['datePublished'] = $recipe->published_at->toIso8601String();
        }
        if ($recipe->prep_time_min) {
            $jsonLd['prepTime'] = 'PT'.$recipe->prep_time_min.'M';
        }
        if ($recipe->cook_time_min) {
            $jsonLd['cookTime'] = 'PT'.$recipe->cook_time_min.'M';
        }
        if ($recipe->total_time_min) {
            $jsonLd['totalTime'] = 'PT'.$recipe->total_time_min.'M';
        }
        if ($recipe->servings) {
            $jsonLd['recipeYield'] = $recipe->servings.' servings';
        }
        if ($recipe->category) {
            $jsonLd['recipeCategory'] = $recipe->category->name;
        }
        if ($recipe->cuisine) {
            $jsonLd['recipeCuisine'] = $recipe->cuisine->name;
        }
        if ($recipe->tags->isNotEmpty()) {
            $jsonLd['keywords'] = $recipe->tags->pluck('name')->implode(', ');
        }
        $jsonLd['recipeIngredient'] = $recipe->recipeIngredients->map(function ($ri) {
            $parts = [];
            if ($ri->amount) {
                $parts[] = rtrim(rtrim(number_format((float) $ri->amount, 3), '0'), '.');
            }
            if ($ri->unit) {
                $parts[] = $ri->unit->name;
            }
            if ($ri->ingredient) {
                $parts[] = $ri->ingredient->name;
            }
            return implode(' ', $parts);
        })->values()->all();
        $jsonLd['recipeInstructions'] = $recipe->steps->map(fn ($step) => [
            '@type' => 'HowToStep',
            'position' => $step->position,
            'text' => trim(strip_tags($step->body)),
        ])->values()->all();
        if ($recipe->display_kcal_per_serving) {
            $jsonLd['nutrition'] = [
                '@type' => 'NutritionInformation',
                'calories' => $recipe->display_kcal_per_serving.' kcal',
                'proteinContent' => $recipe->display_protein_per_serving_g.'g',
                'fatContent' => $recipe->display_fat_per_serving_g.'g',
                'carbohydrateContent' => $recipe->display_carbs_per_serving_g.'g',
                'fiberContent' => $recipe->fiber_per_serving_g.'g',
            ];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
</div>
