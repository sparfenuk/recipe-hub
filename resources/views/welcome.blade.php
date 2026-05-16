<x-layouts.app
    title="Recipe Hub — Your Personal Recipe & Nutrition Calculator"
    :canonical-url="url('/')"
    :og-image="asset('images/book/page-001.jpg')"
>
    {{-- Hero --}}
    <section class="relative -mx-4 overflow-hidden bg-gradient-to-br from-emerald-500 via-emerald-600 to-lime-500 px-4 py-16 text-white sm:-mx-6 sm:px-6 sm:py-20 lg:-mx-8 lg:px-8 lg:py-24">
        <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-2">
            <div>
                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white/95 ring-1 ring-inset ring-white/25">
                    {{ __('book.hero_eyebrow') }}
                </span>
                <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('book.hero_title') }}
                </h1>
                <p class="mt-5 max-w-xl text-lg text-emerald-50/95">
                    {{ __('book.hero_lede') }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('recipes.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-emerald-700 shadow-sm transition-colors hover:bg-emerald-50">
                        <x-heroicon-o-fire class="h-5 w-5" />
                        {{ __('book.hero_cta_browse') }}
                    </a>
                    <a href="{{ route('book') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/40 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition-colors hover:bg-white/20">
                        <x-heroicon-o-book-open class="h-5 w-5" />
                        {{ __('book.hero_cta_book') }}
                    </a>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-2 rotate-3 rounded-3xl bg-white/20 blur-xl"></div>
                <img
                    src="{{ asset('images/book/page-001.jpg') }}"
                    alt="{{ __('book.book_title') }}"
                    class="relative aspect-[16/9] w-full rounded-2xl object-cover shadow-2xl ring-1 ring-black/5"
                    loading="eager"
                >
            </div>
        </div>
    </section>

    {{-- Stats strip --}}
    <section class="-mx-4 border-b border-slate-200 bg-white px-4 py-10 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-7">
            @foreach ([
                ['30', __('book.stats_breakfasts')],
                ['32', __('book.stats_lunches')],
                ['34', __('book.stats_dinners')],
                ['13', __('book.stats_smoothies')],
                ['10', __('book.stats_desserts')],
                ['+13', __('book.stats_sauces')],
                ['+15', __('book.stats_secret')],
            ] as [$num, $label])
                <div class="text-center">
                    <div class="text-3xl font-extrabold tracking-tight text-emerald-600 sm:text-4xl">{{ $num }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Sections grid --}}
    @php
        $sectionTiles = [
            ['slug' => 'breakfast',        'label' => 'book.section_breakfast', 'icon' => 'heroicon-o-sun',      'classes' => 'from-amber-100 to-amber-50 text-amber-800 ring-amber-200 hover:ring-amber-400'],
            ['slug' => 'lunch',            'label' => 'book.section_lunch',     'icon' => 'heroicon-o-cake',     'classes' => 'from-emerald-100 to-emerald-50 text-emerald-800 ring-emerald-200 hover:ring-emerald-400'],
            ['slug' => 'dinner',           'label' => 'book.section_dinner',    'icon' => 'heroicon-o-moon',     'classes' => 'from-indigo-100 to-indigo-50 text-indigo-800 ring-indigo-200 hover:ring-indigo-400'],
            ['slug' => 'snacks',           'label' => 'book.section_snacks',    'icon' => 'heroicon-o-sparkles', 'classes' => 'from-rose-100 to-rose-50 text-rose-800 ring-rose-200 hover:ring-rose-400'],
            ['slug' => 'smoothies',        'label' => 'book.section_smoothies', 'icon' => 'heroicon-o-beaker',   'classes' => 'from-lime-100 to-lime-50 text-lime-800 ring-lime-200 hover:ring-lime-400'],
            ['slug' => 'ice-cream',        'label' => 'book.section_ice_cream', 'icon' => 'heroicon-o-cloud',    'classes' => 'from-sky-100 to-sky-50 text-sky-800 ring-sky-200 hover:ring-sky-400'],
            ['slug' => 'desserts',         'label' => 'book.section_desserts',  'icon' => 'heroicon-o-heart',    'classes' => 'from-pink-100 to-pink-50 text-pink-800 ring-pink-200 hover:ring-pink-400'],
            ['slug' => 'sauces-dressings', 'label' => 'book.section_sauces',    'icon' => 'heroicon-o-bolt',     'classes' => 'from-orange-100 to-orange-50 text-orange-800 ring-orange-200 hover:ring-orange-400'],
        ];
        $tileSlugs = array_column($sectionTiles, 'slug');
        $categoryIdBySlug = \App\Models\Category::query()
            ->whereIn('slug', $tileSlugs)
            ->pluck('id', 'slug');
    @endphp
    <section class="py-16">
        <div class="mx-auto max-w-7xl">
            <div class="mb-10 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    {{ __('book.sections_heading') }}
                </h2>
                <p class="mx-auto mt-3 max-w-2xl text-base text-slate-600">
                    {{ __('book.sections_subhead') }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($sectionTiles as $tile)
                    @php
                        $categoryId = $categoryIdBySlug[$tile['slug']] ?? null;
                        $tileHref = $categoryId
                            ? route('recipes.index', ['categories' => [$categoryId]])
                            : route('recipes.index');
                    @endphp
                    <a href="{{ $tileHref }}" class="group block rounded-2xl bg-gradient-to-br {{ $tile['classes'] }} p-6 ring-1 ring-inset transition-all hover:-translate-y-0.5 hover:shadow-md">
                        <x-dynamic-component :component="$tile['icon']" class="h-8 w-8" />
                        <div class="mt-4 text-lg font-bold tracking-tight">{{ __($tile['label']) }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- About + author teasers --}}
    <section class="-mx-4 border-t border-slate-200 bg-white px-4 py-16 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-2">
            <div class="flex flex-col justify-center rounded-2xl bg-gradient-to-br from-slate-50 to-emerald-50 p-8 ring-1 ring-slate-200">
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                    {{ __('book.about_book_title') }}
                </h2>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    {{ __('book.about_book_body') }}
                </p>
                <a href="{{ route('book') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                    {{ __('book.about_cta') }}
                    <x-heroicon-o-arrow-right class="h-4 w-4" />
                </a>
            </div>

            <div class="flex flex-col justify-center rounded-2xl bg-gradient-to-br from-purple-50 to-fuchsia-50 p-8 ring-1 ring-slate-200">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 shrink-0 rounded-full bg-gradient-to-br from-purple-400 to-fuchsia-400 ring-2 ring-white"></div>
                    <div>
                        <div class="text-sm font-semibold uppercase tracking-wide text-purple-700">{{ __('book.author_role') }}</div>
                        <div class="text-lg font-bold text-slate-900">{{ __('book.author_title') }}</div>
                    </div>
                </div>
                <h3 class="mt-6 text-2xl font-bold text-slate-900 sm:text-3xl">
                    {{ __('book.author_teaser_title') }}
                </h3>
                <p class="mt-3 text-base leading-relaxed text-slate-600">
                    {{ __('book.author_teaser_body') }}
                </p>
                <a href="{{ route('author') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-purple-700 hover:text-purple-800">
                    {{ __('book.author_cta') }}
                    <x-heroicon-o-arrow-right class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>
</x-layouts.app>
