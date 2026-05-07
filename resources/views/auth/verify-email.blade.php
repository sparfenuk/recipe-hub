<x-layouts.guest :title="__('Verify your email')">
    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <h1 class="mb-2 text-center text-2xl font-bold text-slate-900">{{ __('Verify your email') }}</h1>
    <p class="mb-6 text-center text-sm text-slate-600">
        {{ __('Thanks for signing up! Before getting started, please verify your email address by clicking the link we just sent you.') }}
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
        @csrf
        <button type="submit" class="flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            {{ __('Resend verification email') }}
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full justify-center text-sm font-medium text-slate-600 transition-colors hover:text-slate-900">
            {{ __('Log out') }}
        </button>
    </form>
</x-layouts.guest>
