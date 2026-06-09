<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.archive_locations') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
@php
    $sections = [
        ['type' => 'room', 'modelType' => 'room', 'title' => __('main.archive_rooms'), 'rows' => $rooms, 'columns' => ['reference', 'name', 'capacity', 'status']],
        ['type' => 'rack', 'modelType' => 'zone', 'title' => __('main.archive_racks'), 'rows' => $racks, 'parent' => __('main.archive_room')],
        ['type' => 'cabinet', 'modelType' => 'cabinet', 'title' => __('main.archive_cabinets'), 'rows' => $cabinets, 'parent' => __('main.archive_rack')],
        ['type' => 'shelf', 'modelType' => 'shelf', 'title' => __('main.archive_shelves'), 'rows' => $shelves, 'parent' => __('main.archive_cabinet')],
        ['type' => 'compartment', 'modelType' => 'compartment', 'title' => __('main.archive_compartments'), 'rows' => $compartments, 'parent' => __('main.archive_shelf')],
        ['type' => 'box', 'modelType' => 'box', 'title' => __('main.archive_boxes'), 'rows' => $boxes, 'parent' => __('main.archive_physical_path')],
    ];
    $totalLocations = $rooms->total() + $racks->total() + $cabinets->total() + $shelves->total() + $compartments->total() + $boxes->total();
    $activeLocationTab = in_array(request('tab'), collect($sections)->pluck('type')->all(), true) ? request('tab') : 'room';
@endphp
<div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
@include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'locations'])
<main class="dashboard-main">
<header class="dashboard-topbar"><div><h1>{{ __('main.archive_locations') }}</h1><p>{{ $company->name }} / {{ $site->name }}</p></div>@include('main.modules.partials.accounting-header-actions')</header>
<section class="dashboard-content accounting-list-page archiving-page">
<a class="back-link" href="{{ route('main.archiving.dashboard', [$company, $site]) }}"><i class="bi bi-arrow-left"></i>{{ __('main.archive_dashboard') }}</a>
<section class="page-heading"><div><h1>{{ __('main.archive_locations') }}</h1><p>{{ __('main.archive_locations_subtitle') }}</p></div><button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#locationModal" data-location-mode="create"><i class="bi bi-plus-lg"></i>{{ __('main.archive_new_location') }}</button></section>
@if (session('success'))<div class="flash-toast" role="status" data-autohide="15000"><span class="flash-icon"><i class="bi bi-check2-circle"></i></span><span>{{ session('success') }}</span><button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button><span class="flash-progress"></span></div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<section class="table-tools"><form class="search-box" method="GET"><i class="bi bi-search"></i><input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.archive_search_location') }}"></form><span><strong>{{ $totalLocations }}</strong> {{ __('main.rows') }}</span></section>

<section class="archive-location-tabs-wrap">
    <ul class="nav archive-location-tabs" role="tablist" aria-label="{{ __('main.archive_physical_hierarchy') }}">
        @foreach ($sections as $section)
            <li class="nav-item" role="presentation">
                <button class="archive-location-tab {{ $activeLocationTab === $section['type'] ? 'active' : '' }}" id="archive-{{ $section['type'] }}-tab" data-bs-toggle="tab" data-bs-target="#archive-{{ $section['type'] }}-panel" type="button" role="tab" aria-controls="archive-{{ $section['type'] }}-panel" aria-selected="{{ $activeLocationTab === $section['type'] ? 'true' : 'false' }}" data-archive-location-tab="{{ $section['type'] }}">
                    <span>{{ $section['title'] }}</span>
                    <strong>{{ $section['rows']->total() }}</strong>
                </button>
            </li>
        @endforeach
    </ul>
</section>

