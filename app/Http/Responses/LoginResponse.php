<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user && method_exists($user, 'redirectRouteAfterLogin')) {
            return redirect()->intended(route($user->redirectRouteAfterLogin()));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
