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

    public function index(): View
    {
        /** @var \App\Models\User&Authenticatable $user */
        $user = Auth::user();

        $companies = match (true) {
            $user->isSuperadmin() => Company::query()->latest()->get(),
            $user->isAdmin() => Company::query()
                ->where('subscription_id', $user->subscription_id)
                ->latest()
                ->get(),
            default => $user->companies()->latest('companies.created_at')->get(),
        };

        return view('main', [
            'user' => $user,
            'companies' => $companies,
        ]);
    }

    public function adminDashboard(): View
    {
        return view('admin.dashboard');
    }
}
