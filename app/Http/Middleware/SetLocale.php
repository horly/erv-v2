<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', ['fr', 'en']);
        $locale = session('locale', config('app.locale', 'fr'));

        if (! in_array($locale, $supportedLocales, true)) {
            $locale = config('app.locale', 'fr');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
