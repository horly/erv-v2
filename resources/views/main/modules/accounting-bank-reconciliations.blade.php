<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.bank_reconciliation') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $reconciliationRoute = route('main.accounting.bank-reconciliations', [$company, $site]);
        $currency = $activeReconciliation?->currency ?: ($site->currency ?: 'CDF');
        $amount = fn ($value, $code = null) => number_format((float) $value, 2, ',', ' ').' '.($code ?: $currency);
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'bank-reconciliations'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.bank_reconciliation')])

            <section class="dashboard-content module-dashboard-page accounting-list-page bank-reconciliation-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.bank_reconciliation') }}</h1>
                        <p>{{ __('main.bank_reconciliation_subtitle') }}</p>
                    </div>
                    @if ($permissions['can_create'] && $bankMethods->isNotEmpty())
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#newReconciliationModal">
                            <i class="bi bi-bank" aria-hidden="true"></i>
                            {{ __('main.new_bank_reconciliation') }}
                        </button>
                    @endif
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-exclamation-circle' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                @if ($bankMethods->isEmpty())
                    <div class="modal-total-strip bank-reconciliation-info">
                        <span><i class="bi bi-info-circle" aria-hidden="true"></i> {{ __('main.bank_reconciliation_no_bank_account') }}</span>
                        <a href="{{ route('main.accounting.payment-methods', [$company, $site]) }}">{{ __('main.payment_methods') }}</a>
                    </div>
                @endif

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $reconciliations->count() }}</strong>
                        /
                        <strong>{{ $reconciliations->total() }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table bank-reconciliation-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.bank_account') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.period') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="4" data-sort-type="number">{{ __('main.statement_closing_balance') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort justify-content-end" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.difference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reconciliations as $reconciliation)
                                    <tr>
                                        <td>{{ ($reconciliations->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><strong>{{ $reconciliation->reference }}</strong></td>
                                        <td>{{ $reconciliation->paymentMethod?->name ?? '-' }}</td>
                                        <td>{{ optional($reconciliation->period_start)->format('d/m/Y') }} - {{ optional($reconciliation->period_end)->format('d/m/Y') }}</td>
                                        <td class="text-end" data-sort-value="{{ $reconciliation->statement_closing_balance }}">{{ $amount($reconciliation->statement_closing_balance, $reconciliation->currency) }}</td>
                                        <td class="text-end {{ abs((float) $reconciliation->difference) > 0.009 ? 'bank-amount-danger' : 'bank-amount-success' }}" data-sort-value="{{ $reconciliation->difference }}">{{ $amount($reconciliation->difference, $reconciliation->currency) }}</td>
                                        <td><span class="status-pill bank-status-{{ $reconciliation->status }}">{{ $reconciliationStatusLabels[$reconciliation->status] ?? $reconciliation->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-edit" href="{{ $reconciliationRoute }}?reconciliation={{ $reconciliation->id }}" aria-label="{{ __('main.open_reconciliation') }}" title="{{ __('main.open_reconciliation') }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                                <a class="table-button table-button-print" href="{{ route('main.accounting.bank-reconciliations.report', [$company, $site, $reconciliation]) }}" target="_blank" aria-label="{{ __('main.print_report') }}" title="{{ __('main.print_report') }}"><i class="bi bi-printer" aria-hidden="true"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_bank_reconciliations') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($reconciliations->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $reconciliations->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $reconciliations->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $reconciliations->total() }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($reconciliations->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $reconciliations->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($reconciliations->getUrlRange(1, $reconciliations->lastPage()) as $page => $url)
                                @if ($page === $reconciliations->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($reconciliations->hasMorePages())<a href="{{ $reconciliations->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif

                @if ($activeReconciliation)
                    <section class="bank-workspace">
                        <div class="bank-workspace-header">
                            <div>
                                <h2>{{ __('main.reconciliation_workspace') }} {{ $activeReconciliation->reference }}</h2>
                                <p>{{ $activeReconciliation->paymentMethod?->name }} | {{ optional($activeReconciliation->period_start)->format('d/m/Y') }} - {{ optional($activeReconciliation->period_end)->format('d/m/Y') }}</p>
                            </div>
                            <span class="status-pill bank-status-{{ $activeReconciliation->status }}">{{ $reconciliationStatusLabels[$activeReconciliation->status] ?? $activeReconciliation->status }}</span>
                        </div>

                        <div class="bank-reconciliation-metrics">
                            <article><span>{{ __('main.statement_opening_balance') }}</span><strong>{{ $amount($activeReconciliation->statement_opening_balance) }}</strong></article>
                            <article><span>{{ __('main.statement_closing_balance') }}</span><strong>{{ $amount($activeReconciliation->statement_closing_balance) }}</strong></article>
                            <article><span>{{ __('main.erp_closing_balance') }}</span><strong>{{ $amount($activeReconciliation->erp_closing_balance) }}</strong></article>
                            <article class="{{ abs((float) $activeReconciliation->difference) > 0.009 ? 'has-difference' : 'is-balanced' }}"><span>{{ __('main.difference') }}</span><strong>{{ $amount($activeReconciliation->difference) }}</strong></article>
                        </div>

                        @if ($activeReconciliation->status !== \App\Models\AccountingBankReconciliation::STATUS_CLOSED && $permissions['can_create'])
                            <div class="bank-input-grid">
                                <form class="company-card bank-import-card" method="POST" action="{{ route('main.accounting.bank-reconciliations.import', [$company, $site, $activeReconciliation]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <h3><i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>{{ __('main.import_bank_statement') }}</h3>
                                    <p>{{ __('main.bank_statement_csv_help') }}</p>
                                    <label for="statement_file" class="form-label">{{ __('main.csv_file') }} *</label>
                                    <input id="statement_file" class="form-control @error('statement_file') is-invalid @enderror" name="statement_file" type="file" accept=".csv,.txt" required>
                                    @error('statement_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    <button class="modal-submit" type="submit"><i class="bi bi-upload" aria-hidden="true"></i>{{ __('main.import') }}</button>
                                </form>

                                <form class="company-card bank-line-form" method="POST" action="{{ route('main.accounting.bank-reconciliations.lines.store', [$company, $site, $activeReconciliation]) }}">
                                    @csrf
                                    <h3><i class="bi bi-plus-circle" aria-hidden="true"></i>{{ __('main.manual_statement_line') }}</h3>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="transaction_date" class="form-label">{{ __('main.date') }} *</label>
                                            <input id="transaction_date" name="transaction_date" type="date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ old('transaction_date') }}" required>
                                            @error('transaction_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="bank_direction" class="form-label">{{ __('main.direction') }} *</label>
                                            <select id="bank_direction" name="direction" class="form-select @error('direction') is-invalid @enderror" required>
                                                @foreach ($directionLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('direction') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('direction')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="bank_amount" class="form-label">{{ __('main.amount') }} *</label>
                                            <input id="bank_amount" name="amount" type="number" min="0.01" step="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0,00" required>
                                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-5">
                                            <label for="bank_reference" class="form-label">{{ __('main.reference') }}</label>
                                            <input id="bank_reference" name="bank_reference" class="form-control" value="{{ old('bank_reference') }}" placeholder="{{ __('main.bank_reference_placeholder') }}">
                                        </div>
                                        <div class="col-md-7">
                                            <label for="bank_description" class="form-label">{{ __('main.description') }} *</label>
                                            <input id="bank_description" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="{{ __('main.bank_description_placeholder') }}" required>
                                            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <button class="modal-submit" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i>{{ __('main.add_statement_line') }}</button>
                                </form>
                            </div>
                        @endif

                        <section class="company-card bank-lines-card">
                            <div class="bank-lines-heading">
                                <h3>{{ __('main.bank_statement_lines') }}</h3>
                                <span>{{ $activeReconciliation->lines->count() }} {{ __('admin.rows') }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="company-table bank-lines-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('main.date') }}</th>
                                            <th>{{ __('main.reference') }}</th>
                                            <th>{{ __('main.description') }}</th>
                                            <th>{{ __('main.direction') }}</th>
                                            <th class="text-end">{{ __('main.amount') }}</th>
                                            <th>{{ __('main.status') }}</th>
                                            <th>{{ __('main.erp_correspondence') }}</th>
                                            <th class="text-end">{{ __('admin.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($activeReconciliation->lines->sortByDesc('transaction_date') as $line)
                                            @php
                                                $match = $line->matches->first();
                                                $candidates = $availableMovements->filter(fn ($movement) => $movement->direction === $line->direction && abs((float) $movement->amount - (float) $line->amount) < 0.01);
                                            @endphp
                                            <tr>
                                                <td>{{ optional($line->transaction_date)->format('d/m/Y') }}</td>
                                                <td>{{ $line->bank_reference ?: '-' }}</td>
                                                <td>{{ $line->description }}</td>
                                                <td>{{ $directionLabels[$line->direction] ?? $line->direction }}</td>
                                                <td class="text-end">{{ $amount($line->amount) }}</td>
                                                <td><span class="status-pill bank-line-status-{{ $line->status }}">{{ $lineStatusLabels[$line->status] ?? $line->status }}</span></td>
                                                <td>
                                                    @if ($match?->treasuryMovement)
                                                        <strong>{{ $match->treasuryMovement->reference }}</strong><br>
                                                        <small>{{ $match->treasuryMovement->label }}</small>
                                                    @elseif ($line->status === \App\Models\AccountingBankStatementLine::STATUS_IGNORED)
                                                        {{ __('main.bank_line_justified_ignored') }}
                                                    @else
                                                        <form class="bank-match-form" method="POST" action="{{ route('main.accounting.bank-reconciliations.lines.match', [$company, $site, $activeReconciliation, $line]) }}">
                                                            @csrf
                                                            <select name="treasury_movement_id" class="form-select" aria-label="{{ __('main.select_erp_movement') }}">
                                                                <option value="">{{ __('main.select_erp_movement') }}</option>
                                                                @foreach ($candidates as $movement)
                                                                    <option value="{{ $movement->id }}">{{ $movement->reference }} - {{ optional($movement->movement_date)->format('d/m/Y') }} - {{ $movement->label }}</option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" class="table-button table-button-edit" title="{{ __('main.match') }}"><i class="bi bi-link-45deg" aria-hidden="true"></i></button>
                                                        </form>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($activeReconciliation->status !== \App\Models\AccountingBankReconciliation::STATUS_CLOSED && $permissions['can_update'])
                                                        <div class="bank-line-actions">
                                                            @if ($match)
                                                                <form method="POST" action="{{ route('main.accounting.bank-reconciliations.lines.unmatch', [$company, $site, $activeReconciliation, $line]) }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="table-button table-button-delete" type="submit" title="{{ __('main.unmatch') }}"><i class="bi bi-link-45deg" aria-hidden="true"></i></button>
                                                                </form>
                                                            @elseif ($line->status === \App\Models\AccountingBankStatementLine::STATUS_UNMATCHED)
                                                                <form class="bank-adjustment-form" method="POST" action="{{ route('main.accounting.bank-reconciliations.lines.adjustment', [$company, $site, $activeReconciliation, $line]) }}">
                                                                    @csrf
                                                                    <input class="form-control" name="adjustment_label" placeholder="{{ __('main.adjustment_label_placeholder') }}" required>
                                                                    <button type="submit" class="table-button table-button-edit" title="{{ __('main.create_adjustment') }}"><i class="bi bi-plus-square" aria-hidden="true"></i></button>
                                                                </form>
                                                                <form method="POST" action="{{ route('main.accounting.bank-reconciliations.lines.ignore', [$company, $site, $activeReconciliation, $line]) }}">
                                                                    @csrf
                                                                    <button type="submit" class="table-button" title="{{ __('main.ignore_line') }}"><i class="bi bi-eye-slash" aria-hidden="true"></i></button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-row"><td colspan="8">{{ __('main.no_bank_statement_lines') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        @if ($activeReconciliation->status !== \App\Models\AccountingBankReconciliation::STATUS_CLOSED)
                            <div class="bank-close-panel">
                                <span><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('main.bank_close_notice') }}</span>
                                @if ($user->isAdmin())
                                    <form method="POST" action="{{ route('main.accounting.bank-reconciliations.close', [$company, $site, $activeReconciliation]) }}">
                                        @csrf
                                        <button class="modal-submit" type="submit">{{ __('main.close_reconciliation') }}</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal bank-reconciliation-modal" id="newReconciliationModal" tabindex="-1" aria-labelledby="newReconciliationLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form" method="POST" action="{{ route('main.accounting.bank-reconciliations.store', [$company, $site]) }}">
                @csrf
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="newReconciliationLabel"><i class="bi bi-bank" aria-hidden="true"></i>{{ __('main.new_bank_reconciliation') }}</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="payment_method_id" class="form-label">{{ __('main.bank_account') }} *</label>
                            <select id="payment_method_id" name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" required>
                                <option value="">{{ __('main.select_bank_account') }}</option>
                                @foreach ($bankMethods as $method)
                                    <option value="{{ $method->id }}" @selected(old('payment_method_id') == $method->id)>{{ $method->name }} ({{ $method->currency_code }})</option>
                                @endforeach
                            </select>
                            @error('payment_method_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="period_start" class="form-label">{{ __('main.date_from') }} *</label>
                            <input id="period_start" name="period_start" type="date" class="form-control @error('period_start') is-invalid @enderror" value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}" required>
                            @error('period_start')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="period_end" class="form-label">{{ __('main.date_to') }} *</label>
                            <input id="period_end" name="period_end" type="date" class="form-control @error('period_end') is-invalid @enderror" value="{{ old('period_end', now()->format('Y-m-d')) }}" required>
                            @error('period_end')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="statement_opening_balance" class="form-label">{{ __('main.statement_opening_balance') }} *</label>
                            <input id="statement_opening_balance" name="statement_opening_balance" type="number" step="0.01" class="form-control @error('statement_opening_balance') is-invalid @enderror" value="{{ old('statement_opening_balance', '0.00') }}" required>
                            @error('statement_opening_balance')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="statement_closing_balance" class="form-label">{{ __('main.statement_closing_balance') }} *</label>
                            <input id="statement_closing_balance" name="statement_closing_balance" type="number" step="0.01" class="form-control @error('statement_closing_balance') is-invalid @enderror" value="{{ old('statement_closing_balance', '0.00') }}" required>
                            @error('statement_closing_balance')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="reconciliation_notes" class="form-label">{{ __('main.notes') }}</label>
                            <textarea id="reconciliation_notes" name="notes" class="form-control" rows="2" placeholder="{{ __('main.reconciliation_notes_placeholder') }}">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit">{{ __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($errors->hasAny(['payment_method_id', 'period_start', 'period_end', 'statement_opening_balance', 'statement_closing_balance']))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('newReconciliationModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>
