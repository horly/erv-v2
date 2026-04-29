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
            if (method_exists($user, 'isUser') && $user->isUser()) {
                $site = $user->sites()
                    ->with('company')
                    ->orderBy('company_sites.id')
                    ->first();

                if ($site?->company) {
                    return redirect()->to(route('main.companies.sites.show', [$site->company, $site]));
                }
            }

            return redirect()->intended(route($user->redirectRouteAfterLogin()));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
