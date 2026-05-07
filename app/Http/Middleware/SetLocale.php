<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    private const SUPPORTED = ['en', 'uk'];

    public function handle(Request $request, Closure $next): Response
    {
        $queryLocale = $request->query('locale');

        if (is_string($queryLocale) && in_array($queryLocale, self::SUPPORTED, true)) {
            $cleanUrl = $request->fullUrlWithoutQuery('locale');
            $cookie = cookie('locale', $queryLocale, 525_600, '/', null, null, false, false, 'Lax');

            return redirect($cleanUrl)->withCookie($cookie);
        }

        $cookieLocale = $request->cookie('locale');

        if (is_string($cookieLocale) && in_array($cookieLocale, self::SUPPORTED, true)) {
            app()->setLocale($cookieLocale);

            return $next($request);
        }

        $preferred = $request->getPreferredLanguage(self::SUPPORTED);

        if ($preferred !== null) {
            app()->setLocale($preferred);

            return $next($request);
        }

        app()->setLocale('en');

        return $next($request);
    }
}
