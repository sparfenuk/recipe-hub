<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivateAccess
{
    /**
     * Path patterns (Laravel `is()` syntax) that bypass private-mode redirects.
     */
    private const ALLOWLIST = [
        'login',
        'logout',
        'forgot-password',
        'reset-password',
        'reset-password/*',
        'two-factor-challenge',
        'user/two-factor-*',
        'email/verify',
        'email/verify/*',
        'email/verification-notification',
        'up',
        'admin',
        'admin/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.private')) {
            return $next($request);
        }

        if (Auth::check()) {
            return $next($request);
        }

        if ($request->is(...self::ALLOWLIST)) {
            return $next($request);
        }

        return redirect()->guest(route('login'));
    }
}
