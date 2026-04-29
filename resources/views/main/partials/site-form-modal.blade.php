@php
    $usesOldInput = old('_site_modal_id') === $modalId;
    $selectedModules = $usesOldInput ? old('modules', []) : ($site?->modules ?? ['accounting']);
    $selectedModules = is_array($selectedModules) ? $selectedModules : [];
    $selectedResponsible = $usesOldInput ? old('responsible_id') : $site?->responsible_id;
    $selectedCurrency = $usesOldInput ? old('currency') : ($site?->currency ?? 'CDF');
    $isActive = ($usesOldInput ? old('status', 'active') : ($site?->status ?? 'active')) === 'active';
    $fieldValue = fn (string $key, mixed $default = null): mixed => $usesOldInput ? old($key, $default) : $default;
    $currencyLocaleKey = 'name_'.app()->getLocale();
    $currencyLabel = fn (string $code, array $currency): string => sprintf(
        '%s (%s%s)',
        $currency[$currencyLocaleKey] ?? $currency['name_fr'],
        $code,
        blank($currency['symbol'] ?? null) ? '' : ' - '.$currency['symbol'],
    );
    $moduleClasses = [
        'accounting' => 'module-accounting',
        'human_resources' => 'module-human-resources',
        'archiving' => 'module-archiving',
        'document_management' => 'module-document-management',
    ];
@endphp

<div class="modal fade site-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content site-form" method="POST" action="{{ $action }}">
            @csrf
            @if ($method)
                @method($method)
            @endif
            <input type="hidden" name="_site_modal_id" value="{{ $modalId }}">
            <div class="modal-header">
                <h2 id="{{ $modalId }}Label"><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $title }}</h2>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">{{ __('admin.name') }} *</label>
                        <input name="name" type="text" class="form-control {{ $usesOldInput && $errors->has('name') ? 'is-invalid' : '' }}" value="{{ $fieldValue('name', $site?->name) }}">
                        @if ($usesOldInput) @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">{{ __('main.type') }} *</label>
                        <select name="type" class="form-select {{ $usesOldInput && $errors->has('type') ? 'is-invalid' : '' }}">
                            @foreach ($siteTypes as $type)
                                <option value="{{ $type }}" @selected($fieldValue('type', $site?->type ?? 'production') === $type)>{{ $typeLabels[$type] }}</option>
                            @endforeach
                        </select>
                        @if ($usesOldInput) @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('main.code') }}</label>
                        <input name="code" type="text" class="form-control" value="{{ $fieldValue('code', $site?->code) }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ __('main.responsible') }} *</label>
                        <select name="responsible_id" class="form-select {{ $usesOldInput && $errors->has('responsible_id') ? 'is-invalid' : '' }}">
                            <option value="">{{ __('main.select_responsible') }}</option>
                            @foreach ($responsibles as $responsible)
                                <option value="{{ $responsible->id }}" @selected((string) $selectedResponsible === (string) $responsible->id)>{{ $responsible->name }} - {{ $responsible->email }}</option>
                            @endforeach
                        </select>
                        @if ($usesOldInput) @error('responsible_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('main.city') }}</label>
                        <input name="city" type="text" class="form-control" value="{{ $fieldValue('city', $site?->city) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('main.phone') }}</label>
                        <input name="phone" type="text" class="form-control" value="{{ $fieldValue('phone', $site?->phone) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('main.address') }}</label>
                        <input name="address" type="text" class="form-control" value="{{ $fieldValue('address', $site?->address) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('main.modules') }} *</label>
                        <div class="site-module-grid">
                            @foreach ($siteModules as $module)
                                @php
                                    $allowed = in_array($module, $planRules['allowed_modules'], true);
                                    $checked = in_array($module, $selectedModules, true);
                                @endphp
                                <label class="site-module-card {{ $moduleClasses[$module] ?? '' }} {{ ! $allowed ? 'is-disabled' : '' }}">
                                    <input type="checkbox" name="modules[]" value="{{ $module }}" @checked($checked) @disabled(! $allowed)>
                                    <span class="module-card-icon"><i class="bi {{ $module === 'accounting' ? 'bi-receipt' : ($module === 'human_resources' ? 'bi-people' : ($module === 'archiving' ? 'bi-archive' : 'bi-file-earmark-text')) }}" aria-hidden="true"></i></span>
                                    <span>{{ $moduleLabels[$module] }}</span>
                                    @unless ($allowed)<i class="bi bi-lock-fill module-lock" aria-hidden="true"></i>@endunless
                                </label>
                            @endforeach
                        </div>
                        <small class="plan-note">{{ __('main.plan_label', ['plan' => $planRules['name']]) }}</small>
                        @if ($usesOldInput) @error('modules')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('main.email') }}</label>
                        <input name="email" type="email" class="form-control {{ $usesOldInput && $errors->has('email') ? 'is-invalid' : '' }}" value="{{ $fieldValue('email', $site?->email) }}">
                        @if ($usesOldInput) @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.currency') }} *</label>
                        <select name="currency" class="form-select {{ $usesOldInput && $errors->has('currency') ? 'is-invalid' : '' }}">
                            @foreach ($currencies as $code => $currency)
                                <option value="{{ $code }}" @selected($selectedCurrency === $code)>{{ $currencyLabel($code, $currency) }}</option>
                            @endforeach
                        </select>
                        @if ($usesOldInput) @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                    </div>
                    <div class="col-md-2 site-status-control">
                        <input type="hidden" name="status" value="inactive">
                        <label class="site-switch">
                            <input type="checkbox" name="status" value="active" @checked($isActive)>
                            <span></span>
                            {{ __('main.active') }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                <button type="submit" class="modal-submit">{{ $method ? __('admin.update') : __('admin.create') }}</button>
            </div>
        </form>
    </div>
</div>
