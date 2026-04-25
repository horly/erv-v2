<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

        return view('main', [
            'user' => $user,
            'companies' => $companies,
        ]);
    }

    public function adminDashboard(): View
    {
        $roleCounts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('admin.dashboard', [
            'user' => Auth::user(),
            'stats' => [
                'subscriptions' => Subscription::query()->count(),
                'users' => User::query()->count(),
                'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
                'companies' => Company::query()->count(),
                'sites' => max(Company::query()->count() + 2, 3),
            ],
            'roleCounts' => [
                'admin' => (int) ($roleCounts[User::ROLE_ADMIN] ?? 0),
                'superadmin' => (int) ($roleCounts[User::ROLE_SUPERADMIN] ?? 0),
                'user' => (int) ($roleCounts[User::ROLE_USER] ?? 0),
            ],
        ]);
    }

    public function adminSubscriptions(): View
    {
        return view('admin.subscriptions', [
            'user' => Auth::user(),
            'subscriptions' => Subscription::query()
                ->withCount(['users', 'companies'])
                ->latest()
                ->paginate(5),
        ]);
    }

    public function storeSubscription(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:standard,pro,business'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $limits = [
            'standard' => 1,
            'pro' => 3,
            'business' => null,
        ];

        $baseCode = Str::upper(Str::slug($validated['name'], '_'));
        $code = $baseCode;
        $suffix = 2;

        while (Subscription::query()->where('code', $code)->exists()) {
            $code = $baseCode.'_'.$suffix;
            $suffix++;
        }

        Subscription::create([
            'name' => $validated['name'],
            'code' => $code,
            'type' => $validated['type'],
            'company_limit' => $limits[$validated['type']],
            'status' => 'active',
            'expires_at' => $validated['expires_at'] ?: null,
        ]);

        return redirect()
            ->route('admin.subscriptions')
            ->with('success', __('admin.subscription_saved'));
    }

    public function updateSubscription(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:standard,pro,business'],
            'expires_at' => ['nullable', 'date'],
            'subscription_id' => ['required', 'integer'],
            'form_mode' => ['required', 'in:edit'],
        ]);

        $limits = [
            'standard' => 1,
            'pro' => 3,
            'business' => null,
        ];

        $subscription->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'company_limit' => $limits[$validated['type']],
            'expires_at' => $validated['expires_at'] ?: null,
        ]);

        return redirect()
            ->route('admin.subscriptions')
            ->with('success', __('admin.subscription_updated'));
    }
}
