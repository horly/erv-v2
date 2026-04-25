<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isSuperadmin()) {
            $previousUrl = url()->previous();
            $fallbackUrl = route('main');

            return redirect()
                ->to($previousUrl !== $request->fullUrl() ? $previousUrl : $fallbackUrl)
                ->withErrors(['authorization' => __('auth.unauthorized')]);
        }

        return $next($request);
    }
}
