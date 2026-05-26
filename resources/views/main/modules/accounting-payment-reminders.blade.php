<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.payment_reminders') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.payment-reminders', [$company, $site]);
        $money = fn ($amount, $currency) => number_format((float) $amount, 2, ',', ' ').' '.$currency;
        $totalRecords = $followUps->total();
        $modalTarget = old('modal_target');
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'payment-reminders'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.payment_reminders')])

            <section class="dashboard-content module-dashboard-page accounting-list-page payment-reminders-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.payment_reminders') }}</h1>
                        <p>{{ __('main.payment_reminders_subtitle') }}</p>
                    </div>
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <section class="reminder-metrics">
                    <article class="company-card">
                        <i class="bi bi-wallet2"></i>
                        <span>{{ __('main.total_to_collect') }}</span>
                        <div class="reminder-balance-values">
                            @forelse ($metrics['balances'] as $balance)
                                <strong>{{ $money($balance['amount'], $balance['currency']) }}</strong>
                            @empty
                                <strong>{{ $money(0, $site->currency) }}</strong>
                            @endforelse
                        </div>
                    </article>
                    <article class="company-card">
                        <i class="bi bi-alarm"></i>
                        <span>{{ __('main.overdue_documents') }}</span>
                        <strong>{{ $metrics['overdue'] }}</strong>
                    </article>
                    <article class="company-card">
                        <i class="bi bi-exclamation-diamond"></i>
                        <span>{{ __('main.overdue_over_30_days') }}</span>
                        <strong>{{ $metrics['older_than_30'] }}</strong>
                    </article>
                    <article class="company-card">
                        <i class="bi bi-calendar-check"></i>
                        <span>{{ __('main.pending_payment_promises') }}</span>
                        <strong>{{ $metrics['promises'] }}</strong>
                    </article>
                </section>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="reminderStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="reminderStatusFilter" name="status" class="form-select">
                                    <option value="">{{ __('main.all_statuses') }}</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="reminderCurrencyFilter" class="form-label">{{ __('main.currency') }}</label>
                                <select id="reminderCurrencyFilter" name="currency" class="form-select">
                                    <option value="">{{ __('main.all_currencies') }}</option>
                                    @foreach ($currencies as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['currency'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex justify-content-end gap-2">
                                <a class="modal-cancel" href="{{ $indexRoute }}">{{ __('main.reset_filters') }}</a>
                                <button class="modal-submit" type="submit">{{ __('main.apply_filters') }}</button>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" value="{{ request('search') }}" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $followUps->count() }}</strong> / <strong>{{ $totalRecords }}</strong> {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table reminder-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.source') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="date">{{ __('main.due_date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.days_overdue') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="6" data-sort-type="number">{{ __('main.balance_due') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="7">{{ __('main.reminder_level') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="8">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($followUps as $row)
                                    @php $reminder = $row['reminder']; @endphp
                                    <tr>
                                        <td>{{ ($followUps->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><strong>{{ $row['customer'] }}</strong></td>
                                        <td>{{ $row['source_label'] }}</td>
                                        <td>
                                            <span class="reference-pill">{{ $row['source_reference'] }}</span>
                                            @if ($reminder)<small class="d-block text-muted">{{ $reminder->reference }}</small>@endif
                                        </td>
                                        <td>{{ optional($row['due_date'])->format('d/m/Y') ?: '-' }}</td>
                                        <td>{{ $row['overdue_days'] > 0 ? $row['overdue_days'].' j' : '-' }}</td>
                                        <td class="amount-cell text-end">{{ $money($row['balance'], $row['currency']) }}</td>
                                        <td>{{ $reminder ? ($levelLabels[$reminder->level] ?? $reminder->level) : '-' }}</td>
                                        <td><span class="status-pill reminder-status-{{ $row['row_status'] }}">{{ $statusLabels[$row['row_status']] ?? $row['row_status'] }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($row['document_url'])
                                                    <a class="table-button table-button-print" href="{{ $row['document_url'] }}" target="_blank" title="{{ __('main.open_document') }}" aria-label="{{ __('main.open_document') }}">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                @endif
                                                @if ($permissions['can_create'] && $row['balance'] > 0)
                                                    <button class="table-button table-button-edit" type="button" data-bs-toggle="modal" data-bs-target="#reminderModal{{ $row['source_type'] }}{{ $row['source_id'] }}" title="{{ __('main.create_reminder') }}" aria-label="{{ __('main.create_reminder') }}">
                                                        <i class="bi bi-bell"></i>
                                                    </button>
                                                @endif
                                                @if ($reminder)
                                                    <a class="table-button table-button-print" href="{{ route('main.accounting.payment-reminders.letter', [$company, $site, $reminder]) }}" target="_blank" title="{{ __('main.print_reminder') }}" aria-label="{{ __('main.print_reminder') }}">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                    <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#reminderHistoryModal{{ $reminder->id }}" title="{{ __('main.view_history') }}" aria-label="{{ __('main.view_history') }}">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                    @if ($permissions['can_update'] && $row['balance'] > 0)
                                                        <button class="table-button table-button-confirm" type="button" data-bs-toggle="modal" data-bs-target="#promiseModal{{ $reminder->id }}" title="{{ __('main.record_payment_promise') }}" aria-label="{{ __('main.record_payment_promise') }}">
                                                            <i class="bi bi-calendar-check"></i>
                                                        </button>
                                                        <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#disputeModal{{ $reminder->id }}" title="{{ __('main.record_dispute') }}" aria-label="{{ __('main.record_dispute') }}">
                                                            <i class="bi bi-exclamation-octagon"></i>
                                                        </button>
                                                        @if ($reminder->status !== \App\Models\AccountingPaymentReminder::STATUS_SUSPENDED)
                                                            <form method="POST" action="{{ route('main.accounting.payment-reminders.suspend', [$company, $site, $reminder]) }}">
                                                                @csrf
                                                                <button class="table-button table-button-delete" type="submit" title="{{ __('main.suspend_reminder') }}" aria-label="{{ __('main.suspend_reminder') }}">
                                                                    <i class="bi bi-pause-circle"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="10">{{ __('main.no_payment_reminders') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="10">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($followUps->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $followUps->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $followUps->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell">
                            @if ($followUps->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $followUps->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($followUps->getUrlRange(1, $followUps->lastPage()) as $page => $url)
                                @if ($page === $followUps->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($followUps->hasMorePages())<a href="{{ $followUps->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @foreach ($followUps as $row)
        @php $reminder = $row['reminder']; @endphp
        @if ($row['balance'] > 0)
        <div class="modal fade subscription-modal payment-reminder-modal" id="reminderModal{{ $row['source_type'] }}{{ $row['source_id'] }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content admin-form" method="POST" action="{{ route('main.accounting.payment-reminders.store', [$company, $site]) }}">
                    @csrf
                    <input type="hidden" name="modal_target" value="reminderModal{{ $row['source_type'] }}{{ $row['source_id'] }}">
                    <input type="hidden" name="source_type" value="{{ $row['source_type'] }}">
                    <input type="hidden" name="source_id" value="{{ $row['source_id'] }}">
                    <div class="modal-body">
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button>
                        <h2><i class="bi bi-bell"></i>{{ __('main.create_reminder') }}</h2>
                        <div class="reminder-source-box">
                            <strong>{{ $row['customer'] }}</strong>
                            <span>{{ $row['source_label'] }} {{ $row['source_reference'] }} - {{ $money($row['balance'], $row['currency']) }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.reminder_level') }} *</label>
                                <select name="level" class="form-select @error('level') is-invalid @enderror" required>
                                    @foreach ($levelLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('level', $reminder?->level ?? \App\Models\AccountingPaymentReminder::LEVEL_FRIENDLY) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('level')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.channel') }} *</label>
                                <select name="channel" class="form-select @error('channel') is-invalid @enderror" required>
                                    @foreach ($channelLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('channel', $reminder?->channel ?? \App\Models\AccountingPaymentReminder::CHANNEL_EMAIL) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('channel')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('main.subject') }} *</label>
                                <input name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $reminder?->subject ?? __('main.default_reminder_subject', ['reference' => $row['source_reference']])) }}" placeholder="{{ __('main.subject') }}" required>
                                @error('subject')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('main.message') }} *</label>
                                <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror" placeholder="{{ __('main.reminder_message_placeholder') }}" required>{{ old('message', $reminder?->message) }}</textarea>
                                @error('message')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.next_reminder_date') }}</label>
                                <input name="next_reminder_date" type="date" class="form-control @error('next_reminder_date') is-invalid @enderror" value="{{ old('next_reminder_date', optional($reminder?->next_reminder_date)->format('Y-m-d')) }}">
                                @error('next_reminder_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('main.notes') }}</label>
                                <input name="notes" class="form-control" value="{{ old('notes', $reminder?->notes) }}" placeholder="{{ __('main.notes') }}">
                            </div>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                            <button class="modal-submit" type="submit">{{ __('main.save_and_record_reminder') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if ($reminder)
            <div class="modal fade subscription-modal related-table-modal reminder-history-modal" id="reminderHistoryModal{{ $reminder->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content modal-table-dialog">
                        <div class="modal-body">
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button>
                            <h2><i class="bi bi-clock-history"></i>{{ __('main.reminder_history_title', ['reference' => $reminder->reference]) }}</h2>
                            <div class="modal-table-frame">
                                <table class="company-table modal-data-table">
                                    <thead><tr><th>{{ __('main.date') }}</th><th>{{ __('main.action') }}</th><th>{{ __('main.channel') }}</th><th>{{ __('main.created_by') }}</th></tr></thead>
                                    <tbody>
                                        @forelse ($reminder->actions->sortByDesc('action_at') as $action)
                                            <tr>
                                                <td>{{ optional($action->action_at)->format('d/m/Y H:i') }}</td>
                                                <td>{{ $actionLabels[$action->action_type] ?? $action->action_type }}</td>
                                                <td>{{ $channelLabels[$action->channel] ?? '-' }}</td>
                                                <td>{{ $action->creator?->name ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="4">{{ __('main.no_reminder_history') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($reminder->promises->isNotEmpty())
                                <h3 class="reminder-modal-subtitle">{{ __('main.payment_promises') }}</h3>
                                <div class="modal-table-frame">
                                    <table class="company-table modal-data-table">
                                        <thead><tr><th>{{ __('main.promised_date') }}</th><th class="text-end">{{ __('main.amount') }}</th><th>{{ __('main.status') }}</th></tr></thead>
                                        <tbody>
                                            @foreach ($reminder->promises->sortByDesc('promised_date') as $promise)
                                                <tr>
                                                    <td>{{ optional($promise->promised_date)->format('d/m/Y') }}</td>
                                                    <td class="text-end">{{ $money($promise->amount, $promise->currency) }}</td>
                                                    <td><span class="status-pill promise-status-{{ $promise->status }}">{{ $promiseStatusLabels[$promise->status] ?? $promise->status }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            <div class="modal-actions"><button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button></div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($row['balance'] > 0)
            <div class="modal fade subscription-modal payment-reminder-modal" id="promiseModal{{ $reminder->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content admin-form" method="POST" action="{{ route('main.accounting.payment-reminders.promises.store', [$company, $site, $reminder]) }}">
                        @csrf
                        <input type="hidden" name="modal_target" value="promiseModal{{ $reminder->id }}">
                        <div class="modal-body">
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button>
                            <h2><i class="bi bi-calendar-check"></i>{{ __('main.record_payment_promise') }}</h2>
                            <div class="reminder-source-box"><strong>{{ $row['customer'] }}</strong><span>{{ __('main.balance_due') }} : {{ $money($row['balance'], $row['currency']) }}</span></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.amount') }} *</label>
                                    <input name="amount" type="number" min="0.01" max="{{ $row['balance'] }}" step="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0,00" required>
                                    @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('main.promised_date') }} *</label>
                                    <input name="promised_date" type="date" class="form-control @error('promised_date') is-invalid @enderror" value="{{ old('promised_date') }}" required>
                                    @error('promised_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('main.notes') }}</label>
                                    <textarea name="notes" rows="3" class="form-control" placeholder="{{ __('main.notes') }}">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                                <button class="modal-submit" type="submit">{{ __('main.record_payment_promise') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade subscription-modal payment-reminder-modal" id="disputeModal{{ $reminder->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content admin-form" method="POST" action="{{ route('main.accounting.payment-reminders.dispute', [$company, $site, $reminder]) }}">
                        @csrf
                        <input type="hidden" name="modal_target" value="disputeModal{{ $reminder->id }}">
                        <div class="modal-body">
                            <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button>
                            <h2><i class="bi bi-exclamation-octagon"></i>{{ __('main.record_dispute') }}</h2>
                            <div class="reminder-source-box"><strong>{{ $row['customer'] }}</strong><span>{{ $row['source_reference'] }} - {{ $money($row['balance'], $row['currency']) }}</span></div>
                            <label class="form-label">{{ __('main.dispute_reason') }} *</label>
                            <textarea name="reason" rows="4" class="form-control @error('reason') is-invalid @enderror" placeholder="{{ __('main.dispute_reason_placeholder') }}" required>{{ old('reason', $reminder->status === \App\Models\AccountingPaymentReminder::STATUS_DISPUTED ? $reminder->notes : '') }}</textarea>
                            @error('reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="modal-actions">
                                <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                                <button class="modal-submit" type="submit">{{ __('main.record_dispute') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        @endif
    @endforeach

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    @if ($errors->any() && $modalTarget)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById(@json($modalTarget));
                if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        </script>
    @endif
</body>
</html>
