<x-layouts.app title="Recipe Hub">
    <div class="flex flex-col items-center justify-center py-20">
        <h1 class="text-4xl font-bold text-emerald-600">
            Recipe Hub
        </h1>
        <p class="mt-4 text-lg text-slate-600">
            Your personal recipe and nutrition calculator.
        </p>

        <div
            x-data="{ show: false }"
            class="mt-8"
        >
            <button
                x-on:click="show = !show"
                class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white transition hover:bg-emerald-700"
            >
                <span x-text="show ? 'Hide' : 'Hello!'"></span>
            </button>
            <p
                x-show="show"
                x-transition
                class="mt-4 text-center text-slate-500"
            >
                Tailwind + Alpine.js + Livewire are ready.
            </p>
        </div>
    </div>
</x-layouts.app>
