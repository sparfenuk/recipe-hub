<x-layouts.app :title="__('cabinet.welcome', ['name' => Auth::user()->name])">
    <h1 class="text-2xl font-bold text-slate-900">{{ __('cabinet.welcome', ['name' => Auth::user()->name]) }}</h1>
    <p class="mt-2 text-slate-600">{{ __('cabinet.tagline') }}</p>
</x-layouts.app>