<section class="tab-content archive-location-tab-content">
    @foreach ($sections as $section)
        <article class="tab-pane fade {{ $activeLocationTab === $section['type'] ? 'show active' : '' }} company-card archive-location-section" id="archive-{{ $section['type'] }}-panel" role="tabpanel" aria-labelledby="archive-{{ $section['type'] }}-tab" tabindex="0">
            <header class="archive-location-section-header"><div><span>{{ __('main.archive_physical_structure') }}</span><h2>{{ $section['title'] }}</h2></div><strong>{{ $section['rows']->total() }} {{ __('main.rows') }}</strong></header>
            <div class="company-table-wrap">
                <table class="company-table archive-locations-table" data-sortable-table>
                    <thead><tr><th><button class="table-sort" type="button">{{ __('main.reference') }} <i class="bi bi-arrow-down-up"></i></button></th><th><button class="table-sort" type="button">{{ __('main.archive_name_label') }} <i class="bi bi-arrow-down-up"></i></button></th>@if (($section['type'] ?? '') !== 'room')<th><button class="table-sort" type="button">{{ $section['parent'] }} <i class="bi bi-arrow-down-up"></i></button></th>@endif<th><button class="table-sort" type="button">{{ __('main.capacity') }} <i class="bi bi-arrow-down-up"></i></button></th>@if (($section['type'] ?? '') === 'box')<th><button class="table-sort" type="button">{{ __('main.archive_containers') }} <i class="bi bi-arrow-down-up"></i></button></th>@endif<th><button class="table-sort" type="button">{{ __('main.status') }} <i class="bi bi-arrow-down-up"></i></button></th><th>{{ __('main.actions') }}</th></tr></thead>
                    <tbody>
                    @forelse ($section['rows'] as $row)
                        <tr>
                            <td>{{ $row->reference }}</td>
                            <td><strong>{{ $row->name }}</strong><br><small>{{ $row->code ?: '-' }}</small></td>
                            @if ($section['type'] === 'rack')<td>{{ $row->room?->name ?? '-' }}</td>@endif
                            @if ($section['type'] === 'cabinet')<td>{{ $row->rack?->name ?? '-' }}<br><small>{{ $row->rack?->room?->name ?? '-' }}</small></td>@endif
                            @if ($section['type'] === 'shelf')<td>{{ $row->cabinet?->name ?? '-' }}<br><small>{{ $row->cabinet?->rack?->name ?? '-' }}</small></td>@endif
                            @if ($section['type'] === 'compartment')<td>{{ $row->shelf?->name ?? '-' }}<br><small>{{ $row->shelf?->cabinet?->name ?? '-' }}</small></td>@endif
                            @if ($section['type'] === 'box')<td>{{ $row->physical_path }}</td>@endif
                            <td>{{ $row->capacity ?? '-' }} <small>{{ __('main.archive_capacity_units') }}</small></td>
                            @if ($section['type'] === 'box')<td>{{ $row->containers_count ?? 0 }}</td>@endif
                            <td><span class="status-pill archive-status-{{ $row->status }}">{{ $statusLabels[$row->status] ?? $row->status }}</span></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#locationModal" data-location-mode="edit" data-location-action="{{ route('main.archiving.locations.update', [$company, $site, $section['modelType'], $row->id]) }}" data-location-type="{{ $section['modelType'] }}" data-location-name="{{ $row->name }}" data-location-code="{{ $row->code }}" data-location-capacity="{{ $row->capacity }}" data-location-status="{{ $row->status }}" data-location-description="{{ $row->description }}" aria-label="{{ __('admin.edit') }}"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('main.archiving.locations.destroy', [$company, $site, $section['modelType'], $row->id]) }}" onsubmit="return confirm('{{ __('main.archive_delete_location_confirm') }}')">@csrf @method('DELETE')<button type="submit" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}"><i class="bi bi-trash"></i></button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $section['type'] === 'room' ? 5 : ($section['type'] === 'box' ? 7 : 6) }}" class="text-center text-muted">{{ __('admin.no_results') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($section['rows']->hasPages())<section class="subscriptions-pagination"><span>{{ __('admin.showing') }} <strong>{{ $section['rows']->firstItem() }}</strong> {{ __('admin.to') }} <strong>{{ $section['rows']->lastItem() }}</strong> {{ __('admin.on') }} <strong>{{ $section['rows']->total() }}</strong></span>{{ $section['rows']->appends(['tab' => $section['type']])->links() }}</section>@endif
        </article>
    @endforeach
</section>
</section></main></div>

