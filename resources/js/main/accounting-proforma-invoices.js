(() => {
    const modal = document.getElementById('proformaModal');
    const form = modal?.querySelector('.proforma-form') || document.querySelector('.proforma-form');
    const methodInput = document.getElementById('proformaMethod');
    const modeInput = document.getElementById('proformaFormMode');
    const idInput = document.getElementById('proformaId');
    const submitButton = document.getElementById('proformaSubmit');
    const title = document.getElementById('proformaModalLabel');
    const lineList = document.querySelector('[data-proforma-line-list]');
    const lineTemplate = document.getElementById('proformaLineTemplate');
    const taxRateField = document.getElementById('proformaTaxRate');
    const totalFields = {
        subtotal: document.querySelector('[data-total-subtotal]'),
        discount: document.querySelector('[data-total-discount]'),
        ht: document.querySelector('[data-total-ht]'),
        tax: document.querySelector('[data-total-tax]'),
        ttc: document.querySelector('[data-total-ttc]'),
    };
    let lineIndex = lineList?.querySelectorAll('[data-proforma-line-row]').length || 0;

    const fields = Array.from(form?.querySelectorAll('[data-proforma-field]') || []);

    const escapeHtml = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const decodeValues = (payload = '') => {
        if (!payload) {
            return {};
        }

        try {
            return JSON.parse(atob(payload));
        } catch {
            return {};
        }
    };

    const formatAmount = (value) => Number(value || 0).toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const numericValue = (field) => Number(String(field?.value || '0').replace(',', '.')) || 0;

    const lineDiscountAmount = (row, rawTotal) => {
        const discountValue = numericValue(row.querySelector('[data-proforma-discount]'));
        const discountType = row.querySelector('[data-proforma-discount-type]')?.value || 'fixed';

        if (discountType === 'percent') {
            return Math.min(Math.max(discountValue, 0), 100) * rawTotal / 100;
        }

        return Math.min(Math.max(discountValue, 0), rawTotal);
    };

    const normalizeSearch = (value = '') => String(value)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const clearValidationState = () => {
        form?.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
            field.classList.remove('is-invalid', 'is-valid');
        });
    };

    const syncSearchableSelectLabel = (select, shell) => {
        const label = shell.querySelector('[data-search-label]');
        const selected = select.selectedOptions[0];
        const placeholder = select.options[0]?.textContent?.trim() || '';

        if (label) {
            label.textContent = selected?.value ? selected.textContent.trim() : placeholder;
            label.classList.toggle('is-placeholder', !selected?.value);
        }
    };

    const initSearchableSelect = (select) => {
        if (!select || select.dataset.searchReady === 'true') {
            return;
        }

        select.dataset.searchReady = 'true';

        const shell = document.createElement('div');
        shell.className = 'proforma-search-select';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'form-select proforma-search-select-toggle';
        if (select.classList.contains('is-invalid')) {
            toggle.classList.add('is-invalid');
        }
        toggle.innerHTML = '<span data-search-label></span><i class="bi bi-chevron-down" aria-hidden="true"></i>';

        const dropdown = document.createElement('div');
        dropdown.className = 'proforma-search-select-menu';
        dropdown.hidden = true;

        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-control proforma-search-select-input';
        search.placeholder = select.dataset.searchPlaceholder || '';
        search.autocomplete = 'off';

        const list = document.createElement('div');
        list.className = 'proforma-search-select-options';

        const empty = document.createElement('div');
        empty.className = 'proforma-search-select-empty';
        empty.textContent = select.dataset.searchEmpty || '';
        empty.hidden = true;

        dropdown.append(search, list, empty);
        shell.append(toggle, dropdown);
        select.parentNode.insertBefore(shell, select);
        select.classList.add('proforma-native-select');

        const optionButtons = Array.from(select.options)
            .filter((option) => option.value)
            .map((option) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'proforma-search-select-option';
                button.dataset.value = option.value;
                button.dataset.search = normalizeSearch(option.textContent);
                button.textContent = option.textContent.trim();
                button.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    dropdown.hidden = true;
                    search.value = '';
                    filterOptions('');
                    toggle.focus();
                });
                list.appendChild(button);
                return button;
            });

        const filterOptions = (query) => {
            const normalized = normalizeSearch(query);
            let visible = 0;

            optionButtons.forEach((button) => {
                const matches = !normalized || button.dataset.search.includes(normalized);
                button.hidden = !matches;
                if (matches) visible += 1;
            });

            empty.hidden = visible > 0;
        };

        const openDropdown = () => {
            dropdown.hidden = false;
            search.focus();
            search.select();
        };

        toggle.addEventListener('click', () => {
            if (dropdown.hidden) {
                openDropdown();
            } else {
                dropdown.hidden = true;
            }
        });

        search.addEventListener('input', () => filterOptions(search.value));
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                dropdown.hidden = true;
                toggle.focus();
            }

            if (event.key === 'Enter') {
                const firstVisible = optionButtons.find((button) => !button.hidden);
                if (firstVisible) {
                    event.preventDefault();
                    firstVisible.click();
                }
            }
        });

        document.addEventListener('click', (event) => {
            if (!shell.contains(event.target)) {
                dropdown.hidden = true;
            }
        });

        select.addEventListener('change', () => syncSearchableSelectLabel(select, shell));
        syncSearchableSelectLabel(select, shell);
    };

    const refreshLineVisibility = (row) => {
        const type = row.querySelector('[data-proforma-line-type]')?.value || 'free';
        const itemField = row.querySelector('[data-proforma-item-field]');
        const serviceField = row.querySelector('[data-proforma-service-field]');
        const createItemField = row.querySelector('[data-proforma-create-item-field]');

        if (itemField) {
            itemField.hidden = type !== 'item';
        }

        if (serviceField) {
            serviceField.hidden = type !== 'service';
        }

        if (createItemField) {
            createItemField.hidden = type !== 'free';
            if (type !== 'free') {
                const checkbox = createItemField.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
            }
        }
    };

    const refreshTotals = () => {
        let subtotal = 0;
        let discount = 0;
        let totalHt = 0;

        lineList?.querySelectorAll('[data-proforma-line-row]').forEach((row) => {
            const quantity = numericValue(row.querySelector('[data-proforma-quantity]'));
            const unitPrice = numericValue(row.querySelector('[data-proforma-unit-price]'));
            const rawTotal = quantity * unitPrice;
            const discountAmount = lineDiscountAmount(row, rawTotal);
            const lineTotal = Math.max(0, rawTotal - discountAmount);

            subtotal += rawTotal;
            discount += discountAmount;
            totalHt += lineTotal;

            const lineTotalField = row.querySelector('[data-proforma-line-total]');
            if (lineTotalField) {
                lineTotalField.value = formatAmount(lineTotal);
            }
        });

        const taxRate = numericValue(taxRateField);
        const taxAmount = totalHt * (taxRate / 100);
        const totalTtc = totalHt + taxAmount;

        if (totalFields.subtotal) totalFields.subtotal.textContent = formatAmount(subtotal);
        if (totalFields.discount) totalFields.discount.textContent = formatAmount(discount);
        if (totalFields.ht) totalFields.ht.textContent = formatAmount(totalHt);
        if (totalFields.tax) totalFields.tax.textContent = formatAmount(taxAmount);
        if (totalFields.ttc) totalFields.ttc.textContent = formatAmount(totalTtc);
    };

    const bindLine = (row) => {
        const type = row.querySelector('[data-proforma-line-type]');
        const item = row.querySelector('[data-proforma-item]');
        const service = row.querySelector('[data-proforma-service]');
        const description = row.querySelector('[data-proforma-description]');
        const unitPrice = row.querySelector('[data-proforma-unit-price]');

        initSearchableSelect(item);
        initSearchableSelect(service);

        type?.addEventListener('change', () => {
            refreshLineVisibility(row);
            refreshTotals();
        });

        item?.addEventListener('change', () => {
            const selected = item.selectedOptions[0];
            if (selected?.value) {
                unitPrice.value = selected.dataset.price || unitPrice.value || '0';
                description.value = selected.textContent.replace(/\s*\([^)]*\)\s*$/, '');
            }
            refreshTotals();
        });

        service?.addEventListener('change', () => {
            const selected = service.selectedOptions[0];
            if (selected?.value) {
                unitPrice.value = selected.dataset.price || unitPrice.value || '0';
                description.value = selected.textContent.replace(/\s*\([^)]*\)\s*$/, '');
            }
            refreshTotals();
        });

        row.querySelectorAll('input, select, textarea').forEach((field) => {
            field.addEventListener('input', refreshTotals);
            field.addEventListener('change', refreshTotals);
        });

        refreshLineVisibility(row);
    };

    const rowFromTemplate = () => {
        const fragment = lineTemplate.content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace('__INDEX__', String(lineIndex));
        });
        lineIndex += 1;

        return fragment;
    };

    const appendLine = (line = {}) => {
        const fragment = rowFromTemplate();
        const row = fragment.querySelector('[data-proforma-line-row]');
        const setValue = (selector, value) => {
            const field = row.querySelector(selector);
            if (field) {
                field.value = value ?? '';
            }
        };

        setValue('[data-proforma-line-type]', line.line_type || 'free');
        setValue('[data-proforma-item]', line.item_id || '');
        setValue('[data-proforma-service]', line.service_id || '');
        setValue('[data-proforma-description]', line.description || '');
        setValue('[name$="[details]"]', line.details || '');
        setValue('[data-proforma-quantity]', line.quantity || '1');
        setValue('[data-proforma-cost-price]', line.cost_price || '0');
        setValue('[data-proforma-unit-price]', line.unit_price || '0');
        setValue('[data-proforma-discount-type]', line.discount_type || 'fixed');
        setValue('[data-proforma-discount]', line.discount_amount || '0');
        const createStockItem = row.querySelector('[data-proforma-create-item-field] input[type="checkbox"]');
        if (createStockItem) {
            createStockItem.checked = Boolean(Number(line.create_stock_item || 0));
        }

        lineList?.appendChild(fragment);
        bindLine(row);
    };

    const resetLines = (lines = []) => {
        if (!lineList) {
            return;
        }

        lineList.innerHTML = '';
        lineIndex = 0;
        (lines.length ? lines : [{}]).forEach((line) => appendLine(line));
        refreshTotals();
    };

    const setFieldValue = (field, value = '') => {
        field.value = value ?? field.dataset.defaultValue ?? '';
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setFormMode = (trigger) => {
        const isEdit = trigger?.dataset.proformaMode === 'edit';
        const values = isEdit ? decodeValues(trigger.dataset.proformaValues) : {};

        clearValidationState();
        form.action = isEdit ? trigger.dataset.proformaAction : form.dataset.createAction;
        title.innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-file-earmark-richtext'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.proformaId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        fields.forEach((field) => {
            setFieldValue(field, isEdit ? values[field.name] : field.dataset.defaultValue);
        });

        resetLines(isEdit ? (values.lines || []) : []);
    };

    document.querySelectorAll('[data-proforma-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });

    document.querySelector('[data-add-proforma-line]')?.addEventListener('click', () => {
        appendLine({});
        refreshTotals();
    });

    lineList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-proforma-line]');
        if (!button) return;

        const row = button.closest('[data-proforma-line-row]');
        if (lineList.querySelectorAll('[data-proforma-line-row]').length > 1) {
            row?.remove();
            refreshTotals();
        }
    });

    taxRateField?.addEventListener('input', refreshTotals);
    lineList?.querySelectorAll('[data-proforma-line-row]').forEach(bindLine);
    refreshTotals();
})();
