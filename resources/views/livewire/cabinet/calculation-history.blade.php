<div>
    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-slate-500">
        <a href="{{ route('cabinet') }}" class="transition-colors hover:text-emerald-600">{{ __('cabinet.dashboard') }}</a>
        <span class="mx-2">/</span>
        <span class="text-slate-900">{{ __('cabinet.calculations') }}</span>
    </nav>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('cabinet.calculations') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('cabinet.calculations_desc') }}</p>
    </div>

    @if ($sessions->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 py-16 text-center">
            <x-heroicon-o-calculator class="h-12 w-12 text-slate-300" />
            <p class="mt-4 text-sm font-medium text-slate-500">{{ __('cabinet.no_calculations') }}</p>
            <a href="{{ route('recipes.index') }}" class="mt-2 text-sm font-medium text-emerald-600 hover:text-emerald-700">
                {{ __('cabinet.browse_recipes') }}
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($sessions as $session)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-4 p-5">
                        <div class="min-w-0 flex-1">
                            @if ($session->recipe)
                                <a href="{{ route('recipes.show', $session->recipe->slug) }}" class="font-semibold text-slate-900 hover:text-emerald-700">
                                    {{ $session->recipe->title }}
                                </a>
                            @else
                                <span class="font-semibold text-slate-400">{{ __('cabinet.deleted_recipe') }}</span>
                            @endif

                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">
                                    {{ __('calculator.mode_' . $session->mode) }}
                                </span>
                                <span>
                                    {{ __('cabinet.input_value') }}: {{ rtrim(rtrim(number_format((float) $session->input_value, 2), '0'), '.') }}
                                    @if ($session->mode === 'kcal')
                                        {{ __('recipes.kcal') }}
                                    @elseif ($session->mode === 'daily_pct')
                                        %
                                    @else
                                        {{ __('recipes.servings') }}
                                    @endif
                                </span>
                                <span>{{ $session->created_at?->diffForHumans() }}</span>
                            </div>

                            @php $totals = $session->totals; @endphp
                            @if (is_array($totals))
                                <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-600">
                                    <span>{{ number_format((float) ($totals['kcal'] ?? 0), 0) }} {{ __('recipes.kcal') }}</span>
                                    <span>{{ __('recipes.protein') }}: {{ number_format((float) ($totals['protein_g'] ?? 0), 1) }}g</span>
                                    <span>{{ __('recipes.fat') }}: {{ number_format((float) ($totals['fat_g'] ?? 0), 1) }}g</span>
                                    <span>{{ __('recipes.carbs') }}: {{ number_format((float) ($totals['carbs_g'] ?? 0), 1) }}g</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @if ($session->recipe)
                                <a
                                    href="{{ route('recipes.show', $session->recipe->slug) }}"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    {{ __('cabinet.view_recipe') }}
                                </a>
                            @endif
                            <button
                                wire:click="delete({{ $session->id }})"
                                wire:confirm="{{ __('cabinet.delete_calculation_confirm') }}"
                                type="button"
                                class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                title="{{ __('cabinet.delete') }}"
                            >
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $sessions->links() }}
        </div>
    @endif
</div>