<div class="modal fade subscription-modal accounting-proforma-modal archive-location-modal" id="locationModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 id="locationModalTitle"><i class="bi bi-geo-alt"></i> {{ __('main.archive_new_location') }}</h2><button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button></div>
<form method="POST" action="{{ route('main.archiving.locations.store', [$company, $site]) }}" class="admin-form" data-location-create-action="{{ route('main.archiving.locations.store', [$company, $site]) }}">@csrf
<input type="hidden" name="_method" id="locationHttpMethod" value="PUT" disabled>
<div class="modal-body"><div class="modal-fields two-columns">
<label>{{ __('main.type') }} *<select name="type" class="form-select" required data-archive-location-type>@foreach ($typeLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
<label data-parent-field="room">{{ __('main.archive_room') }} *<select name="archive_room_id" class="form-select"><option value="">{{ __('main.none') }}</option>@foreach ($roomOptions as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach</select></label>
<label data-parent-field="rack">{{ __('main.archive_rack') }} *<select name="archive_rack_id" class="form-select"><option value="">{{ __('main.none') }}</option>@foreach ($rackOptions as $option)<option value="{{ $option->id }}">{{ $option->room?->name }} / {{ $option->name }}</option>@endforeach</select></label>
<label data-parent-field="cabinet">{{ __('main.archive_cabinet') }} *<select name="archive_cabinet_id" class="form-select"><option value="">{{ __('main.none') }}</option>@foreach ($cabinetOptions as $option)<option value="{{ $option->id }}">{{ $option->rack?->room?->name }} / {{ $option->rack?->name }} / {{ $option->name }}</option>@endforeach</select></label>
<label data-parent-field="shelf">{{ __('main.archive_shelf') }} *<select name="archive_shelf_id" class="form-select"><option value="">{{ __('main.none') }}</option>@foreach ($shelfOptions as $option)<option value="{{ $option->id }}">{{ $option->cabinet?->rack?->room?->name }} / {{ $option->cabinet?->rack?->name }} / {{ $option->cabinet?->name }} / {{ $option->name }}</option>@endforeach</select></label>
<label data-parent-field="compartment">{{ __('main.archive_compartment') }}<select name="archive_compartment_id" class="form-select"><option value="">{{ __('main.none') }}</option>@foreach ($compartmentOptions as $option)<option value="{{ $option->id }}">{{ $option->shelf?->cabinet?->name }} / {{ $option->shelf?->name }} / {{ $option->name }}</option>@endforeach</select></label>
<label>{{ __('main.archive_name_label') }} *<input class="form-control" name="name" required placeholder="{{ __('main.archive_location_name_placeholder') }}"></label>
<label>{{ __('main.code') }}<input class="form-control" name="code" placeholder="A01"></label>
<label>{{ __('main.capacity') }}<input class="form-control" type="number" name="capacity" min="1" placeholder="100"></label>
<label>{{ __('main.status') }} *<select name="status" class="form-select" required>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
</div><p class="field-help">{{ __('main.archive_parent_location_help') }}</p><label>{{ __('main.description') }}<textarea class="form-control" name="description" rows="3" placeholder="{{ __('main.archive_location_description_placeholder') }}"></textarea></label></div>
<div class="modal-actions"><button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button><button class="modal-submit" type="submit" id="locationSubmit">{{ __('main.create') }}</button></div>
</form></div></div></div>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('locationModal');
    const form = modal?.querySelector('form');
    const title = document.getElementById('locationModalTitle');
    const method = document.getElementById('locationHttpMethod');
    const submit = document.getElementById('locationSubmit');
    const typeSelect = document.querySelector('[data-archive-location-type]');
    const parentFields = Array.from(document.querySelectorAll('[data-parent-field]'));
    const rules = { room: [], zone: ['room'], cabinet: ['rack'], shelf: ['cabinet'], compartment: ['shelf'], box: ['shelf', 'compartment'] };
    const fields = {
        name: form?.querySelector('[name="name"]'),
        code: form?.querySelector('[name="code"]'),
        capacity: form?.querySelector('[name="capacity"]'),
        status: form?.querySelector('[name="status"]'),
        description: form?.querySelector('[name="description"]'),
    };
    const refresh = () => {
        const visible = rules[typeSelect?.value || 'room'] || [];
        parentFields.forEach((field) => {
            const show = visible.includes(field.dataset.parentField);
            field.hidden = !show;
            field.querySelectorAll('select').forEach((select) => { select.disabled = !show; if (!show) select.value = ''; });
        });
    };
    const setCreateMode = () => {
        form?.reset();
        if (form?.dataset.locationCreateAction) form.action = form.dataset.locationCreateAction;
        if (method) method.disabled = true;
        if (typeSelect) typeSelect.disabled = false;
        if (title) title.innerHTML = '<i class="bi bi-geo-alt"></i> {{ __('main.archive_new_location') }}';
        if (submit) submit.textContent = '{{ __('main.create') }}';
        refresh();
    };
    const setEditMode = (trigger) => {
        if (! form || ! trigger) return;
        form.action = trigger.dataset.locationAction || form.action;
        if (method) method.disabled = false;
        if (typeSelect) {
            typeSelect.value = trigger.dataset.locationType || 'room';
            typeSelect.disabled = true;
        }
        Object.entries(fields).forEach(([key, field]) => {
            if (field) field.value = trigger.dataset[`location${key.charAt(0).toUpperCase()}${key.slice(1)}`] || '';
        });
        parentFields.forEach((field) => {
            field.hidden = true;
            field.querySelectorAll('select').forEach((select) => { select.disabled = true; });
        });
        if (title) title.innerHTML = '<i class="bi bi-geo-alt"></i> {{ __('main.archive_edit_location') }}';
        if (submit) submit.textContent = '{{ __('admin.update') }}';
    };
    modal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (trigger?.dataset.locationMode === 'edit') {
            setEditMode(trigger);
            return;
        }
        setCreateMode();
    });
    typeSelect?.addEventListener('change', refresh);
    refresh();

    document.querySelectorAll('[data-archive-location-tab]').forEach((tab) => {
        tab.addEventListener('shown.bs.tab', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab.dataset.archiveLocationTab);
            window.history.replaceState({}, '', url);
        });
    });
});
</script>
</body></html>
