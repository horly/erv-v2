<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
                ->with(['subscription' => fn ($query) => $query->withCount(['users', 'companies'])])
                ->orderByRaw("case when role = 'superadmin' then 0 else 1 end")
                ->latest()
                ->paginate(5),
            'subscriptionOptions' => Subscription::query()
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
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


    public function companies(): View
    {
        return view('admin.companies', [
            'user' => Auth::user(),
            'companies' => Company::query()
                ->with(['subscription', 'phones', 'accounts'])
                ->latest()
                ->paginate(5),
            'countries' => config('countries'),
        ]);
    }

    public function createCompany(): View
    {
        return view('admin.companies-create', [
            'user' => Auth::user(),
            'countries' => config('countries'),
            'subscriptions' => Subscription::query()
                ->with(['users' => fn ($query) => $query
                    ->where('role', User::ROLE_ADMIN)
                    ->orderBy('name')])
                ->withCount('companies')
                ->orderBy('name')
                ->get(),
            'admins' => User::query()
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'subscription_id']),
        ]);
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'admin_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_ADMIN)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', Rule::in(array_values(config('countries')))],
            'slogan' => ['nullable', 'string', 'max:255'],
            'rccm' => ['nullable', 'string', 'max:255'],
            'id_nat' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'phones' => ['nullable', 'array'],
            'phones.*.label' => ['nullable', 'string', 'max:255'],
            'phones.*.phone_number' => ['nullable', 'string', 'max:50'],
            'accounts' => ['nullable', 'array'],
            'accounts.*.bank_name' => ['nullable', 'string', 'max:255'],
            'accounts.*.account_number' => ['nullable', 'string', 'max:100'],
            'accounts.*.currency' => ['nullable', 'string', 'max:12'],
        ]);

        $subscription = Subscription::query()
            ->withCount('companies')
            ->findOrFail($validated['subscription_id']);

        if ($subscription->company_limit !== null && $subscription->companies_count >= $subscription->company_limit) {
            return back()
                ->withInput()
                ->withErrors(['subscription_id' => __('admin.company_limit_reached')]);
        }

        $admin = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('subscription_id', $subscription->id)
            ->find($validated['admin_id']);

        if (! $admin) {
            return back()
                ->withInput()
                ->withErrors(['admin_id' => __('admin.admin_subscription_mismatch')]);
        }

        DB::transaction(function () use ($request, $validated, $admin): void {
            $logoPath = $request->file('logo')?->store('company-logos', 'public');

            $company = Company::create([
                'subscription_id' => $validated['subscription_id'],
                'created_by' => $admin->id,
                'name' => $validated['name'],
                'country' => $validated['country'],
                'slogan' => $validated['slogan'] ?? null,
                'rccm' => $validated['rccm'] ?? null,
                'id_nat' => $validated['id_nat'] ?? null,
                'nif' => $validated['nif'] ?? null,
                'email' => $validated['email'],
                'website' => $validated['website'] ?? null,
                'address' => $validated['address'] ?? null,
                'logo' => $logoPath,
            ]);

            foreach ($validated['phones'] ?? [] as $phone) {
                if (blank($phone['phone_number'] ?? null)) {
                    continue;
                }

                $company->phones()->create([
                    'label' => $phone['label'] ?? null,
                    'phone_number' => $phone['phone_number'],
                ]);
            }

            foreach ($validated['accounts'] ?? [] as $account) {
                if (blank($account['account_number'] ?? null)) {
                    continue;
                }

                $company->accounts()->create([
                    'bank_name' => $account['bank_name'] ?? null,
                    'account_number' => $account['account_number'],
                    'currency' => $account['currency'] ?? null,
                ]);
            }

            $company->users()->syncWithoutDetaching([
                $admin->id => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ],
            ]);
        });

        return redirect()
            ->route('admin.companies')
            ->with('success', __('admin.company_saved'));
    }
    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'role' => ['required', 'in:admin,user'],
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'grade' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        User::create($validated);

        return redirect()
            ->route('admin.users')
            ->with('success', __('admin.user_saved'));
    }

    public function updateUser(Request $request, User $account): RedirectResponse
    {
        if ($account->isSuperadmin()) {
            return redirect()
                ->route('admin.users')
                ->withErrors(['authorization' => __('auth.unauthorized')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$account->id],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'role' => ['required', 'in:admin,user'],
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'grade' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'form_mode' => ['required', 'in:edit'],
            'user_id' => ['required', 'integer'],
        ]);

        unset($validated['form_mode'], $validated['user_id']);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $account->update($validated);

        return redirect()
            ->route('admin.users')
            ->with('success', __('admin.user_updated'));
    }

    public function destroyUser(User $account): RedirectResponse
    {
        if ($account->isSuperadmin()) {
            return redirect()
                ->route('admin.users')
                ->withErrors(['authorization' => __('auth.unauthorized')]);
        }

        $account->delete();

        return redirect()
            ->route('admin.users')
            ->with('success', __('admin.user_deleted'))
            ->with('toast_type', 'danger');
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
