(() => {
    const form = document.querySelector('.customer-order-form');
    const lineList = document.querySelector('[data-customer-order-line-list]');
    const lineTemplate = document.getElementById('customerOrderLineTemplate');
    const clientSelect = document.querySelector('[data-customer-order-client]');
    const taxRateField = document.getElementById('orderTaxRate');
    const totalFields = {
        subtotal: document.querySelector('[data-order-total-subtotal]'),
        cost: document.querySelector('[data-order-total-cost]'),
        margin: document.querySelector('[data-order-total-margin]'),
        marginRate: document.querySelector('[data-order-total-margin-rate]'),
        discount: document.querySelector('[data-order-total-discount]'),
        ht: document.querySelector('[data-order-total-ht]'),
        tax: document.querySelector('[data-order-total-tax]'),
        ttc: document.querySelector('[data-order-total-ttc]'),
    };
    let lineIndex = lineList?.querySelectorAll('[data-customer-order-line-row]').length || 0;

    if (!form || !lineList || !lineTemplate) {
        return;
    }

    const formatAmount = (value) => Number(value || 0).toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const numericValue = (field) => Number(String(field?.value || '0').replace(',', '.')) || 0;

    const normalizeSearch = (value = '') => String(value)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const syncSearchableSelectLabel = (select, shell) => {
        const label = shell.querySelector('[data-search-label]');
        const selected = select.selectedOptions[0];
        const placeholder = select.options[0]?.textContent?.trim() || '';

        if (label) {
            label.textContent = selected?.value ? selected.textContent.trim() : placeholder;
            label.classList.toggle('is-placeholder', !selected?.value);
        }
    };

    const searchableSelectConfig = (select) => {
        if (select?.matches('[data-customer-order-client]')) {
            return null;
        }

        if (select?.matches('[data-customer-order-item]')) {
            return { selector: '[data-customer-order-item]', rowType: 'item' };
        }

        if (select?.matches('[data-customer-order-service]')) {
            return { selector: '[data-customer-order-service]', rowType: 'service' };
        }

        return null;
    };

    const isSearchableSelectActive = (select, config = searchableSelectConfig(select)) => {
        if (!select || !config) {
            return false;
        }

        const row = select.closest('[data-customer-order-line-row]');
        return row?.querySelector('[data-customer-order-line-type]')?.value === config.rowType;
    };

    const selectedValuesForSearchableGroup = (select) => {
        const config = searchableSelectConfig(select);
        const selectedValues = new Set();

        if (!config) {
            return selectedValues;
        }

        lineList.querySelectorAll(config.selector).forEach((field) => {
            if (field === select || !field.value || !isSearchableSelectActive(field, config)) {
                return;
            }

            selectedValues.add(field.value);
        });

        return selectedValues;
    };

    const refreshSearchableSelectOptions = () => {
        lineList.querySelectorAll('[data-customer-order-item], [data-customer-order-service]').forEach((select) => {
            select.refreshSearchOptions?.();
        });
    };

    const clearDuplicateSelection = (select) => {
        if (!select?.value || !isSearchableSelectActive(select)) {
            return false;
        }

        if (!selectedValuesForSearchableGroup(select).has(select.value)) {
            return false;
        }

        select.value = '';
        return true;
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
            const selectedValues = selectedValuesForSearchableGroup(select);
            let visible = 0;

            optionButtons.forEach((button) => {
                const isAlreadySelected = selectedValues.has(button.dataset.value);
                const matches = !normalized || button.dataset.search.includes(normalized);
                button.hidden = isAlreadySelected || !matches;
                if (!button.hidden) visible += 1;
            });

            empty.hidden = visible > 0;
        };

        select.refreshSearchOptions = () => filterOptions(search.value);

        toggle.addEventListener('click', () => {
            dropdown.hidden = !dropdown.hidden;
            if (!dropdown.hidden) {
                search.focus();
                search.select();
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

        select.addEventListener('change', () => {
            clearDuplicateSelection(select);
            syncSearchableSelectLabel(select, shell);
            refreshSearchableSelectOptions();
        });
        syncSearchableSelectLabel(select, shell);
        filterOptions('');
    };

    const lineDiscountAmount = (row, rawTotal) => {
        const discountValue = numericValue(row.querySelector('[data-customer-order-discount]'));
        const discountType = row.querySelector('[data-customer-order-discount-type]')?.value || 'fixed';

        if (discountType === 'percent') {
            return Math.min(Math.max(discountValue, 0), 100) * rawTotal / 100;
        }

        return Math.min(Math.max(discountValue, 0), rawTotal);
    };

    const refreshLineVisibility = (row) => {
        const type = row.querySelector('[data-customer-order-line-type]')?.value || 'free';
        const itemField = row.querySelector('[data-customer-order-item-field]');
        const serviceField = row.querySelector('[data-customer-order-service-field]');
        const createItemField = row.querySelector('[data-customer-order-create-item-field]');

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

    const applyMarginToUnitPrice = (row) => {
        const costPrice = numericValue(row.querySelector('[data-customer-order-cost-price]'));
        const marginValue = numericValue(row.querySelector('[data-customer-order-margin-value]'));
        const marginType = row.querySelector('[data-customer-order-margin-type]')?.value || 'fixed';
        const unitPrice = row.querySelector('[data-customer-order-unit-price]');

        if (!unitPrice) {
            return;
        }

        const nextPrice = marginType === 'percent'
            ? costPrice + (costPrice * marginValue / 100)
            : costPrice + marginValue;

        unitPrice.value = Number(nextPrice || 0).toFixed(2);
    };

    const refreshTotals = () => {
        let subtotal = 0;
        let costTotal = 0;
        let marginTotal = 0;
        let discount = 0;
        let totalHt = 0;

        lineList.querySelectorAll('[data-customer-order-line-row]').forEach((row) => {
            const quantity = numericValue(row.querySelector('[data-customer-order-quantity]'));
            const costPrice = numericValue(row.querySelector('[data-customer-order-cost-price]'));
            const unitPrice = numericValue(row.querySelector('[data-customer-order-unit-price]'));
            const rawTotal = quantity * unitPrice;
            const lineCostTotal = quantity * costPrice;
            const discountAmount = lineDiscountAmount(row, rawTotal);
            const lineTotal = Math.max(0, rawTotal - discountAmount);
            const lineMargin = lineTotal - lineCostTotal;

            subtotal += rawTotal;
            costTotal += lineCostTotal;
            marginTotal += lineMargin;
            discount += discountAmount;
            totalHt += lineTotal;

            const lineMarginField = row.querySelector('[data-customer-order-line-margin]');
            const lineTotalField = row.querySelector('[data-customer-order-line-total]');
            if (lineMarginField) lineMarginField.value = formatAmount(lineMargin);
            if (lineTotalField) lineTotalField.value = formatAmount(lineTotal);
        });

        const taxRate = numericValue(taxRateField);
        const taxAmount = totalHt * (taxRate / 100);
        const totalTtc = totalHt + taxAmount;
        const marginRate = costTotal > 0 ? marginTotal / costTotal * 100 : 0;

        if (totalFields.subtotal) totalFields.subtotal.textContent = formatAmount(subtotal);
        if (totalFields.cost) totalFields.cost.textContent = formatAmount(costTotal);
        if (totalFields.margin) totalFields.margin.textContent = formatAmount(marginTotal);
        if (totalFields.marginRate) totalFields.marginRate.textContent = `${formatAmount(marginRate)} %`;
        if (totalFields.discount) totalFields.discount.textContent = formatAmount(discount);
        if (totalFields.ht) totalFields.ht.textContent = formatAmount(totalHt);
        if (totalFields.tax) totalFields.tax.textContent = formatAmount(taxAmount);
        if (totalFields.ttc) totalFields.ttc.textContent = formatAmount(totalTtc);
    };

    const bindLine = (row) => {
        const type = row.querySelector('[data-customer-order-line-type]');
        const item = row.querySelector('[data-customer-order-item]');
        const service = row.querySelector('[data-customer-order-service]');
        const description = row.querySelector('[data-customer-order-description]');
        const costPrice = row.querySelector('[data-customer-order-cost-price]');
        const unitPrice = row.querySelector('[data-customer-order-unit-price]');
        const marginType = row.querySelector('[data-customer-order-margin-type]');
        const marginValue = row.querySelector('[data-customer-order-margin-value]');

        initSearchableSelect(item);
        initSearchableSelect(service);

        type?.addEventListener('change', () => {
            refreshLineVisibility(row);
            refreshSearchableSelectOptions();
            refreshTotals();
        });

        const hydrateFromOption = (select) => {
            const selected = select.selectedOptions[0];
            if (selected?.value) {
                costPrice.value = selected.dataset.cost || costPrice.value || '0';
                unitPrice.value = selected.dataset.price || unitPrice.value || '0';
                description.value = selected.textContent.replace(/\s*\([^)]*\)\s*$/, '');
            }
            refreshTotals();
        };

        item?.addEventListener('change', () => hydrateFromOption(item));
        service?.addEventListener('change', () => hydrateFromOption(service));

        costPrice?.addEventListener('input', () => {
            applyMarginToUnitPrice(row);
            refreshTotals();
        });
        marginType?.addEventListener('change', () => {
            applyMarginToUnitPrice(row);
            refreshTotals();
        });
        marginValue?.addEventListener('input', () => {
            applyMarginToUnitPrice(row);
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

    const appendLine = () => {
        const fragment = rowFromTemplate();
        const row = fragment.querySelector('[data-customer-order-line-row]');
        lineList.appendChild(fragment);
        bindLine(row);
        refreshSearchableSelectOptions();
        refreshTotals();
    };

    document.querySelector('[data-add-customer-order-line]')?.addEventListener('click', appendLine);

    lineList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-customer-order-line]');
        if (!button) return;

        const row = button.closest('[data-customer-order-line-row]');
        if (lineList.querySelectorAll('[data-customer-order-line-row]').length > 1) {
            row?.remove();
            refreshSearchableSelectOptions();
            refreshTotals();
        }
    });

    taxRateField?.addEventListener('input', refreshTotals);
    initSearchableSelect(clientSelect);
    lineList.querySelectorAll('[data-customer-order-line-row]').forEach(bindLine);
    refreshTotals();
})();
