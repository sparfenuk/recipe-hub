<x-layouts.guest title="Confirm password">
    <h1 class="mb-2 text-center text-2xl font-bold text-slate-900">Confirm your password</h1>
    <p class="mb-6 text-center text-sm text-slate-600">
        This is a secure area. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
            />
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            Confirm
        </button>
    </form>
</x-layouts.guest>
