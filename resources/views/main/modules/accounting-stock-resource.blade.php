<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $config['title'] }} | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body">
    @php
        $currentLocale = app()->getLocale();
        $moduleRoute = route('main.companies.sites.modules.show', [$company, $site, $module]);
        $totalRecords = $records->total();
        $hasResourceErrors = $errors->any();
        $isEditingResource = old('form_mode') === 'edit' && old('record_id');
        $formAction = $isEditingResource
            ? route('main.accounting.stock.update', [$company, $site, $resource, old('record_id')])
            : route('main.accounting.stock.store', [$company, $site, $resource]);
        $cellValue = function ($record, array $column) {
            $value = data_get($record, $column['key']);
            $maps = [
                'status' => ['active' => __('main.active'), 'inactive' => __('main.inactive')],
                'draft_status' => [
                    'draft' => __('main.stock_status_draft'),
                    'validated' => __('main.stock_status_validated'),
                    'cancelled' => __('main.stock_status_cancelled'),
                ],
                'unit_type' => [
                    'unit' => __('main.stock_unit_type_unit'),
                    'weight' => __('main.stock_unit_type_weight'),
                    'volume' => __('main.stock_unit_type_volume'),
                    'length' => __('main.stock_unit_type_length'),
                    'package' => __('main.stock_unit_type_package'),
                    'quantity' => __('main.stock_unit_type_quantity'),
                ],
                'movement_type' => [
                    'entry' => __('main.stock_movement_type_entry'),
                    'exit' => __('main.stock_movement_type_exit'),
                    'adjustment' => __('main.stock_movement_type_adjustment'),
                ],
                'alert_type' => [
                    'low_stock' => __('main.stock_alert_type_low_stock'),
                    'overstock' => __('main.stock_alert_type_overstock'),
                    'expiration' => __('main.stock_alert_type_expiration'),
                ],
            ];

            if ($value instanceof \Carbon\CarbonInterface) {
                return $value->translatedFormat('d M Y');
            }

            if (isset($maps[$column['type'] ?? ''][$value])) {
                return $maps[$column['type']][$value];
            }

            if (($column['type'] ?? null) === 'money') {
                return number_format((float) $value, 0, ',', ' ').' '.($record->currency ?? '');
            }

            if (($column['type'] ?? null) === 'number') {
                return number_format((float) $value, 2, ',', ' ');
            }

            if (blank($value)) {
                return '-';
            }

            return $value;
        };
        $recordPayload = function ($record, array $fields) {
            $payload = [];

            foreach ($fields as $field) {
                $value = data_get($record, $field['name']);

                if ($value instanceof \Carbon\CarbonInterface) {
                    $value = $value->format('Y-m-d');
                }

                $payload[$field['name']] = $value;
            }

            return $payload;
        };
        $stockSubcategoryItemsPayload = function ($record, bool $forCategory = false) {
            return $record->items
                ->map(function ($item) use ($forCategory) {
                    $payload = [
                        'reference' => $item->reference ?: '-',
                        'name' => $item->name ?: '-',
                        'unit' => $item->unit?->name ?: '-',
                    ];

                    if ($forCategory) {
                        $payload['subcategory'] = $item->subcategory?->name ?: '-';
                    } else {
                        $payload['sale_price'] = number_format((float) $item->sale_price, 2, ',', ' ').' '.($item->currency ?: '');
                    }

                    return $payload;
                })
                ->values()
                ->all();
        };
    @endphp

    <div class="dashboard-shell main-shell accounting-shell" data-theme="light">
        @include('main.modules.partials.accounting-sidebar', ['activeAccountingPage' => $config['active']])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ $config['title'] }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>

                <div class="header-actions">
                    <button class="icon-button" type="button" id="themeButton" aria-label="{{ __('auth.theme_dark') }}" title="{{ __('auth.theme_dark') }}">
                        <i class="bi bi-brightness-high-fill" aria-hidden="true"></i>
                    </button>
                    <div class="language-menu">
                        <button class="language-button" type="button" id="languageButton" aria-label="{{ __('auth.language_switch') }}" aria-expanded="false" aria-controls="languageDropdown" title="{{ __('auth.language_switch') }}">
                            <i class="bi bi-globe2" aria-hidden="true"></i>
                            <span>{{ strtoupper($currentLocale) }}</span>
                            <i class="bi bi-chevron-down language-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="language-dropdown" id="languageDropdown" aria-labelledby="languageButton">
                            <a class="language-option {{ $currentLocale === 'fr' ? 'active' : '' }}" href="{{ route('locale.switch', 'fr') }}">
                                <span class="language-code">FR</span>
                                <span class="language-name">{{ __('auth.language_fr') }}</span>
                                @if ($currentLocale === 'fr')
                                    <i class="bi bi-check-lg language-check" aria-hidden="true"></i>
                                @endif
                            </a>
                            <a class="language-option {{ $currentLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">
                                <span class="language-code">EN</span>
                                <span class="language-name">{{ __('auth.language_en') }}</span>
                                @if ($currentLocale === 'en')
                                    <i class="bi bi-check-lg language-check" aria-hidden="true"></i>
                                @endif
                            </a>
                        </div>
                    </div>
                    <div class="profile-menu">
                        <button class="profile-button" type="button" id="profileButton" aria-expanded="false" aria-controls="profileDropdown">
                            @include('partials.user-avatar', ['avatarUser' => $user])
                            <span class="profile-name">{{ $user->name }}</span>
                            <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown" aria-labelledby="profileButton">
                            <div class="profile-summary">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->email }}</span>
                                <em>{{ $user->role === 'admin' ? __('main.admin_badge') : strtoupper($user->role) }}</em>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="profile-link">
                                <i class="bi bi-person-circle" aria-hidden="true"></i>
                                {{ __('main.profile') }}
                            </a>
                            @if ($user->isAdmin())
                                <a href="{{ route('main.users') }}" class="profile-link">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    {{ __('main.users') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="profile-link logout-link" type="submit">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                    {{ __('main.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <section class="dashboard-content module-dashboard-page accounting-list-page">
                <a class="back-link" href="{{ $moduleRoute }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ __('main.accounting_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ $config['title'] }}</h1>
                        <p>{{ $config['subtitle'] }}</p>
                    </div>
                    @if ($stockPermissions['can_create'])
                        <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#stockResourceModal" data-stock-mode="create">
                            <i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>
                            {{ $config['new_label'] }}
                        </button>
                    @endif
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
                        <strong id="visibleCount">{{ $records->count() }}</strong>
                        /
                        <strong>{{ $totalRecords }}</strong>
                        {{ __('admin.rows') }}
                    </span>
                </section>

                <section class="company-card">
                    <div class="table-responsive">
                        <table class="company-table stock-resource-table" id="companyTable">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" type="button" data-sort-index="0" data-sort-type="number"># <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @foreach ($config['columns'] as $index => $column)
                                        <th @class(['text-end' => ($column['type'] ?? null) === 'money'])><button class="table-sort" type="button" data-sort-index="{{ $index + 1 }}" @if (($column['type'] ?? null) === 'number' || ($column['type'] ?? null) === 'money') data-sort-type="number" @endif>{{ $column['label'] }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                    @endforeach
                                    <th class="text-end">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr>
                                        <td>{{ ($records->firstItem() ?? 1) + $loop->index }}</td>
                                        @foreach ($config['columns'] as $column)
                                            @php $value = $cellValue($record, $column); @endphp
                                            <td @class(['amount-cell text-end' => ($column['type'] ?? null) === 'money']) @if (($column['type'] ?? null) === 'number' || ($column['type'] ?? null) === 'money') data-sort-value="{{ data_get($record, $column['key']) }}" @endif>
                                                @if (str_contains($column['key'], 'status'))
                                                    <span class="status-pill stock-status-{{ data_get($record, $column['key']) }}">{{ $value }}</span>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            @php
                                                $isProtectedDefault = in_array($resource, ['categories', 'subcategories', 'warehouses', 'units'], true) && (bool) data_get($record, 'is_default');
                                            @endphp
                                            @if ($stockPermissions['can_update'] || ($stockPermissions['can_delete'] && ! $isProtectedDefault))
                                                <div class="table-actions">
                                                    @if (in_array($resource, ['categories', 'subcategories'], true))
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#stockRelatedModal" data-stock-related-kind="{{ $resource }}" data-stock-related-title="{{ $resource === 'categories' ? __('main.category_items_title', ['name' => $record->name]) : __('main.subcategory_items_title', ['name' => $record->name]) }}" data-stock-related-empty="{{ $resource === 'categories' ? __('main.no_category_items') : __('main.no_subcategory_items') }}" data-stock-related-rows="{{ base64_encode(json_encode($stockSubcategoryItemsPayload($record, $resource === 'categories'))) }}" aria-label="{{ $resource === 'categories' ? __('main.view_category_items') : __('main.view_subcategory_items') }}">
                                                            <i class="bi bi-list-ul" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($stockPermissions['can_update'])
                                                        <button type="button" class="table-button table-button-edit" data-bs-toggle="modal" data-bs-target="#stockResourceModal" data-stock-mode="edit" data-stock-action="{{ route('main.accounting.stock.update', [$company, $site, $resource, $record]) }}" data-stock-id="{{ $record->id }}" data-stock-values="{{ base64_encode(json_encode($recordPayload($record, $config['fields']))) }}" aria-label="{{ __('admin.edit') }}">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                    @if ($stockPermissions['can_delete'] && ! $isProtectedDefault)
                                                        <form method="POST" action="{{ route('main.accounting.stock.destroy', [$company, $site, $resource, $record]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="table-button table-button-delete" aria-label="{{ __('admin.delete') }}" data-delete-trigger data-delete-title="{{ __('main.delete_stock_resource_title') }}" data-delete-text="{{ __('main.delete_stock_resource_text', ['name' => data_get($record, 'name', data_get($record, 'reference'))]) }}" data-delete-confirm="{{ __('admin.delete_user_confirm') }}" data-delete-cancel="{{ __('admin.delete_user_cancel') }}">
                                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="muted-dash">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row"><td colspan="{{ count($config['columns']) + 2 }}">{{ $config['empty'] }}</td></tr>
                                @endforelse
                                <tr class="empty-row search-empty-row" hidden><td colspan="{{ count($config['columns']) + 2 }}">{{ __('admin.no_results') }}</td></tr>
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

    <div class="modal fade subscription-modal accounting-stock-modal" id="stockResourceModal" tabindex="-1" aria-labelledby="stockResourceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content admin-form stock-resource-form" method="POST" action="{{ $formAction }}" data-create-action="{{ route('main.accounting.stock.store', [$company, $site, $resource]) }}" data-title-create="{{ $config['new_label'] }}" data-title-edit="{{ $config['edit_label'] }}" data-title-view="{{ __('main.view_stock_resource', ['resource' => $config['singular_lower']]) }}" data-submit-create="{{ __('admin.create') }}" data-submit-edit="{{ __('admin.update') }}" data-cancel-label="{{ __('admin.cancel') }}" data-close-label="{{ __('admin.close') }}" data-icon="{{ $config['icon'] }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="stockResourceMethod" value="PUT" @disabled(! $isEditingResource)>
                <input type="hidden" name="form_mode" id="stockResourceFormMode" value="{{ $isEditingResource ? 'edit' : 'create' }}">
                <input type="hidden" name="record_id" id="stockResourceId" value="{{ old('record_id') }}">
                <div class="modal-body">
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    <h2 id="stockResourceModalLabel"><i class="bi {{ $config['icon'] }}" aria-hidden="true"></i>{{ $isEditingResource ? $config['edit_label'] : $config['new_label'] }}</h2>

                    <div class="row g-3">
                        @foreach ($config['fields'] as $field)
                            @php
                                $fieldName = $field['name'];
                                $fieldId = 'stock_'.str_replace(['.', '[', ']'], '_', $fieldName);
                                $default = old($fieldName, $field['default'] ?? '');
                            @endphp
                            <div class="{{ ($field['type'] ?? 'text') === 'textarea' ? 'col-12' : 'col-md-6' }}">
                                <label for="{{ $fieldId }}" class="form-label">{{ $field['label'] }} @if($field['required'] ?? false)*@endif</label>
                                @if (($field['type'] ?? 'text') === 'select')
                                    <select id="{{ $fieldId }}" name="{{ $fieldName }}" class="form-select @error($fieldName) is-invalid @enderror" data-stock-field data-default-value="{{ $field['default'] ?? '' }}">
                                        <option value="">{{ __('admin.choose_subscription') }}</option>
                                        @foreach (($field['options'] ?? []) as $optionValue => $optionLabel)
                                            @php $optionAttributes = $field['option_attributes'][$optionValue] ?? []; @endphp
                                            <option value="{{ $optionValue }}" @foreach ($optionAttributes as $attribute => $attributeValue) {{ $attribute }}="{{ $attributeValue }}" @endforeach @selected((string) $default === (string) $optionValue)>{{ $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                @elseif (($field['type'] ?? 'text') === 'textarea')
                                    <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" rows="3" class="form-control @error($fieldName) is-invalid @enderror" placeholder="{{ $field['label'] }}" data-stock-field data-default-value="{{ $field['default'] ?? '' }}">{{ $default }}</textarea>
                                @else
                                    <input id="{{ $fieldId }}" name="{{ $fieldName }}" type="{{ $field['type'] ?? 'text' }}" @if (($field['type'] ?? 'text') === 'number') min="0" step="0.01" @endif class="form-control @error($fieldName) is-invalid @enderror" value="{{ $default }}" placeholder="{{ $field['label'] }}" data-stock-field data-default-value="{{ $field['default'] ?? '' }}">
                                @endif
                                @error($fieldName)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" id="stockResourceCancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" id="stockResourceSubmit" type="submit">{{ $isEditingResource ? __('admin.update') : __('admin.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (in_array($resource, ['categories', 'subcategories'], true))
        <div class="modal fade subscription-modal related-table-modal" id="stockRelatedModal" tabindex="-1" aria-labelledby="stockRelatedModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content admin-form modal-table-dialog">
                    <div class="modal-body">
                        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                        <h2 id="stockRelatedModalLabel"><i class="bi bi-box-seam" aria-hidden="true"></i>{{ $resource === 'categories' ? __('main.view_category_items') : __('main.view_subcategory_items') }}</h2>

                        <section class="table-tools modal-table-tools" aria-label="{{ __('admin.search_tools') }}">
                            <label class="search-box">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" data-related-search placeholder="{{ __('admin.search') }}" autocomplete="off">
                            </label>
                            <span class="row-count">
                                <strong data-related-visible-count>0</strong>
                                /
                                <strong data-related-total-count>0</strong>
                                {{ __('admin.rows') }}
                            </span>
                        </section>

                        <div class="modal-table-frame">
                            <table class="company-table modal-data-table" data-related-table>
                                <thead>
                                    <tr>
                                        <th><button class="table-sort" type="button" data-related-sort="reference">{{ __('main.reference') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-related-sort="name">{{ __('main.item') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        <th><button class="table-sort" type="button" data-related-sort="unit">{{ __('main.stock_unit') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        @if ($resource === 'categories')
                                            <th><button class="table-sort" type="button" data-related-sort="subcategory">{{ __('main.subcategory') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        @else
                                            <th class="text-end"><button class="table-sort" type="button" data-related-sort="sale_price">{{ __('main.sale_price') }} <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody data-related-table-body></tbody>
                            </table>
                            <p class="modal-table-empty" data-related-empty hidden>{{ $resource === 'categories' ? __('main.no_category_items') : __('main.no_subcategory_items') }}</p>
                        </div>

                        <section class="subscriptions-pagination modal-table-pagination" data-related-pagination data-previous-label="{{ __('admin.previous') }}" data-next-label="{{ __('admin.next') }}" data-showing-label="{{ __('admin.showing') }}" data-to-label="{{ __('admin.to') }}" data-on-label="{{ __('admin.on') }}" hidden aria-label="{{ __('admin.pagination') }}">
                            <span data-related-pagination-count></span>
                            <nav class="pagination-shell" data-related-pagination-nav aria-label="{{ __('admin.pagination') }}"></nav>
                        </section>

                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($hasResourceErrors)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('stockResourceModal')).show();
            });
        </script>
    @endif
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <!-- resources/js/main/accounting-stock-resource.js -->
    <script>{!! file_get_contents(resource_path('js/main/accounting-stock-resource.js')) !!}</script>
</body>
</html>
