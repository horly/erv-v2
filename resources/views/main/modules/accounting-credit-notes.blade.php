<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.credit_notes') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalCreditNotes = $creditNotes->total();
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'credit-notes'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.credit_notes')])

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.credit_notes') }}</h1>
                        <p>{{ __('main.credit_notes_subtitle') }}</p>
                    </div>
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-trash3' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <label class="search-box">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="companySearch" placeholder="{{ __('admin.search') }}" autocomplete="off">
                    </label>
                    <span class="row-count">
                        <strong id="visibleCount">{{ $creditNotes->count() }}</strong>
                        /
                        <strong>{{ $totalCreditNotes }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.sales_invoice') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.customer') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4">{{ __('main.date') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end"><button class="table-sort" type="button" data-sort-index="5" data-sort-type="number">{{ __('main.total_ttc') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($creditNotes as $creditNote)
                                    <tr>
                                        <td>{{ ($creditNotes->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $creditNote->reference }}</span></td>
                                        <td>{{ $creditNote->salesInvoice?->reference ?? '-' }}</td>
                                        <td>{{ $creditNote->client?->display_name ?? '-' }}</td>
                                        <td>{{ optional($creditNote->credit_date)->format('d/m/Y') }}</td>
                                        <td class="amount-cell text-end" data-sort-value="{{ $creditNote->total_ttc }}">{{ number_format((float) $creditNote->total_ttc, 2, ',', ' ') }} {{ $creditNote->currency }}</td>
                                        <td><span class="status-pill credit-note-status-{{ $creditNote->status }}">{{ $statusLabels[$creditNote->status] ?? $creditNote->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-print" href="{{ route('main.accounting.credit-notes.print', [$company, $site, $creditNote]) }}" target="_blank" rel="noopener" aria-label="{{ __('main.print_pdf') }}" title="{{ __('main.print_pdf') }}">
                                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                                </a>
                                                @if ($creditNotePermissions['can_update'] && $creditNote->isDraft())
                                                    <form method="POST" action="{{ route('main.accounting.credit-notes.validate', [$company, $site, $creditNote]) }}">
                                                        @csrf
                                                        <button class="table-button table-button-edit" type="submit" aria-label="{{ __('main.validate_credit_note') }}" title="{{ __('main.validate_credit_note') }}">
                                                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($creditNotePermissions['can_update'] && $creditNote->isValidated())
                                                    <form method="POST" action="{{ route('main.accounting.credit-notes.cancel', [$company, $site, $creditNote]) }}">
                                                        @csrf
                                                        <button class="table-button table-button-delete" type="submit" aria-label="{{ __('main.cancel_credit_note') }}" title="{{ __('main.cancel_credit_note') }}">
                                                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($creditNotePermissions['can_delete'] && $creditNote->isDraft())
                                                    <form method="POST" action="{{ route('main.accounting.credit-notes.destroy', [$company, $site, $creditNote]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_credit_note_title') }}" data-delete-text="{{ __('main.delete_credit_note_text', ['reference' => $creditNote->reference]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_credit_notes') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($creditNotes->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $creditNotes->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $creditNotes->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalCreditNotes }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($creditNotes->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $creditNotes->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($creditNotes->getUrlRange(1, $creditNotes->lastPage()) as $page => $url)
                                @if ($page === $creditNotes->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($creditNotes->hasMorePages())<a href="{{ $creditNotes->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>
