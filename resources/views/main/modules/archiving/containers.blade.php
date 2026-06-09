<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.archive_containers') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
<div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
    @include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'containers'])
    <main class="dashboard-main">
        <header class="dashboard-topbar"><div><h1>{{ __('main.archive_containers') }}</h1><p>{{ $company->name }} / {{ $site->name }}</p></div>@include('main.modules.partials.accounting-header-actions')</header>
        <section class="dashboard-content accounting-list-page archiving-page">
            <a class="back-link" href="{{ route('main.archiving.dashboard', [$company, $site]) }}"><i class="bi bi-arrow-left"></i>{{ __('main.archive_dashboard') }}</a>
            <section class="page-heading"><div><h1>{{ __('main.archive_containers') }}</h1><p>{{ __('main.archive_containers_subtitle') }}</p></div><button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#containerModal"><i class="bi bi-plus-lg"></i>{{ __('main.archive_new_container') }}</button></section>
            @if (session('success'))<div class="flash-toast" role="status" data-autohide="15000"><span class="flash-icon"><i class="bi bi-check2-circle"></i></span><span>{{ session('success') }}</span><button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button><span class="flash-progress"></span></div>@endif
            @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <section class="table-tools"><form class="search-box" method="GET"><i class="bi bi-search"></i><input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.archive_search_container') }}"></form><span><strong>{{ $containers->count() }}</strong> / <strong>{{ $containers->total() }}</strong> {{ __('main.rows') }}</span></section>
            <article class="company-card"><div class="company-table-wrap"><table class="company-table" data-sortable-table>
                <thead><tr><th><button class="table-sort" type="button">{{ __('main.reference') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" type="button">{{ __('main.archive_container') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" type="button">{{ __('main.archive_box') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" type="button">{{ __('main.category') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" type="button">{{ __('main.documents') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" type="button">{{ __('main.status') }} <i class="bi bi-arrow-down-up"></i></button></th><th>{{ __('main.actions') }}</th></tr></thead>
                <tbody>@forelse ($containers as $container)<tr><td>{{ $container->reference }}</td><td><strong>{{ $container->title }}</strong><br><small>{{ $container->period_label ?: '-' }}</small></td><td><strong>{{ $container->box?->name ?? '-' }}</strong><br><small>{{ $container->box?->physical_path ?? '-' }}</small></td><td>{{ $container->category ?: '-' }}<br><small>{{ $container->owner_service ?: '-' }}</small></td><td>{{ $container->records_count }}</td><td><span class="status-pill archive-status-{{ $container->status }}">{{ $containerStatusLabels[$container->status] ?? $container->status }}</span></td><td><div class="table-actions"><button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#containerModal" data-container-mode="edit" data-container-action="{{ route('main.archiving.containers.update', [$company, $site, $container]) }}" data-container-box="{{ $container->box?->physical_path }}" data-container-title="{{ $container->title }}" data-container-category="{{ $container->category }}" data-container-owner-service="{{ $container->owner_service }}" data-container-period-label="{{ $container->period_label }}" data-container-confidentiality-level="{{ $container->confidentiality_level }}" data-container-capacity="{{ $container->capacity }}" data-container-status="{{ $container->status }}" data-container-description="{{ $container->description }}" aria-label="{{ __('admin.edit') }}"><i class="bi bi-pencil"></i></button><form method="POST" action="{{ route('main.archiving.containers.destroy', [$company, $site, $container]) }}" onsubmit="return confirm('{{ __('main.archive_delete_container_confirm') }}')">@csrf @method('DELETE')<button type="submit" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash"></i></button></form></div></td></tr>@empty<tr><td colspan="7" class="text-center text-muted">{{ __('main.archive_no_containers') }}</td></tr>@endforelse</tbody>
            </table></div></article>
            @if ($containers->hasPages())<section class="subscriptions-pagination"><span>{{ __('admin.showing') }} <strong>{{ $containers->firstItem() }}</strong> {{ __('admin.to') }} <strong>{{ $containers->lastItem() }}</strong> {{ __('admin.on') }} <strong>{{ $containers->total() }}</strong></span>{{ $containers->links() }}</section>@endif
        </section>
    </main>
</div>
<div class="modal fade subscription-modal accounting-proforma-modal archive-container-modal" id="containerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 id="containerModalTitle"><i class="bi bi-folder2-open"></i> {{ __('main.archive_new_container') }}</h2><button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
        <form method="POST" action="{{ route('main.archiving.containers.store', [$company, $site]) }}" class="admin-form" data-container-create-action="{{ route('main.archiving.containers.store', [$company, $site]) }}">@csrf
            <input type="hidden" name="_method" id="containerHttpMethod" value="PUT" disabled>
            <div class="modal-body">
                <div class="modal-fields two-columns">
                    <label>{{ __('main.archive_box') }} *<select class="form-select" name="archive_box_id" required data-container-box-select>@foreach ($boxOptions as $option)<option value="{{ $option->id }}">{{ $option->physical_path }}</option>@endforeach</select><input class="form-control mt-2" type="text" value="" readonly hidden data-container-box-display><small class="field-help" data-container-parent-help hidden>{{ __('main.archive_container_parent_locked') }}</small></label>
                    <label>{{ __('main.archive_title_label') }} *<input class="form-control" name="title" required placeholder="{{ __('main.archive_container_title_placeholder') }}"></label>
                    <label>{{ __('main.category') }}<input class="form-control" name="category" placeholder="{{ __('main.archive_category_placeholder') }}"></label>
                    <label>{{ __('main.owner_service') }}<input class="form-control" name="owner_service" placeholder="{{ __('main.archive_service_placeholder') }}"></label>
                    <label>{{ __('main.period') }}<input class="form-control" name="period_label" placeholder="2026"></label>
                    <label>{{ __('main.confidentiality') }} *<select class="form-select" name="confidentiality_level" required>@foreach ($confidentialityLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label>{{ __('main.capacity') }}<input class="form-control" type="number" name="capacity" min="1"></label>
                    <label>{{ __('main.status') }} *<select class="form-select" name="status" required>@foreach ($containerStatusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                </div>
                <label>{{ __('main.description') }}<textarea class="form-control" name="description" rows="3"></textarea></label>
            </div>
            <div class="modal-actions"><button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button><button class="modal-submit" type="submit" id="containerSubmit">{{ __('main.create') }}</button></div>
        </form>
    </div></div>
</div>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('containerModal');
    const form = modal?.querySelector('form');
    const title = document.getElementById('containerModalTitle');
    const method = document.getElementById('containerHttpMethod');
    const submit = document.getElementById('containerSubmit');
    const boxSelect = form?.querySelector('[data-container-box-select]');
    const boxDisplay = form?.querySelector('[data-container-box-display]');
    const parentHelp = form?.querySelector('[data-container-parent-help]');
    const fields = {
        title: form?.querySelector('[name="title"]'),
        category: form?.querySelector('[name="category"]'),
        ownerService: form?.querySelector('[name="owner_service"]'),
        periodLabel: form?.querySelector('[name="period_label"]'),
        confidentialityLevel: form?.querySelector('[name="confidentiality_level"]'),
        capacity: form?.querySelector('[name="capacity"]'),
        status: form?.querySelector('[name="status"]'),
        description: form?.querySelector('[name="description"]'),
    };
    const setCreateMode = () => {
        form?.reset();
        if (form?.dataset.containerCreateAction) form.action = form.dataset.containerCreateAction;
        if (method) method.disabled = true;
        if (boxSelect) boxSelect.disabled = false;
        if (boxDisplay) boxDisplay.hidden = true;
        if (parentHelp) parentHelp.hidden = true;
        if (title) title.innerHTML = '<i class="bi bi-folder2-open"></i> {{ __('main.archive_new_container') }}';
        if (submit) submit.textContent = '{{ __('main.create') }}';
    };
    const setEditMode = (trigger) => {
        if (! form || ! trigger) return;
        form.action = trigger.dataset.containerAction || form.action;
        if (method) method.disabled = false;
        if (boxSelect) boxSelect.disabled = true;
        if (boxDisplay) {
            boxDisplay.value = trigger.dataset.containerBox || '-';
            boxDisplay.hidden = false;
        }
        if (parentHelp) parentHelp.hidden = false;
        Object.entries(fields).forEach(([key, field]) => {
            if (field) field.value = trigger.dataset[`container${key.charAt(0).toUpperCase()}${key.slice(1)}`] || '';
        });
        if (title) title.innerHTML = '<i class="bi bi-folder2-open"></i> {{ __('main.archive_edit_container') }}';
        if (submit) submit.textContent = '{{ __('admin.update') }}';
    };
    modal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (trigger?.dataset.containerMode === 'edit') {
            setEditMode(trigger);
            return;
        }
        setCreateMode();
    });
});
</script>
</body>
</html>
