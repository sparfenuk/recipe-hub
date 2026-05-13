<button
    wire:click="toggle"
    type="button"
    title="{{ $isFavorited ? __('recipes.remove_favorite') : __('recipes.add_favorite') }}"
    class="inline-flex items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium shadow-sm transition
        {{ $isFavorited
            ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
        }}"
    wire:loading.attr="disabled"
>
    @if ($isFavorited)
        <x-heroicon-s-heart class="h-4 w-4 text-red-500" />
        {{ __('recipes.favorited') }}
    @else
        <x-heroicon-o-heart class="h-4 w-4" />
        {{ __('recipes.add_favorite') }}
    @endif
</button>
