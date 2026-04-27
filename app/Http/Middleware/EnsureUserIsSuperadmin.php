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
            return redirect()
                ->route('main')
                ->withErrors(['authorization' => __('auth.unauthorized')]);
        }

        return $next($request);
    }
}