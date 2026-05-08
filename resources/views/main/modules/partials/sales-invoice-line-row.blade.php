@php
    $line = is_array($line ?? null) ? $line : [];
    $lineType = $line['line_type'] ?? \App\Models\AccountingSalesInvoiceLine::TYPE_FREE;
    $discountType = $line['discount_type'] ?? \App\Models\AccountingSalesInvoiceLine::DISCOUNT_FIXED;
@endphp

<div class="proforma-line-card" data-proforma-line-row>
    <input type="hidden" name="lines[{{ $index }}][cost_price]" value="{{ $line['cost_price'] ?? '0' }}" data-proforma-cost-price>
    <input type="hidden" name="lines[{{ $index }}][customer_order_line_id]" value="{{ $line['customer_order_line_id'] ?? '' }}">
    <input type="hidden" name="lines[{{ $index }}][delivery_note_line_id]" value="{{ $line['delivery_note_line_id'] ?? '' }}">
    <div class="row g-3 align-items-end">
        <div class="col-lg-2 col-md-4">
            <label class="form-label">{{ __('main.type') }} *</label>
            <select name="lines[{{ $index }}][line_type]" class="form-select @error("lines.$index.line_type") is-invalid @enderror" data-proforma-line-type>
                @foreach ($lineTypeLabels as $value => $label)
                    <option value="{{ $value }}" @selected($lineType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error("lines.$index.line_type")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-3 col-md-8" data-proforma-item-field @hidden($lineType !== \App\Models\AccountingSalesInvoiceLine::TYPE_ITEM)>
            <label class="form-label">{{ __('main.item') }}</label>
            <select name="lines[{{ $index }}][item_id]" class="form-select @error("lines.$index.item_id") is-invalid @enderror" data-proforma-item data-search-placeholder="{{ __('main.search') }}" data-search-empty="{{ __('admin.no_results') }}">
                <option value="">{{ __('main.choose_item') }}</option>
                @foreach ($items as $id => $item)
                    <option value="{{ $id }}" data-price="{{ $item['price'] }}" @selected(($line['item_id'] ?? '') == $id)>{{ $item['label'] }}</option>
                @endforeach
            </select>
            @error("lines.$index.item_id")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-3 col-md-8" data-proforma-service-field @hidden($lineType !== \App\Models\AccountingSalesInvoiceLine::TYPE_SERVICE)>
            <label class="form-label">{{ __('main.service') }}</label>
            <select name="lines[{{ $index }}][service_id]" class="form-select @error("lines.$index.service_id") is-invalid @enderror" data-proforma-service data-search-placeholder="{{ __('main.search') }}" data-search-empty="{{ __('admin.no_results') }}">
                <option value="">{{ __('main.choose_service') }}</option>
                @foreach ($services as $id => $service)
                    <option value="{{ $id }}" data-price="{{ $service['price'] }}" @selected(($line['service_id'] ?? '') == $id)>{{ $service['label'] }}</option>
                @endforeach
            </select>
            @error("lines.$index.service_id")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4 col-md-8">
            <label class="form-label">{{ __('main.designation') }} *</label>
            <input name="lines[{{ $index }}][description]" type="text" class="form-control @error("lines.$index.description") is-invalid @enderror" value="{{ $line['description'] ?? '' }}" placeholder="{{ __('main.designation_placeholder') }}" data-proforma-description>
            @error("lines.$index.description")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-1 col-md-2">
            <button type="button" class="icon-light-button" data-remove-proforma-line aria-label="{{ __('admin.delete') }}">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.quantity') }} *</label>
            <input name="lines[{{ $index }}][quantity]" type="number" min="0.01" step="0.01" class="form-control @error("lines.$index.quantity") is-invalid @enderror" value="{{ $line['quantity'] ?? '1' }}" placeholder="1" data-proforma-quantity>
            @error("lines.$index.quantity")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.unit_price') }} *</label>
            <input name="lines[{{ $index }}][unit_price]" type="number" min="0" step="0.01" class="form-control @error("lines.$index.unit_price") is-invalid @enderror" value="{{ $line['unit_price'] ?? '0' }}" placeholder="0" data-proforma-unit-price>
            @error("lines.$index.unit_price")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.discount_method') }}</label>
            <select name="lines[{{ $index }}][discount_type]" class="form-select @error("lines.$index.discount_type") is-invalid @enderror" data-proforma-discount-type>
                <option value="{{ \App\Models\AccountingSalesInvoiceLine::DISCOUNT_FIXED }}" @selected($discountType === \App\Models\AccountingSalesInvoiceLine::DISCOUNT_FIXED)>{{ __('main.discount_fixed') }}</option>
                <option value="{{ \App\Models\AccountingSalesInvoiceLine::DISCOUNT_PERCENT }}" @selected($discountType === \App\Models\AccountingSalesInvoiceLine::DISCOUNT_PERCENT)>{{ __('main.discount_percent') }}</option>
            </select>
            @error("lines.$index.discount_type")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.discount_value') }}</label>
            <input name="lines[{{ $index }}][discount_amount]" type="number" min="0" step="0.01" class="form-control @error("lines.$index.discount_amount") is-invalid @enderror" value="{{ $line['discount_amount'] ?? '0' }}" placeholder="0" data-proforma-discount>
            @error("lines.$index.discount_amount")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-2 col-md-3">
            <label class="form-label">{{ __('main.line_total') }}</label>
            <input type="text" class="form-control" value="0,00" data-proforma-line-total readonly>
        </div>
        <div class="col-lg-4">
            <label class="form-label">{{ __('main.description') }}</label>
            <input name="lines[{{ $index }}][details]" type="text" class="form-control @error("lines.$index.details") is-invalid @enderror" value="{{ $line['details'] ?? '' }}" placeholder="{{ __('main.description') }}">
            @error("lines.$index.details")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4" data-proforma-create-item-field @hidden($lineType !== \App\Models\AccountingSalesInvoiceLine::TYPE_FREE)>
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
