<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.tasks') }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $indexRoute = route('main.accounting.tasks', [$company, $site]);
        $totalRecords = $tasks->total();
        $modalTarget = old('modal_target');
        $sourceKey = fn ($task) => $task->source_type && $task->source_id ? $task->source_type.':'.$task->source_id : '';
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => 'tasks'])

        <main class="dashboard-main">
            @include('main.modules.partials.accounting-topbar', ['title' => __('main.tasks')])

            <section class="dashboard-content module-dashboard-page accounting-list-page accounting-tasks-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.tasks') }}</h1>
                        <p>{{ __('main.tasks_subtitle') }}</p>
                    </div>
                    @if ($permissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#taskCreateModal">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            {{ __('main.new_task') }}
                        </button>
                    @endif
                </section>

                @if (session('success'))
                    <div class="flash-toast {{ session('toast_type') === 'danger' ? 'flash-toast-danger' : '' }}" role="status" aria-live="polite" data-autohide="15000">
                        <span class="flash-icon"><i class="bi {{ session('toast_type') === 'danger' ? 'bi-exclamation-triangle' : 'bi-check2-circle' }}" aria-hidden="true"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <span class="flash-progress" aria-hidden="true"></span>
                    </div>
                @endif

                <section class="reminder-metrics task-metrics">
                    <article class="company-card">
                        <i class="bi bi-list-check"></i>
                        <span>{{ __('main.open_tasks') }}</span>
                        <strong>{{ $metrics['open'] }}</strong>
                    </article>
                    <article class="company-card">
                        <i class="bi bi-alarm"></i>
                        <span>{{ __('main.overdue_tasks') }}</span>
                        <strong>{{ $metrics['overdue'] }}</strong>
                    </article>
                    <article class="company-card">
                        <i class="bi bi-calendar-event"></i>
                        <span>{{ __('main.tasks_due_today') }}</span>
                        <strong>{{ $metrics['due_today'] }}</strong>
                    </article>
                    <article class="company-card">
                        <i class="bi bi-exclamation-diamond"></i>
                        <span>{{ __('main.urgent_tasks') }}</span>
                        <strong>{{ $metrics['urgent'] }}</strong>
                    </article>
                    <article class="company-card">
                        <i class="bi bi-check2-circle"></i>
                        <span>{{ __('main.tasks_completed_this_week') }}</span>
                        <strong>{{ $metrics['completed_this_week'] }}</strong>
                    </article>
                </section>

                <section class="company-card receipt-filter-card">
                    <form method="GET" action="{{ $indexRoute }}" class="receipt-filter-form">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="taskStatusFilter" class="form-label">{{ __('main.status') }}</label>
                                <select id="taskStatusFilter" name="status" class="form-select">
                                    <option value="">{{ __('main.all_statuses') }}</option>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="taskPriorityFilter" class="form-label">{{ __('main.priority') }}</label>
                                <select id="taskPriorityFilter" name="priority" class="form-select">
                                    <option value="">{{ __('main.all_priorities') }}</option>
                                    @foreach ($priorityLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['priority'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="taskAssigneeFilter" class="form-label">{{ __('main.task_assignee') }}</label>
                                <select id="taskAssigneeFilter" name="assigned_to" class="form-select">
                                    <option value="">{{ __('main.all_assignees') }}</option>
                                    @foreach ($assignees as $assignee)
                                        <option value="{{ $assignee->id }}" @selected($filters['assigned_to'] === $assignee->id)>{{ $assignee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex justify-content-end gap-2">
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
                        <strong id="visibleCount">{{ $tasks->count() }}</strong> / <strong>{{ $totalRecords }}</strong> {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table task-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="1">{{ __('main.reference') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="2">{{ __('main.tasks') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="3">{{ __('main.task_assignee') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="4" data-sort-type="date">{{ __('main.task_due_date') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="5">{{ __('main.priority') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sort-index="6">{{ __('main.status') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tasks as $task)
                                    <tr>
                                        <td>{{ ($tasks->firstItem() ?? 1) + $loop->index }}</td>
                                        <td><span class="reference-pill">{{ $task->reference }}</span></td>
                                        <td>
                                            <strong>{{ $task->title }}</strong>
                                            <small class="d-block text-muted">{{ $typeLabels[$task->type] ?? $task->type }}{{ $task->source_reference ? ' - '.$task->source_reference : '' }}</small>
                                        </td>
                                        <td>{{ $task->assignee?->name ?? __('main.unassigned') }}</td>
                                        <td data-sort-value="{{ $task->due_date?->format('Y-m-d') }}">{{ $task->due_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td><span class="status-pill task-priority-{{ $task->priority }}">{{ $priorityLabels[$task->priority] ?? $task->priority }}</span></td>
                                        <td>
                                            <span class="status-pill task-status-{{ $task->status }}">{{ $statusLabels[$task->status] ?? $task->status }}</span>
                                            @if ($task->is_automatic)<small class="d-block text-muted">{{ __('main.automatic_task') }}</small>@endif
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                @if ($task->document_url)
                                                    <a class="table-button table-button-print" href="{{ $task->document_url }}" target="_blank" title="{{ __('main.open_document') }}" aria-label="{{ __('main.open_document') }}"><i class="bi bi-eye"></i></a>
                                                @endif
                                                <button class="table-button table-button-history" type="button" data-bs-toggle="modal" data-bs-target="#taskHistoryModal{{ $task->id }}" title="{{ __('main.view_history') }}" aria-label="{{ __('main.view_history') }}"><i class="bi bi-clock-history"></i></button>
                                                @if ($permissions['can_update'])
                                                    <button class="table-button table-button-edit" type="button" data-bs-toggle="modal" data-bs-target="#taskEditModal{{ $task->id }}" title="{{ __('admin.edit') }}" aria-label="{{ __('admin.edit') }}"><i class="bi bi-pencil"></i></button>
                                                    @if ($task->status !== \App\Models\AccountingTask::STATUS_COMPLETED)
                                                        <form method="POST" action="{{ route('main.accounting.tasks.complete', [$company, $site, $task]) }}">
                                                            @csrf
                                                            <button class="table-button table-button-confirm" type="submit" title="{{ __('main.complete_task') }}" aria-label="{{ __('main.complete_task') }}"><i class="bi bi-check2"></i></button>
                                                        </form>
                                                    @endif
                                                @endif
                                                @if ($permissions['can_delete'] && ! $task->is_automatic)
                                                    <form method="POST" action="{{ route('main.accounting.tasks.destroy', [$company, $site, $task]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="table-button table-button-delete" data-delete-trigger data-delete-title="{{ __('admin.delete') }}" data-delete-text="{{ $task->title }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}" title="{{ __('admin.delete') }}" aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="8">{{ __('main.no_tasks') }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="8">{{ __('admin.no_results') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($tasks->hasPages())
                    <section class="subscriptions-pagination" aria-label="{{ __('admin.pagination') }}">
                        <span>{{ __('admin.showing') }} <strong>{{ $tasks->firstItem() ?? 0 }}</strong> {{ __('admin.to') }} <strong>{{ $tasks->lastItem() ?? 0 }}</strong> {{ __('admin.on') }} <strong>{{ $totalRecords }}</strong></span>
                        <nav class="pagination-shell" aria-label="{{ __('admin.pagination') }}">
                            @if ($tasks->onFirstPage())<span class="disabled">{{ __('admin.previous') }}</span>@else<a href="{{ $tasks->previousPageUrl() }}">{{ __('admin.previous') }}</a>@endif
                            @foreach ($tasks->getUrlRange(1, $tasks->lastPage()) as $page => $url)
                                @if ($page === $tasks->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                            @endforeach
                            @if ($tasks->hasMorePages())<a href="{{ $tasks->nextPageUrl() }}">{{ __('admin.next') }}</a>@else<span class="disabled">{{ __('admin.next') }}</span>@endif
                        </nav>
                    </section>
                @endif
            </section>
        </main>
    </div>

    @if ($permissions['can_create'])
        <div class="modal fade subscription-modal task-modal" id="taskCreateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content admin-form" method="POST" action="{{ route('main.accounting.tasks.store', [$company, $site]) }}">
                    @csrf
                    <input type="hidden" name="modal_target" value="taskCreateModal">
                    @include('main.modules.partials.accounting-task-form', ['task' => null, 'formTarget' => 'taskCreateModal'])
                </form>
            </div>
        </div>
    @endif

    @foreach ($tasks as $task)
        @if ($permissions['can_update'])
            <div class="modal fade subscription-modal task-modal" id="taskEditModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content admin-form" method="POST" action="{{ route('main.accounting.tasks.update', [$company, $site, $task]) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="modal_target" value="taskEditModal{{ $task->id }}">
                        @include('main.modules.partials.accounting-task-form', ['task' => $task, 'formTarget' => 'taskEditModal'.$task->id])
                    </form>
                </div>
            </div>
        @endif

        <div class="modal fade subscription-modal related-table-modal" id="taskHistoryModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-table-dialog">
                    <div class="modal-body" data-sales-payments-table>
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button>
                        <h2><i class="bi bi-clock-history"></i>{{ __('main.task_history_title', ['reference' => $task->reference]) }}</h2>
                        <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                            <label class="search-box"><i class="bi bi-search"></i><input type="search" placeholder="{{ __('admin.search') }}" autocomplete="off" data-sales-payments-search></label>
                            <span class="row-count"><strong data-sales-payments-visible-count>{{ $task->activities->count() }}</strong> / <strong data-sales-payments-total-count>{{ $task->activities->count() }}</strong> {{ __('admin.rows') }}</span>
                        </section>
                        <div class="modal-table-frame">
                            <table class="company-table modal-data-table">
                                <thead><tr>
                                    <th><button class="table-sort" type="button" data-sales-payments-sort="0" data-sort-type="number"># <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sales-payments-sort="1">{{ __('main.action') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sales-payments-sort="2">{{ __('main.status') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th><button class="table-sort" type="button" data-sales-payments-sort="3" data-sort-type="date">{{ __('main.date') }} <i class="bi bi-arrow-down-up"></i></button></th>
                                    <th>{{ __('main.task_assignee') }}</th>
                                </tr></thead>
                                <tbody data-sales-payments-body>
                                    @foreach ($task->activities->sortByDesc('created_at')->values() as $activity)
                                        <tr data-payment-row>
                                            <td data-sort-value="{{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                            <td>{{ $activityLabels[$activity->action_type] ?? $activity->action_type }}</td>
                                            <td>{{ $statusLabels[$activity->to_status] ?? '-' }}</td>
                                            <td data-sort-value="{{ $activity->created_at?->format('Y-m-d H:i:s') }}">{{ $activity->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $activity->creator?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="modal-table-empty" data-sales-payments-empty @if($task->activities->isNotEmpty()) hidden @endif>{{ __('main.no_task_history') }}</p>
                        </div>
                        <section class="subscriptions-pagination modal-table-pagination" data-sales-payments-pagination data-previous-label="{{ __('admin.previous') }}" data-next-label="{{ __('admin.next') }}" data-showing-label="{{ __('admin.showing') }}" data-to-label="{{ __('admin.to') }}" data-on-label="{{ __('admin.on') }}" hidden aria-label="{{ __('admin.pagination') }}">
                            <span data-sales-payments-pagination-count></span>
                            <nav class="pagination-shell" data-sales-payments-pagination-nav></nav>
                        </section>
                        <div class="modal-actions"><button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button></div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>{!! file_get_contents(resource_path('js/main/modal-tables.js')) !!}</script>
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
