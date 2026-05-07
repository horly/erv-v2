@php
    $line = is_array($line ?? null) ? $line : [];
    $lineType = $line['line_type'] ?? \App\Models\AccountingCustomerOrderLine::TYPE_FREE;
    $marginType = $line['margin_type'] ?? \App\Models\AccountingCustomerOrderLine::MARGIN_FIXED;
    $discountType = $line['discount_type'] ?? \App\Models\AccountingCustomerOrderLine::DISCOUNT_FIXED;
@endphp

<div class="proforma-line-card customer-order-line-card" data-customer-order-line-row>
    <div class="row g-3 align-items-end">
        <div class="col-lg-2 col-md-4">
            <label class="form-label">{{ __('main.type') }} *</label>
            <select name="lines[{{ $index }}][line_type]" class="form-select @error("lines.$index.line_type") is-invalid @enderror" data-customer-order-line-type>
                @foreach ($lineTypeLabels as $value => $label)
                    <option value="{{ $value }}" @selected($lineType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error("lines.$index.line_type")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-3 col-md-8" data-customer-order-item-field @hidden($lineType !== \App\Models\AccountingCustomerOrderLine::TYPE_ITEM)>
            <label class="form-label">{{ __('main.item') }}</label>
            <select name="lines[{{ $index }}][item_id]" class="form-select @error("lines.$index.item_id") is-invalid @enderror" data-customer-order-item data-search-placeholder="{{ __('main.search') }}" data-search-empty="{{ __('admin.no_results') }}">
                <option value="">{{ __('main.choose_item') }}</option>
                @foreach ($items as $id => $item)
                    <option value="{{ $id }}" data-price="{{ $item['price'] }}" data-cost="{{ $item['cost'] }}" @selected(($line['item_id'] ?? '') == $id)>{{ $item['label'] }}</option>
                @endforeach
            </select>
            @error("lines.$index.item_id")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-3 col-md-8" data-customer-order-service-field @hidden($lineType !== \App\Models\AccountingCustomerOrderLine::TYPE_SERVICE)>
            <label class="form-label">{{ __('main.service') }}</label>
            <select name="lines[{{ $index }}][service_id]" class="form-select @error("lines.$index.service_id") is-invalid @enderror" data-customer-order-service data-search-placeholder="{{ __('main.search') }}" data-search-empty="{{ __('admin.no_results') }}">
                <option value="">{{ __('main.choose_service') }}</option>
                @foreach ($services as $id => $service)
                    <option value="{{ $id }}" data-price="{{ $service['price'] }}" data-cost="{{ $service['cost'] }}" @selected(($line['service_id'] ?? '') == $id)>{{ $service['label'] }}</option>
                @endforeach
            </select>
            @error("lines.$index.service_id")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4 col-md-8">
            <label class="form-label">{{ __('main.designation') }} *</label>
            <input name="lines[{{ $index }}][description]" type="text" class="form-control @error("lines.$index.description") is-invalid @enderror" value="{{ $line['description'] ?? '' }}" placeholder="{{ __('main.designation_placeholder') }}" data-customer-order-description>
            @error("lines.$index.description")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-1 col-md-2">
            <button type="button" class="icon-light-button" data-remove-customer-order-line aria-label="{{ __('admin.delete') }}">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.quantity') }} *</label>
            <input name="lines[{{ $index }}][quantity]" type="number" min="0.01" step="0.01" class="form-control @error("lines.$index.quantity") is-invalid @enderror" value="{{ $line['quantity'] ?? '1' }}" placeholder="1" data-customer-order-quantity>
            @error("lines.$index.quantity")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.cost_price') }} *</label>
            <input name="lines[{{ $index }}][cost_price]" type="number" min="0" step="0.01" class="form-control @error("lines.$index.cost_price") is-invalid @enderror" value="{{ $line['cost_price'] ?? '0' }}" placeholder="0" data-customer-order-cost-price>
            @error("lines.$index.cost_price")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.margin_method') }}</label>
            <select name="lines[{{ $index }}][margin_type]" class="form-select @error("lines.$index.margin_type") is-invalid @enderror" data-customer-order-margin-type>
                <option value="{{ \App\Models\AccountingCustomerOrderLine::MARGIN_FIXED }}" @selected($marginType === \App\Models\AccountingCustomerOrderLine::MARGIN_FIXED)>{{ __('main.margin_fixed') }}</option>
                <option value="{{ \App\Models\AccountingCustomerOrderLine::MARGIN_PERCENT }}" @selected($marginType === \App\Models\AccountingCustomerOrderLine::MARGIN_PERCENT)>{{ __('main.margin_percent') }}</option>
            </select>
            @error("lines.$index.margin_type")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.margin_value') }}</label>
            <input name="lines[{{ $index }}][margin_value]" type="number" min="0" step="0.01" class="form-control @error("lines.$index.margin_value") is-invalid @enderror" value="{{ $line['margin_value'] ?? '0' }}" placeholder="0" data-customer-order-margin-value>
            @error("lines.$index.margin_value")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.unit_price') }} *</label>
            <input name="lines[{{ $index }}][unit_price]" type="number" min="0" step="0.01" class="form-control @error("lines.$index.unit_price") is-invalid @enderror" value="{{ $line['unit_price'] ?? '0' }}" placeholder="0" data-customer-order-unit-price>
            @error("lines.$index.unit_price")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.discount_method') }}</label>
            <select name="lines[{{ $index }}][discount_type]" class="form-select @error("lines.$index.discount_type") is-invalid @enderror" data-customer-order-discount-type>
                <option value="{{ \App\Models\AccountingCustomerOrderLine::DISCOUNT_FIXED }}" @selected($discountType === \App\Models\AccountingCustomerOrderLine::DISCOUNT_FIXED)>{{ __('main.discount_fixed') }}</option>
                <option value="{{ \App\Models\AccountingCustomerOrderLine::DISCOUNT_PERCENT }}" @selected($discountType === \App\Models\AccountingCustomerOrderLine::DISCOUNT_PERCENT)>{{ __('main.discount_percent') }}</option>
            </select>
            @error("lines.$index.discount_type")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.discount_value') }}</label>
            <input name="lines[{{ $index }}][discount_amount]" type="number" min="0" step="0.01" class="form-control @error("lines.$index.discount_amount") is-invalid @enderror" value="{{ $line['discount_amount'] ?? '0' }}" placeholder="0" data-customer-order-discount>
            @error("lines.$index.discount_amount")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.line_margin') }}</label>
            <input type="text" class="form-control" value="0,00" data-customer-order-line-margin readonly>
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.line_total') }}</label>
            <input type="text" class="form-control" value="0,00" data-customer-order-line-total readonly>
        </div>
        <div class="col-lg-4">
            <label class="form-label">{{ __('main.description') }}</label>
            <input name="lines[{{ $index }}][details]" type="text" class="form-control @error("lines.$index.details") is-invalid @enderror" value="{{ $line['details'] ?? '' }}" placeholder="{{ __('main.description') }}">
            @error("lines.$index.details")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4" data-customer-order-create-item-field @hidden($lineType !== \App\Models\AccountingCustomerOrderLine::TYPE_FREE)>
            <label class="form-check customer-order-stock-check">
                <input type="hidden" name="lines[{{ $index }}][create_stock_item]" value="0">
                <input class="form-check-input" type="checkbox" name="lines[{{ $index }}][create_stock_item]" value="1" @checked((bool) ($line['create_stock_item'] ?? false))>
                <span class="form-check-label">{{ __('main.create_stock_item_from_free_line') }}</span>
            </label>
            <small class="form-text text-muted">{{ __('main.create_stock_item_from_free_line_hint') }}</small>
            @error("lines.$index.create_stock_item")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
