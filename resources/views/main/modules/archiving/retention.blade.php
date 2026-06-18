<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.archive_retention') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
    @php
        $retentionStats = array_merge([
            'rules' => 0,
            'activeRules' => 0,
            'expiringSoon' => 0,
            'expiredRecords' => 0,
            'nextExpiry' => null,
        ], $retentionStats ?? []);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
        @include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'retention'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.archive_retention') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content accounting-list-page archiving-page archive-retention-page">
                <a class="back-link" href="{{ route('main.archiving.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.archive_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.archive_retention') }}</h1>
                        <p>{{ __('main.archive_retention_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#retentionModal">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        {{ __('main.archive_new_retention_rule') }}
                    </button>
                </section>

                @if (session('success'))
                    <div class="flash-toast" role="status" data-autohide="15000">
                        <span class="flash-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress"></span>
                    </div>
                @endif

                <section class="archive-retention-kpis" aria-label="{{ __('admin.indicators') }}">
                    <article>
                        <span><i class="bi bi-list-check" aria-hidden="true"></i></span>
                        <div>
                            <strong>{{ $retentionStats['rules'] }}</strong>
                            <small>{{ __('main.archive_retention_rules') }}</small>
                        </div>
                    </article>
                    <article>
                        <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                        <div>
                            <strong>{{ $retentionStats['activeRules'] }}</strong>
                            <small>{{ __('main.archive_active_retention_rules') }}</small>
                        </div>
                    </article>
                    <article>
                        <span><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                        <div>
                            <strong>{{ $retentionStats['expiringSoon'] }}</strong>
                            <small>{{ __('main.archive_next_90_days') }}</small>
                        </div>
                    </article>
                    <article class="{{ $retentionStats['expiredRecords'] > 0 ? 'is-warning' : '' }}">
                        <span><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                        <div>
                            <strong>{{ $retentionStats['expiredRecords'] }}</strong>
                            <small>{{ __('main.archive_expired_records') }}</small>
                        </div>
                    </article>
                </section>

                <section class="archive-retention-layout">
                    <article class="archive-retention-rules-panel">
                        <div class="hr-panel-header">
                            <div>
                                <span>{{ __('main.archive_governance') }}</span>
                                <h3>{{ __('main.archive_retention_rules') }}</h3>
                            </div>
                            <strong>{{ $rules->count() }} / {{ $rules->total() }} {{ __('main.rows') }}</strong>
                        </div>

                        <div class="archive-retention-rules">
                            @forelse ($rules as $rule)
                                <article class="archive-retention-rule-card">
                                    <div class="archive-retention-rule-icon">
                                        <i class="bi bi-folder-check" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <header>
                                            <div>
                                                <span>{{ __('main.category') }}</span>
                                                <h2>{{ $rule->category }}</h2>
                                            </div>
                                            <span class="status-pill archive-status-{{ $rule->status }}">{{ __('main.status_'.$rule->status) }}</span>
                                        </header>
                                        <p>{{ $rule->description ?: __('main.archive_retention_default_description') }}</p>
                                        <footer>
                                            <span><i class="bi bi-calendar2-week" aria-hidden="true"></i>{{ $rule->retention_years }} {{ __('main.years') }}</span>
                                            <span><i class="bi bi-arrow-repeat" aria-hidden="true"></i>{{ __('main.archive_rule_reused_on_category') }}</span>
                                        </footer>
                                    </div>
                                </article>
                            @empty
                                <div class="module-empty-state">
                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                    <strong>{{ __('main.archive_no_retention_rules') }}</strong>
                                    <span>{{ __('main.archive_retention_rules_empty_text') }}</span>
                                </div>
                            @endforelse
                        </div>

                        @if ($rules->hasPages())
                            <section class="subscriptions-pagination">{{ $rules->links() }}</section>
                        @endif
                    </article>

                    <aside class="archive-retention-watch-panel">
                        <div class="hr-panel-header">
                            <div>
                                <span>{{ __('main.archive_monitoring') }}</span>
                                <h3>{{ __('main.archive_expiring_soon') }}</h3>
                            </div>
                            <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        </div>

                        <div class="archive-retention-next-card">
                            <span>{{ __('main.archive_next_expiry') }}</span>
                            @if ($retentionStats['nextExpiry'])
                                <strong>{{ $retentionStats['nextExpiry']->retention_until?->format('d/m/Y') }}</strong>
                                <small>{{ $retentionStats['nextExpiry']->reference }} &middot; {{ $retentionStats['nextExpiry']->title }}</small>
                            @else
                                <strong>-</strong>
                                <small>{{ __('main.archive_retention_clear_text') }}</small>
                            @endif
                        </div>

                        <div class="archive-retention-expiry-list">
                            @forelse ($expiringRecords as $record)
                                <article>
                                    <span><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $record->title }}</strong>
                                        <small>{{ $record->reference }} &middot; {{ $record->category ?: __('main.not_defined') }}</small>
                                    </div>
                                    <time datetime="{{ $record->retention_until?->toDateString() }}">{{ $record->retention_until?->format('d/m/Y') ?? '-' }}</time>
                                </article>
                            @empty
                                <div class="module-empty-state module-empty-state-small">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                                    <strong>{{ __('main.archive_no_expiring_records') }}</strong>
                                    <span>{{ __('main.archive_retention_clear_text') }}</span>
                                </div>
                            @endforelse
                        </div>
                    </aside>
                </section>
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal archive-retention-modal" id="retentionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="bi bi-hourglass-split" aria-hidden="true"></i>{{ __('main.archive_new_retention_rule') }}</h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('main.archiving.retention.store', [$company, $site]) }}" class="admin-form">
                    @csrf
                    <div class="modal-body">
                        <div class="modal-fields two-columns">
                            <label>
                                {{ __('main.category') }} *
                                <input class="form-control" name="category" placeholder="{{ __('main.archive_category_placeholder') }}" required>
                            </label>
                            <label>
                                {{ __('main.duration') }} *
                                <input class="form-control" type="number" min="1" name="retention_years" value="5" required>
                            </label>
                            <label>
                                {{ __('main.status') }} *
                                <select class="form-select" name="status" required>
                                    <option value="active">{{ __('main.status_active') }}</option>
                                    <option value="inactive">{{ __('main.status_inactive') }}</option>
                                </select>
                            </label>
                        </div>
                        <label>
                            {{ __('main.description') }}
                            <textarea class="form-control" name="description" rows="3" placeholder="{{ __('main.archive_retention_description_placeholder') }}"></textarea>
                        </label>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit">{{ __('main.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>
