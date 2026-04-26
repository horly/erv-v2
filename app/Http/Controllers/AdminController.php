<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard(): View
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

    public function users(): View
    {
        return view('admin.users', [
            'user' => Auth::user(),
            'users' => User::query()
                ->with('subscription')
                ->orderByRaw("case when role = 'superadmin' then 0 when role = 'admin' then 1 else 2 end")
                ->latest()
                ->paginate(5),
        ]);
    }
    public function subscriptions(): View
    {
        return view('admin.subscriptions', [
            'user' => Auth::user(),
            'subscriptions' => Subscription::query()
                ->withCount(['users', 'companies'])
                ->latest()
                ->paginate(5),
            'subscriptionOptions' => Subscription::query()
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
        ]);
    }
    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'admin_subscription_id' => ['required', 'exists:subscriptions,id'],
        ]);

        User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => $validated['password'],
            'role' => User::ROLE_ADMIN,
            'subscription_id' => $validated['admin_subscription_id'],
        ]);

        return redirect()
            ->route('admin.subscriptions')
            ->with('success', __('admin.admin_saved'));
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
            'status' => Subscription::statusForExpiration($validated['expires_at'] ?? null),
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
            'status' => Subscription::statusForExpiration($validated['expires_at'] ?? null),
            'expires_at' => $validated['expires_at'] ?: null,
        ]);

        return redirect()
            ->route('admin.subscriptions')
            ->with('success', __('admin.subscription_updated'));
    }

    public function destroySubscription(Subscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return redirect()
            ->route('admin.subscriptions')
            ->with('success', __('admin.subscription_deleted'))
            ->with('toast_type', 'danger');
    }
}