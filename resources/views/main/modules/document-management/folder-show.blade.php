<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $folder->name }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body document-management-module-body">
    @php
        $totalRecords = $records->total();
        $recordOpenRoute = fn ($record) => match ($record->record_type) {
            \App\Models\DocumentManagementRecord::TYPE_INCOMING => route('main.document-management.incoming', [$company, $site]),
            \App\Models\DocumentManagementRecord::TYPE_OUTGOING => route('main.document-management.outgoing', [$company, $site]),
            \App\Models\DocumentManagementRecord::TYPE_INTERNAL => route('main.document-management.internal', [$company, $site]),
            default => route('main.document-management.dashboard', [$company, $site]),
        };
    @endphp

    <div class="dashboard-shell main-shell accounting-shell document-management-shell" data-theme="light">
        @include('main.modules.document-management.partials.sidebar', ['activeDocumentManagementPage' => 'folders'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.ged_folder_detail') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page accounting-list-page document-management-page">
                <a class="back-link" href="{{ route('main.document-management.folders', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.ged_folders') }}
                </a>

                <section class="ged-folder-detail-head company-card">
                    <span class="ged-folder-detail-icon"><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                    <div>
                        <span class="status-pill ged-folder-status-{{ $folder->status }}">{{ $folderStatusLabels[$folder->status] ?? $folder->status }}</span>
                        <h1>{{ $folder->name }}</h1>
                        <p>{{ $folder->reference }} @if ($folder->category) / {{ $folder->category }} @endif</p>
                        @if ($folder->description)
                            <small>{{ $folder->description }}</small>
                        @endif
                    </div>
                </section>

                <section class="table-tools" aria-label="{{ __('admin.search_tools') }}">
                    <span class="row-count">
                        <strong>{{ $records->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table ged-folder-records-table">
                            <thead>
                                <tr>
                                    <th>{{ __('main.reference') }}</th>
                                    <th>{{ __('main.ged_document') }}</th>
                                    <th>{{ __('main.ged_tracking') }}</th>
                                    <th>{{ __('main.status') }}</th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr>
                                        <td><strong>{{ $record->reference }}</strong></td>
                                        <td class="ged-mail-cell">
                                            <strong>{{ $record->subject }}</strong>
                                            <span>{{ $typeLabels[$record->record_type] ?? $record->record_type }}</span>
                                            <small>{{ $record->sender ?: $record->recipient ?: '-' }}</small>
                                        </td>
                                        <td class="ged-tracking-cell">
                                            <strong>{{ $record->assignee?->name ?? __('main.ged_unassigned') }}</strong>
                                            <span>{{ __('main.ged_due_at') }} : {{ $record->due_at?->format('d/m/Y') ?? '-' }}</span>
                                            <span class="status-pill ged-priority-{{ $record->priority }}">{{ $priorityLabels[$record->priority] ?? $record->priority }}</span>
                                        </td>
                                        <td><span class="status-pill ged-status-{{ $record->status }}">{{ $statusLabels[$record->status] ?? $record->status }}</span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a class="table-button table-button-print" href="{{ $recordOpenRoute($record) }}" aria-label="{{ __('main.ged_open_related_document') }}">
                                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="5">{{ __('main.ged_folder_no_documents') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($records->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $records->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $records->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($records->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $records->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($records->getUrlRange(1, $records->lastPage()) as $page => $url)
                                @if ($page === $records->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($records->hasMorePages())<a href="{{ $records->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
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
