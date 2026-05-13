<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    {{-- Header --}}
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
            <x-heroicon-o-calculator class="h-5 w-5 text-emerald-600" />
            {{ __('calculator.title') }}
        </h2>
    </div>

    {{-- Mode tabs --}}
    <div class="flex border-b border-slate-100">
        @foreach (['servings', 'kcal', 'daily_pct'] as $tab)
            <button
                wire:click="setMode('{{ $tab }}')"
                type="button"
                class="flex-1 px-3 py-2.5 text-center text-xs font-medium transition
                    {{ $this->mode === $tab
                        ? 'border-b-2 border-emerald-600 text-emerald-700'
                        : 'text-slate-500 hover:text-slate-700' }}"
            >
                {{ __('calculator.mode_' . $tab) }}
            </button>
        @endforeach
    </div>

    {{-- Mode inputs --}}
    <div class="px-5 py-4">
        @if ($this->mode === 'servings')
            {{-- Servings input --}}
            <label class="block text-sm font-medium text-slate-700" for="target-servings">
                {{ __('calculator.target_servings') }}
            </label>
            <div class="mt-2 flex items-center gap-2">
                <button
                    wire:click="decrement"
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
                    @if ($this->targetServings && $this->targetServings <= 1) disabled @endif
                    aria-label="{{ __('calculator.decrease') }}"
                >
                    <x-heroicon-o-minus class="h-4 w-4" />
                </button>
                <input
                    id="target-servings"
                    type="number"
                    wire:model.live.debounce.300ms="targetServings"
                    min="1"
                    max="100"
                    class="block w-20 rounded-lg border-slate-200 text-center text-lg font-semibold text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                >
                <button
                    wire:click="increment"
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
                    @if ($this->targetServings && $this->targetServings >= 100) disabled @endif
                    aria-label="{{ __('calculator.increase') }}"
                >
                    <x-heroicon-o-plus class="h-4 w-4" />
                </button>
            </div>
            @if ($this->isScaled)
                <button wire:click="resetServings" type="button" class="mt-1.5 text-xs text-emerald-600 hover:underline">
                    {{ __('calculator.reset', ['servings' => $this->originalServings]) }}
                </button>
            @endif

        @elseif ($this->mode === 'kcal')
            {{-- Calorie input --}}
            <label class="block text-sm font-medium text-slate-700" for="target-kcal">
                {{ __('calculator.target_kcal') }}
            </label>
            <div class="mt-2">
                <div class="relative">
                    <input
                        id="target-kcal"
                        type="number"
                        wire:model.live.debounce.300ms="targetKcal"
                        min="1"
                        step="10"
                        placeholder="{{ __('calculator.kcal_placeholder', ['kcal' => number_format((float) $this->recipe->total_kcal, 0)]) }}"
                        class="block w-full rounded-lg border-slate-200 pr-14 text-lg font-semibold text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">
                        {{ __('recipes.kcal') }}
                    </span>
                </div>
                @if ($this->recipe->total_kcal)
                    <p class="mt-1.5 text-xs text-slate-500">
                        {{ __('calculator.original_kcal', ['kcal' => number_format((float) $this->recipe->total_kcal, 0)]) }}
                    </p>
                @endif
            </div>

        @elseif ($this->mode === 'daily_pct')
            {{-- Daily % input --}}
            @if ($this->dailyKcalTarget)
                <label class="block text-sm font-medium text-slate-700" for="target-daily-pct">
                    {{ __('calculator.target_daily_pct') }}
                </label>
                <div class="mt-2">
                    <div class="relative">
                        <input
                            id="target-daily-pct"
                            type="number"
                            wire:model.live.debounce.300ms="targetDailyPct"
                            min="5"
                            max="100"
                            step="5"
                            placeholder="{{ __('calculator.daily_pct_placeholder') }}"
                            class="block w-full rounded-lg border-slate-200 pr-8 text-lg font-semibold text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">
                            %
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">
                        {{ __('calculator.daily_target_info', ['kcal' => number_format($this->dailyKcalTarget)]) }}
                    </p>
                </div>
            @else
                <div class="rounded-lg bg-slate-50 p-4 text-center">
                    <x-heroicon-o-chart-pie class="mx-auto h-8 w-8 text-slate-300" />
                    <p class="mt-2 text-sm text-slate-500">{{ __('calculator.daily_pct_no_target') }}</p>
                    <a href="{{ route('cabinet.health') }}" class="mt-2 inline-block text-sm font-medium text-emerald-600 hover:underline">
                        {{ __('calculator.set_daily_target') }}
                    </a>
                </div>
            @endif
        @endif
    </div>

    {{-- Scaled ingredients --}}
    <div class="border-t border-slate-100 px-5 py-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            {{ __('calculator.scaled_ingredients') }}
        </h3>

        <div class="mt-3">
            @php
                $grouped = $this->scaledIngredients->groupBy('group_label');
            @endphp

            @foreach ($grouped as $label => $ingredients)
                @if ($label)
                    <p class="mt-3 mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                @endif

                <ul class="space-y-1">
                    @foreach ($ingredients as $item)
                        <li class="flex items-start gap-2 text-sm {{ $item['is_optional'] ? 'opacity-60' : '' }}">
                            <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full {{ $item['is_optional'] ? 'bg-slate-300' : 'bg-emerald-500' }}"></span>
                            <span>
                                <span class="font-semibold text-slate-900">{{ rtrim(rtrim(number_format($item['amount'], 3), '0'), '.') }}</span>
                                @if ($item['unit_code'])
                                    <span class="text-slate-600">{{ $item['unit_code'] }}</span>
                                @endif
                                <span class="text-slate-700">{{ $item['name'] }}</span>
                                @if ($item['is_optional'])
                                    <span class="text-xs text-slate-400">({{ __('recipes.optional') }})</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>

    {{-- Scaled nutrition --}}
    @if ($this->recipe->nutrition_cached_at)
        <div class="border-t border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                {{ __('recipes.nutrition_per_serving') }}
            </h3>

            @php $nutrition = $this->scaledNutrition; @endphp

            <div class="mt-3 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">{{ __('recipes.calories') }}</span>
                    <span class="text-lg font-bold text-slate-900">{{ number_format($nutrition['kcal_per_serving'], 0) }} {{ __('recipes.kcal') }}</span>
                </div>
                <div class="h-px bg-slate-100"></div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">{{ __('recipes.protein') }}</span>
                    <span class="font-semibold text-slate-700">{{ number_format($nutrition['protein_per_serving_g'], 1) }}g</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">{{ __('recipes.fat') }}</span>
                    <span class="font-semibold text-slate-700">{{ number_format($nutrition['fat_per_serving_g'], 1) }}g</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">{{ __('recipes.carbs') }}</span>
                    <span class="font-semibold text-slate-700">{{ number_format($nutrition['carbs_per_serving_g'], 1) }}g</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">{{ __('recipes.fiber') }}</span>
                    <span class="font-semibold text-slate-700">{{ number_format($nutrition['fiber_per_serving_g'], 1) }}g</span>
                </div>
            </div>

            <div class="mt-4 h-px bg-slate-100"></div>

            <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                @if ($this->mode === 'daily_pct' && $this->isScaled)
                    {{ __('calculator.total_daily_pct', ['pct' => $this->targetDailyPct]) }}
                @elseif ($this->mode === 'kcal' && $this->isScaled)
                    {{ __('calculator.total_scaled') }}
                @elseif ($this->mode === 'servings' && $this->isScaled)
                    {{ __('calculator.total_for_servings', ['servings' => $this->targetServings]) }}
                @else
                    {{ __('recipes.nutrition_total') }}
                @endif
            </h3>

            <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between text-slate-600">
                    <span>{{ __('recipes.calories') }}</span>
                    <span class="font-medium">{{ number_format($nutrition['kcal'], 0) }} {{ __('recipes.kcal') }}</span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>{{ __('recipes.protein') }}</span>
                    <span class="font-medium">{{ number_format($nutrition['protein_g'], 1) }}g</span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>{{ __('recipes.fat') }}</span>
                    <span class="font-medium">{{ number_format($nutrition['fat_g'], 1) }}g</span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>{{ __('recipes.carbs') }}</span>
                    <span class="font-medium">{{ number_format($nutrition['carbs_g'], 1) }}g</span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>{{ __('recipes.fiber') }}</span>
                    <span class="font-medium">{{ number_format($nutrition['fiber_g'], 1) }}g</span>
                </div>
            </div>
        </div>
    @endif
</div>
