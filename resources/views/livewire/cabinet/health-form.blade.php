<div>
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <a href="{{ route('cabinet') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-emerald-600">
            <x-heroicon-o-arrow-left class="h-4 w-4" />
            {{ __('cabinet.back_to_cabinet') }}
        </a>
    </div>

    <div class="mx-auto max-w-2xl">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('cabinet.health_profile') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('cabinet.health_profile_desc') }}</p>

        {{-- Success message --}}
        @if ($saved)
            <div class="mt-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0 text-emerald-500" />
                {{ __('cabinet.health_saved') }}
            </div>
        @endif

        <form wire:submit="save" class="mt-8 space-y-8">
            {{-- Body metrics --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('cabinet.body_metrics') }}</h2>

                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    {{-- Sex --}}
                    <div>
                        <label for="sex" class="block text-sm font-medium text-slate-700">{{ __('cabinet.sex') }}</label>
                        <select id="sex" wire:model.live="sex" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                            <option value="">{{ __('cabinet.select') }}</option>
                            <option value="male">{{ __('cabinet.male') }}</option>
                            <option value="female">{{ __('cabinet.female') }}</option>
                        </select>
                        @error('sex') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Birth date --}}
                    <div>
                        <label for="birth_date" class="block text-sm font-medium text-slate-700">{{ __('cabinet.birth_date') }}</label>
                        <input type="date" id="birth_date" wire:model.live="birth_date" max="{{ now()->subYear()->format('Y-m-d') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                        @error('birth_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Height --}}
                    <div>
                        <label for="height_cm" class="block text-sm font-medium text-slate-700">{{ __('cabinet.height_cm') }}</label>
                        <div class="relative mt-1">
                            <input type="number" id="height_cm" wire:model.live.debounce.500ms="height_cm" step="0.1" min="50" max="300" class="block w-full rounded-lg border-slate-300 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">cm</span>
                        </div>
                        @error('height_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Weight --}}
                    <div>
                        <label for="weight_kg" class="block text-sm font-medium text-slate-700">{{ __('cabinet.weight_kg') }}</label>
                        <div class="relative mt-1">
                            <input type="number" id="weight_kg" wire:model.live.debounce.500ms="weight_kg" step="0.1" min="20" max="500" class="block w-full rounded-lg border-slate-300 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">kg</span>
                        </div>
                        @error('weight_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Activity level --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('cabinet.activity_level') }}</h2>

                <div class="mt-4 space-y-2">
                    @foreach ([
                        'sedentary' => __('cabinet.activity_sedentary'),
                        'lightly_active' => __('cabinet.activity_lightly_active'),
                        'moderately_active' => __('cabinet.activity_moderately_active'),
                        'very_active' => __('cabinet.activity_very_active'),
                        'extremely_active' => __('cabinet.activity_extremely_active'),
                    ] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors {{ $activity_level === $value ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <input type="radio" wire:model.live="activity_level" value="{{ $value }}" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium {{ $activity_level === $value ? 'text-emerald-900' : 'text-slate-700' }}">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('activity_level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Daily calorie target --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('cabinet.daily_target') }}</h2>

                @if ($suggested_kcal)
                    <div class="mt-4 flex items-center gap-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-emerald-800">{{ __('cabinet.suggested_kcal', ['kcal' => number_format($suggested_kcal)]) }}</p>
                            <p class="text-xs text-emerald-600">{{ __('cabinet.based_on_mifflin') }}</p>
                        </div>
                        <button type="button" wire:click="useSuggested" class="ml-auto shrink-0 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700">
                            {{ __('cabinet.use_suggested') }}
                        </button>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500">{{ __('cabinet.fill_fields_for_suggestion') }}</p>
                @endif

                <div class="mt-4">
                    <label for="daily_kcal_target" class="block text-sm font-medium text-slate-700">{{ __('cabinet.daily_kcal_target') }}</label>
                    <div class="relative mt-1">
                        <input type="number" id="daily_kcal_target" wire:model="daily_kcal_target" min="500" max="10000" class="block w-full rounded-lg border-slate-300 pr-12 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">kcal</span>
                    </div>
                    @error('daily_kcal_target') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
                <a href="{{ route('cabinet') }}" class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:text-slate-900">
                    {{ __('cabinet.cancel') }}
                </a>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:opacity-50"
                >
                    <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="save">{{ __('cabinet.save') }}</span>
                    <span wire:loading wire:target="save">{{ __('cabinet.saving') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
