<?php

namespace App\Providers;

use App\Models\UserLoginHistory;
use App\Models\CompanySite;
use App\Models\User;
use App\Support\AccountingActivityFeed;
use App\Support\AppBranding;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        config(['app.name' => AppBranding::name()]);
        View::share('appBranding', AppBranding::all());
        View::composer('main.modules.*', function ($view): void {
            static $activityCache = [];

            $site = request()->route('site');

            if (! $site instanceof CompanySite) {
                $view->with('accountingNotifications', []);
                $view->with('accountingUnreadNotificationsCount', 0);

                return;
            }

            $authUser = auth()->user();
            $moduleGroup = $this->notificationModuleGroup();
            $cacheKey = $site->id.'-'.($authUser?->id ?: 'guest').'-'.($moduleGroup ?: 'none');
            $activityCache[$cacheKey] ??= [
                'items' => $moduleGroup
                    ? AccountingActivityFeed::forSite($site, $authUser instanceof User ? $authUser : null, 10, $moduleGroup)
                    : [],
                'unread_count' => $authUser instanceof User
                    ? ($moduleGroup ? AccountingActivityFeed::unreadCount($site, $authUser, $moduleGroup) : 0)
                    : 0,
                'module_group' => $moduleGroup,
            ];

            $view->with('accountingNotifications', $activityCache[$cacheKey]['items']);
            $view->with('accountingUnreadNotificationsCount', $activityCache[$cacheKey]['unread_count']);
            $view->with('notificationModuleGroup', $activityCache[$cacheKey]['module_group']);
        });

        Event::listen(Login::class, function (Login $event): void {
            UserLoginHistory::create([
                'user_id' => $event->user->getAuthIdentifier(),
                'device' => $this->formatDevice(request()->userAgent()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'logged_in_at' => now(),
            ]);
        });
    }

    private function formatDevice(?string $userAgent): string
    {
        if (blank($userAgent)) {
            return 'Unknown device';
        }

        $browser = match (true) {
            Str::contains($userAgent, 'Edg/') => 'Edge',
            Str::contains($userAgent, 'Firefox/') => 'Firefox',
            Str::contains($userAgent, 'Chrome/') => 'Chrome',
            Str::contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            Str::contains($userAgent, 'Windows') => 'Windows',
            Str::contains($userAgent, ['Macintosh', 'Mac OS']) => 'macOS',
            Str::contains($userAgent, ['iPhone', 'iPad']) => 'iOS',
            Str::contains($userAgent, 'Android') => 'Android',
            Str::contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown',
        };

        return $browser.' on '.$platform;
    }

    private function notificationModuleGroup(): ?string
    {
        $routeName = request()->route()?->getName();

        if (is_string($routeName) && Str::startsWith($routeName, 'main.accounting.')) {
            return CompanySite::MODULE_ACCOUNTING;
        }

        if (is_string($routeName) && Str::startsWith($routeName, 'main.human-resources.')) {
            return CompanySite::MODULE_HUMAN_RESOURCES;
        }

        if (is_string($routeName) && Str::startsWith($routeName, 'main.document-management.')) {
            return CompanySite::MODULE_DOCUMENT_MANAGEMENT;
        }

        if (is_string($routeName) && Str::startsWith($routeName, 'main.archiving.')) {
            return CompanySite::MODULE_ARCHIVING;
        }

        $module = request()->route('module');

        return is_string($module) ? $module : null;
    }
}
