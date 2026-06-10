<button
    wire:click="toggle"
    type="button"
    @guest
        title="{{ __('recipes.login_to_save') }}"
        aria-label="{{ __('recipes.login_to_save') }}"
    @else
        title="{{ $isFavorited ? __('recipes.remove_favorite') : __('recipes.add_favorite') }}"
        aria-label="{{ $isFavorited ? __('recipes.remove_favorite') : __('recipes.add_favorite') }}"
    @endguest
    class="inline-flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium shadow-sm transition
        {{ $isFavorited
            ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
        }}"
    wire:loading.attr="disabled"
>
    @guest
        {{-- Visible locked state advertises the account benefit (UX.23) --}}
        <x-heroicon-o-heart class="h-4 w-4" />
        {{ __('recipes.login_to_save') }}
    @elseif ($isFavorited)
        <x-heroicon-s-heart class="h-4 w-4 text-red-500" />
        {{ __('recipes.favorited') }}
    @else
        <x-heroicon-o-heart class="h-4 w-4" />
        {{ __('recipes.add_favorite') }}
    @endguest
</button>
