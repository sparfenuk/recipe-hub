<div>
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <a href="{{ route('cabinet') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-emerald-600">
            <x-heroicon-o-arrow-left class="h-4 w-4" />
            {{ __('cabinet.back_to_cabinet') }}
        </a>
    </div>

    <div class="mx-auto max-w-2xl">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('cabinet.profile') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('cabinet.profile_desc') }}</p>

        {{-- Success message --}}
        @if ($saved)
            <div class="mt-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0 text-emerald-500" />
                {{ __('cabinet.profile_saved') }}
            </div>
        @endif

        <form wire:submit="save" class="mt-8 space-y-8">
            {{-- Avatar section --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('cabinet.avatar') }}</h2>

                <div class="mt-4 flex items-center gap-5">
                    <div class="relative shrink-0">
                        @if ($avatar && $avatar->isPreviewable())
                            <img src="{{ $avatar->temporaryUrl() }}" alt="" class="h-20 w-20 rounded-full object-cover ring-2 ring-slate-100">
                        @elseif ($currentAvatarUrl)
                            <img src="{{ $currentAvatarUrl }}" alt="" class="h-20 w-20 rounded-full object-cover ring-2 ring-slate-100">
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-2 ring-slate-100">
                                <x-heroicon-o-user class="h-10 w-10" />
                            </div>
                        @endif

                        {{-- Upload spinner --}}
                        <div wire:loading wire:target="avatar" class="absolute inset-0 flex items-center justify-center rounded-full bg-white/70">
                            <svg class="h-6 w-6 animate-spin text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50">
                            <x-heroicon-o-arrow-up-tray class="h-4 w-4 text-slate-400" />
                            {{ __('cabinet.choose_photo') }}
                            <input type="file" wire:model="avatar" accept="image/*" class="sr-only">
                        </label>

                        @if ($currentAvatarUrl)
                            <button
                                type="button"
                                wire:click="removeAvatar"
                                wire:confirm="{{ __('cabinet.remove_confirm') }}"
                                class="block text-sm font-medium text-red-600 transition-colors hover:text-red-700"
                            >
                                {{ __('cabinet.remove') }}
                            </button>
                        @endif

                        <p class="text-xs text-slate-400">{{ __('cabinet.avatar_hint') }}</p>
                    </div>
                </div>

                @error('avatar')
                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Personal info section --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('cabinet.personal_info') }}</h2>

                <div class="mt-4 space-y-5">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                        <input
                            type="text"
                            id="name"
                            wire:model="name"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email (read-only) --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                        <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                            {{ Auth::user()->email }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Preferences section --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('cabinet.preferences') }}</h2>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700">{{ __('cabinet.units_pref') }}</label>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <button type="button" wire:click="$set('units_pref', 'metric')" class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-left transition-colors {{ $units_pref === 'metric' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md {{ $units_pref === 'metric' ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                                <x-heroicon-o-scale class="h-4 w-4" />
                            </div>
                            <div>
                                <span class="block text-sm font-medium {{ $units_pref === 'metric' ? 'text-emerald-900' : 'text-slate-700' }}">{{ __('cabinet.metric') }}</span>
                                <span class="text-xs {{ $units_pref === 'metric' ? 'text-emerald-600' : 'text-slate-400' }}">kg, cm, ml</span>
                            </div>
                        </button>

                        <button type="button" wire:click="$set('units_pref', 'imperial')" class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-left transition-colors {{ $units_pref === 'imperial' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md {{ $units_pref === 'imperial' ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                                <x-heroicon-o-scale class="h-4 w-4" />
                            </div>
                            <div>
                                <span class="block text-sm font-medium {{ $units_pref === 'imperial' ? 'text-emerald-900' : 'text-slate-700' }}">{{ __('cabinet.imperial') }}</span>
                                <span class="text-xs {{ $units_pref === 'imperial' ? 'text-emerald-600' : 'text-slate-400' }}">lb, in, fl oz</span>
                            </div>
                        </button>
                    </div>
                    @error('units_pref')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
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
