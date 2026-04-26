<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class MainController extends Controller
{
    public function root(): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function index(): View|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        if ($user->isSuperadmin()) {
            return redirect()->route('admin.dashboard');
        }

        $companies = match (true) {
            $user->isAdmin() => Company::query()
                ->where('subscription_id', $user->subscription_id)
                ->latest()
                ->get(),
            default => $user->companies()->latest('companies.created_at')->get(),
        };

        return view('main.main', [
            'user' => $user,
            'companies' => $companies,
        ]);
    }
}