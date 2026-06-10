@props(['recipe', 'variant' => 'compact'])

{{-- Shared recipe card used by the catalog (horizontal) and grids such as
     favorites / related (compact). Defines badges, kcal/time/difficulty
     formatting and hover behaviour once. (UX.10)
     The optional `overlay` slot renders a sibling of the link (e.g. the
     unfavorite button) so it stays clickable above the card link. --}}
@php
    $cardKcal = $recipe->display_kcal_per_serving;
    $cardImg = $recipe->getFirstMediaUrl('hero', 'card');
@endphp

@if ($variant === 'horizontal')
    <a
        href="{{ route('recipes.show', $recipe->slug) }}"
        {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-lg sm:flex-row']) }}
    >
        <div class="flex h-64 shrink-0 items-center justify-center bg-gradient-to-br from-amber-50 to-orange-50 p-4 sm:h-72 sm:w-72 lg:h-80 lg:w-80">
            @if ($cardImg)
                <img
                    src="{{ $cardImg }}"
                    alt="{{ $recipe->title }}"
                    width="320"
                    height="320"
                    class="h-full w-full object-contain transition-transform duration-500 group-hover:scale-110"
                    loading="lazy"
                >
            @else
                <x-heroicon-o-photo class="h-20 w-20 text-slate-300" />
            @endif
        </div>

        <div class="flex flex-1 flex-col justify-center p-6 sm:p-8">
            <h3 class="text-2xl font-bold text-slate-900 group-hover:text-emerald-700">{{ $recipe->title }}</h3>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-600">
                @if ($cardKcal)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-amber-700">
                        <x-heroicon-o-fire class="h-4 w-4" />
                        {{ number_format((float) $cardKcal, 0) }} {{ __('recipes.kcal_serving') }}
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
                        {{ __('recipes.difficulty_' . $recipe->difficulty->value) }}
                    </span>
                @endif
            </div>
        </div>
    </a>
@else
    <div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-md']) }}>
        {{ $overlay ?? '' }}

        <a href="{{ route('recipes.show', $recipe->slug) }}">
            <div class="aspect-[3/2] overflow-hidden bg-slate-100">
                @if ($cardImg)
                    <img
                        src="{{ $cardImg }}"
                        alt="{{ $recipe->title }}"
                        width="600"
                        height="400"
                        class="h-full w-full object-cover transition-transform group-hover:scale-105"
                        loading="lazy"
                    >
                @else
                    <div class="flex h-full items-center justify-center">
                        <x-heroicon-o-photo class="h-12 w-12 text-slate-300" />
                    </div>
                @endif
            </div>

            <div class="p-4">
                <h3 class="font-semibold text-slate-900 group-hover:text-emerald-700">{{ $recipe->title }}</h3>

                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    @if ($cardKcal)
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-o-fire class="h-3.5 w-3.5" />
                            {{ number_format((float) $cardKcal, 0) }} {{ __('recipes.kcal_serving') }}
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
                            {{ __('recipes.difficulty_' . $recipe->difficulty->value) }}
                        </span>
                    @endif
                </div>
            </div>
        </a>
    </div>
@endif
