@php
    $accountingNotifications ??= [];
    $notificationCount = count($accountingNotifications);
    $unreadNotificationCount = $accountingUnreadNotificationsCount ?? collect($accountingNotifications)->where('is_read', false)->count();
    $notificationShowRoute = match ($notificationModuleGroup ?? null) {
        \App\Models\CompanySite::MODULE_ACCOUNTING => 'main.accounting.notifications.show',
        \App\Models\CompanySite::MODULE_HUMAN_RESOURCES => 'main.human-resources.notifications.show',
        \App\Models\CompanySite::MODULE_DOCUMENT_MANAGEMENT => 'main.document-management.notifications.show',
        \App\Models\CompanySite::MODULE_ARCHIVING => 'main.archiving.notifications.show',
        default => null,
    };
    $notificationIndexRoute = match ($notificationModuleGroup ?? null) {
        \App\Models\CompanySite::MODULE_ACCOUNTING => 'main.accounting.notifications',
        \App\Models\CompanySite::MODULE_HUMAN_RESOURCES => 'main.human-resources.notifications',
        \App\Models\CompanySite::MODULE_DOCUMENT_MANAGEMENT => 'main.document-management.notifications',
        \App\Models\CompanySite::MODULE_ARCHIVING => 'main.archiving.notifications',
        default => null,
    };
@endphp

<div class="notification-menu">
    <button class="icon-button notification-button" type="button" id="notificationButton" aria-label="{{ __('main.notifications') }}" aria-expanded="false" aria-controls="notificationDropdown" title="{{ __('main.notifications') }}">
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if ($unreadNotificationCount > 0)
            <span class="notification-badge">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
        @endif
    </button>
    <div class="notification-dropdown" id="notificationDropdown" aria-labelledby="notificationButton">
        <div class="notification-dropdown-header">
            <strong>{{ __('main.notifications') }}</strong>
            <span>{{ $notificationCount }}</span>
        </div>
        <div class="notification-list">
            @forelse ($accountingNotifications as $notification)
                @if ($notificationShowRoute)
                    <a class="notification-item {{ empty($notification['is_read']) ? 'unread' : '' }}" href="{{ route($notificationShowRoute, [$company, $site, $notification['id']]) }}">
                        <span class="notification-item-icon">
                            <i class="bi {{ $notification['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p>
                                <strong>{{ $notification['actor'] }}</strong>
                                {{ $notification['action'] }}
                                @if (! empty($notification['reference']))
                                    <em>{{ $notification['reference'] }}</em>
                                @endif
                            </p>
                            <small>{{ $notification['time'] }}</small>
                        </div>
                    </a>
                @else
                    <div class="notification-item {{ empty($notification['is_read']) ? 'unread' : '' }}">
                        <span class="notification-item-icon">
                            <i class="bi {{ $notification['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p>
                                <strong>{{ $notification['actor'] }}</strong>
                                {{ $notification['action'] }}
                                @if (! empty($notification['reference']))
                                    <em>{{ $notification['reference'] }}</em>
                                @endif
                            </p>
                            <small>{{ $notification['time'] }}</small>
                        </div>
                    </div>
                @endif
            @empty
                <p class="notification-empty">{{ __('main.no_notifications') }}</p>
            @endforelse
        </div>
        @if ($notificationIndexRoute)
            <a class="notification-view-all" href="{{ route($notificationIndexRoute, [$company, $site]) }}">
                {{ __('main.view_all_notifications') }}
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        @endif
    </div>
</div>
