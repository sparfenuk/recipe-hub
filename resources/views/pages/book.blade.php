<x-layouts.app
    :title="__('book.book_title') . ' — ' . __('book.book_subtitle')"
    :meta-description="__('book.book_intro_body')"
    :canonical-url="url('/book')"
    :og-image="asset('images/book/page-001.jpg')"
>
    {{-- Cover hero --}}
    <section class="-mx-4 mb-12 overflow-hidden rounded-none bg-gradient-to-br from-emerald-500 to-lime-400 sm:-mx-6 sm:rounded-3xl lg:-mx-8">
        <div class="grid items-center gap-8 px-6 py-12 lg:grid-cols-2 lg:gap-12 lg:py-16">
            <div>
                <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white ring-1 ring-inset ring-white/30">
                    {{ __('book.book_subtitle') }}
                </span>
                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">
                    {{ __('book.book_title') }}
                </h1>
                <p class="mt-4 text-base text-emerald-50/95">
                    {{ __('book.book_signed_by') }}
                </p>
            </div>
            <img
                src="{{ asset('images/book/page-001.jpg') }}"
                alt=""
                class="aspect-[16/9] w-full rounded-2xl object-cover shadow-2xl ring-1 ring-black/10"
                loading="eager"
            >
        </div>
    </section>

    <article class="mx-auto max-w-4xl space-y-16 pb-16">
        {{-- Intro greeting --}}
        <section>
            <h2 class="text-4xl font-extrabold tracking-tight text-emerald-700">
                {{ __('book.book_intro_hello') }}
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-700">
                {{ __('book.book_intro_body') }}
            </p>
        </section>

        {{-- Goal --}}
        <section class="rounded-2xl bg-gradient-to-br from-emerald-50 to-lime-50 p-8 ring-1 ring-emerald-100">
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.goal_title') }}</h2>
            <p class="mt-3 text-lg font-semibold text-emerald-700">{{ __('book.goal_lead') }}</p>
            <p class="mt-4 text-base leading-relaxed text-slate-700">{{ __('book.goal_body') }}</p>
        </section>

        {{-- Tools list --}}
        <section>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.tools_title') }}</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach (['t1', 't2', 't3', 't4', 't5'] as $i => $key)
                    <li @class([
                        'rounded-xl bg-white p-5 ring-1 ring-slate-200',
                        'sm:col-span-2' => $i === 4,
                    ])>
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">{{ $i + 1 }}</div>
                            <div>
                                <h3 class="font-bold text-slate-900">{{ __("book.tools_{$key}_h") }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __("book.tools_{$key}_b") }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Audience --}}
        <section>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.audience_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">{{ __('book.audience_lead') }}</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (['a1', 'a2', 'a3', 'a4', 'a5'] as $key)
                    <div class="rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200">
                        <h3 class="font-bold text-slate-900">{{ __("book.audience_{$key}_h") }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __("book.audience_{$key}_b") }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Portion note --}}
        <section class="rounded-2xl border-l-4 border-amber-400 bg-amber-50 p-6">
            <div class="flex gap-3">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 shrink-0 text-amber-600" />
                <div>
                    <h3 class="font-bold text-amber-900">{{ __('book.portion_note_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-amber-900/90">{{ __('book.portion_note_body') }}</p>
                </div>
            </div>
        </section>

        {{-- Calorie targets --}}
        <section>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.cal_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">{{ __('book.cal_lead') }}</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-slate-200">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                        <x-heroicon-o-arrow-trending-down class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 font-bold text-slate-900">{{ __('book.cal_lose') }}</h3>
                    <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-700">
                        {{ __('book.cal_lose_formula') }}
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-slate-200">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <x-heroicon-o-minus class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 font-bold text-slate-900">{{ __('book.cal_maintain') }}</h3>
                    <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-700">
                        {{ __('book.cal_maintain_formula') }}
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 text-center ring-1 ring-slate-200">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                        <x-heroicon-o-arrow-trending-up class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 font-bold text-slate-900">{{ __('book.cal_gain') }}</h3>
                    <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-700">
                        {{ __('book.cal_gain_formula') }}
                    </div>
                </div>
            </div>

            <p class="mt-4 text-sm text-slate-500">{{ __('book.cal_floor') }}</p>

            <div class="mt-6 rounded-2xl bg-slate-900 p-6 text-slate-100">
                <h3 class="text-lg font-bold text-white">{{ __('book.cal_sport_title') }}</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-start gap-2"><span class="text-emerald-400">&blacktriangleright;</span>{{ __('book.cal_sport_1') }}</li>
                    <li class="flex items-start gap-2"><span class="text-emerald-400">&blacktriangleright;</span>{{ __('book.cal_sport_2') }}</li>
                    <li class="flex items-start gap-2"><span class="text-emerald-400">&blacktriangleright;</span>{{ __('book.cal_sport_3') }}</li>
                </ul>
                <p class="mt-4 text-xs text-slate-400">{{ __('book.cal_sport_note') }}</p>
                @auth
                    <a href="{{ route('cabinet.health') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-300 hover:text-emerald-200">
                        {{ __('book.cal_calc_cta') }}
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                @endauth
            </div>
        </section>

        {{-- 3 steps --}}
        <section>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.steps_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">{{ __('book.steps_lead') }}</p>

            <ol class="mt-6 space-y-4">
                @foreach ([1, 2, 3] as $n)
                    <li class="flex gap-4 rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-lime-400 text-xl font-extrabold text-white shadow-md">{{ $n }}</div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ __("book.step_{$n}_h") }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __("book.step_{$n}_b") }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Daily norms --}}
        <section>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.norms_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">{{ __('book.norms_lead') }}</p>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach (['protein', 'carbs', 'veg'] as $key)
                    <div class="rounded-2xl bg-emerald-50 p-6 ring-1 ring-emerald-100">
                        <h3 class="font-bold text-emerald-900">{{ __("book.norms_{$key}_h") }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-emerald-900/85">{{ __("book.norms_{$key}_b") }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                <h3 class="text-xl font-bold text-slate-900">{{ __('book.extra_title') }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ __('book.extra_lead') }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach (['extra_sauces', 'extra_oil', 'extra_avocado'] as $key)
                        <div class="rounded-xl bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                            {{ __("book.{$key}") }}
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ __('book.extra_swaps') }}</p>
            </div>
        </section>

        {{-- Timing --}}
        <section>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.timing_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">{{ __('book.timing_lead') }}</p>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <x-heroicon-o-sun class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 font-bold text-slate-900">{{ __('book.timing_breakfast_h') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('book.timing_breakfast_b') }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 font-bold text-slate-900">{{ __('book.timing_lunch_h') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('book.timing_lunch_b') }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                        <x-heroicon-o-moon class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 font-bold text-slate-900">{{ __('book.timing_dinner_h') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('book.timing_dinner_b') }}</p>
                </div>
            </div>
        </section>

        {{-- Substitutions --}}
        <section x-data="{ open: null }">
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.subs_title') }}</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">{{ __('book.subs_lead') }}</p>
            <p class="mt-2 rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-rose-100">
                <strong>{{ __('book.subs_allergy') }}</strong>
            </p>

            <div class="mt-6 space-y-3">
                @foreach (['protein', 'dairy', 'carbs', 'sweet', 'fruit', 'veg'] as $i => $key)
                    <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
                        <button
                            type="button"
                            x-on:click="open === {{ $i }} ? open = null : open = {{ $i }}"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50"
                            :aria-expanded="open === {{ $i }}"
                        >
                            <span class="font-bold text-slate-900">{{ __("book.subs_group_{$key}_h") }}</span>
                            <x-heroicon-o-chevron-down
                                class="h-5 w-5 shrink-0 text-slate-500 transition-transform"
                                ::class="open === {{ $i }} && 'rotate-180'"
                            />
                        </button>
                        <div x-show="open === {{ $i }}" x-cloak x-transition>
                            <p class="border-t border-slate-200 bg-slate-50/50 px-5 py-4 text-sm leading-relaxed text-slate-700">
                                {{ __("book.subs_group_{$key}_b") }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Tips --}}
        <section>
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.tips_title') }}</h2>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach (range(1, 16) as $n)
                    <div class="flex gap-3 rounded-xl bg-white p-5 ring-1 ring-slate-200">
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">{{ $n }}</div>
                        <div>
                            <h3 class="font-bold text-slate-900">{{ __("book.tips_t{$n}_h") }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ __("book.tips_t{$n}_b") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- CTA --}}
        <section class="rounded-3xl bg-gradient-to-br from-emerald-600 to-lime-500 p-10 text-center text-white shadow-lg">
            <h2 class="text-3xl font-extrabold tracking-tight">
                {{ __('book.sections_heading') }}
            </h2>
            <p class="mx-auto mt-3 max-w-xl text-emerald-50">
                {{ __('book.sections_subhead') }}
            </p>
            <a href="{{ route('recipes.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-emerald-700 shadow-sm transition-colors hover:bg-emerald-50">
                <x-heroicon-o-fire class="h-5 w-5" />
                {{ __('book.hero_cta_browse') }}
            </a>
        </section>
    </article>
</x-layouts.app>
