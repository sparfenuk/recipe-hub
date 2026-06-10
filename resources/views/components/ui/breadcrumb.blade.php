@props(['items' => []])

{{-- Shared cabinet/page breadcrumb. $items: [['label' => '...', 'url' => '...'?], ...].
     The last item renders as the current page (no link). (UX.8) --}}
<nav {{ $attributes->merge(['class' => 'mb-6 text-sm text-slate-500']) }} aria-label="{{ __('Breadcrumb') }}">
    @foreach ($items as $item)
        @if (! empty($item['url']) && ! $loop->last)
            <a href="{{ $item['url'] }}" class="transition-colors hover:text-emerald-600">{{ $item['label'] }}</a>
            <span class="mx-2" aria-hidden="true">/</span>
        @else
            <span class="text-slate-900" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
            @unless ($loop->last)
                <span class="mx-2" aria-hidden="true">/</span>
            @endunless
        @endif
    @endforeach
</nav>
