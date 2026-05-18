<x-layouts.guest :title="__('Log in')">
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <h1 class="mb-6 text-center text-2xl font-bold text-slate-900">{{ __('Log in to your account') }}</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
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

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">{{ __('Password') }}</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
            />
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                <span class="text-sm text-slate-600">{{ __('Remember me') }}</span>
            </label>

            <a href="{{ route('password.request') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">
                {{ __('Forgot password?') }}
            </a>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            {{ __('Log in') }}
        </button>
    </form>

</x-layouts.guest>
