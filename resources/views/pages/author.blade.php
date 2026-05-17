<x-layouts.app
    :title="__('book.author_title') . ' — ' . __('book.author_role')"
    :meta-description="__('book.author_about_body')"
    :canonical-url="url('/author')"
    :og-image="asset('images/book/page-014.jpg')"
>
    <article class="mx-auto max-w-5xl pb-16">
        {{-- Hero --}}
        <section class="grid items-center gap-10 py-12 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <img
                    src="{{ asset('images/book/page-014.jpg') }}"
                    alt="{{ __('book.author_title') }}"
                    class="aspect-[4/5] w-full rounded-3xl object-cover shadow-xl ring-1 ring-slate-200"
                    loading="eager"
                >
            </div>
            <div class="lg:col-span-3">
                <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-purple-700">
                    {{ __('book.author_role') }}
                </span>
                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
                    {{ __('book.author_title') }}
                </h1>
                <p class="mt-5 text-lg leading-relaxed text-slate-700">
                    {{ __('book.author_about_body') }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="https://healthmak.com" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-purple-700">
                        <x-heroicon-o-globe-alt class="h-5 w-5" />
                        {{ __('book.author_link_site') }} · healthmak.com
                    </a>
                    <a href="https://instagram.com/1andremac" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                        <x-heroicon-o-camera class="h-5 w-5" />
                        {{ __('book.author_link_ig') }} · @1andremac
                    </a>
                </div>
            </div>
        </section>

        {{-- About the book --}}
        <section class="mt-8 rounded-3xl bg-gradient-to-br from-emerald-50 to-lime-50 p-8 ring-1 ring-emerald-100 sm:p-10">
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('book.author_about_title') }}</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-700">
                {{ __('book.author_about_body') }}
            </p>
        </section>

        {{-- How it works --}}
        <section class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl bg-white p-8 ring-1 ring-slate-200">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                    <x-heroicon-o-clipboard-document-list class="h-6 w-6" />
                </div>
                <h3 class="mt-4 text-xl font-bold text-slate-900">{{ __('book.author_how_title') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('book.author_how_body') }}</p>
            </div>
            <div class="rounded-2xl bg-white p-8 ring-1 ring-slate-200">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                    <x-heroicon-o-heart class="h-6 w-6" />
                </div>
                <h3 class="mt-4 text-xl font-bold text-slate-900">{{ __('book.author_key_title') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('book.author_key_body') }}</p>
            </div>
        </section>

        {{-- CTA --}}
        <section class="mt-12 rounded-3xl bg-slate-900 p-10 text-center text-white">
            <h2 class="text-3xl font-extrabold tracking-tight">{{ __('book.book_title') }}</h2>
            <p class="mt-3 text-slate-300">{{ __('book.book_subtitle') }}</p>
            <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('book') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-slate-900 transition-colors hover:bg-slate-100">
                    <x-heroicon-o-book-open class="h-5 w-5" />
                    {{ __('book.hero_cta_book') }}
                </a>
                <a href="{{ route('recipes.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition-colors hover:bg-white/20">
                    <x-heroicon-o-fire class="h-5 w-5" />
                    {{ __('book.hero_cta_browse') }}
                </a>
            </div>
        </section>
    </article>
</x-layouts.app>
