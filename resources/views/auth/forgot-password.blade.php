<x-layouts.guest title="Forgot password">
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <h1 class="mb-2 text-center text-2xl font-bold text-slate-900">Forgot your password?</h1>
    <p class="mb-6 text-center text-sm text-slate-600">
        Enter your email and we'll send you a reset link.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
            />
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            Send reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-medium text-emerald-600 hover:text-emerald-500">Back to log in</a>
    </p>
</x-layouts.guest>
