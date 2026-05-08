(() => {
    const modal = document.getElementById('paymentMethodModal');
    const form = modal?.querySelector('.payment-method-form');
    const methodInput = document.getElementById('paymentMethodHttpMethod');
    const modeInput = document.getElementById('paymentMethodFormMode');
    const idInput = document.getElementById('paymentMethodId');
    const submitButton = document.getElementById('paymentMethodSubmit');
    const cancelButton = document.getElementById('paymentMethodCancel');
    const title = document.getElementById('paymentMethodModalLabel');
    const typeField = document.getElementById('payment_method_type');
    const bankSection = modal?.querySelector('[data-bank-fields]');
    const fields = Array.from(form?.querySelectorAll('[data-payment-method-field]') || []);
    const bankFieldNames = ['bank_name', 'account_holder', 'account_number', 'iban', 'bic_swift', 'bank_address'];

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

    const clearValidationState = () => {
        form?.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
            field.classList.remove('is-invalid', 'is-valid');
        });
    };

    const setBankSectionVisibility = () => {
        if (!bankSection || !typeField) {
            return;
        }

        const isBank = typeField.value === 'bank';
        bankSection.hidden = !isBank;

        if (!isBank) {
            fields
                .filter((field) => bankFieldNames.includes(field.name))
                .forEach((field) => {
                    field.value = '';
                });
        }
    };

    const setFieldValue = (field, value = '') => {
        const nextValue = value ?? field.dataset.defaultValue ?? '';

        if (field.type === 'checkbox') {
            field.checked = String(nextValue) === '1';
            field.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        field.value = String(nextValue);

        if (field.tagName === 'SELECT' && field.value !== String(nextValue)) {
            const matchingOption = Array.from(field.options).find((option) => option.value === String(nextValue));

            if (matchingOption) {
                matchingOption.selected = true;
            }
        }

        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setFormMode = (trigger) => {
        const mode = trigger?.dataset.paymentMethodMode || 'create';
        const isEdit = mode === 'edit';
        const isView = mode === 'view';
        const values = (isEdit || isView) ? decodeValues(trigger.dataset.paymentMethodValues) : {};

        clearValidationState();

        form.action = isEdit ? trigger.dataset.paymentMethodAction : form.dataset.createAction;
        title.innerHTML = `<i class="bi ${isView ? 'bi-eye' : (isEdit ? 'bi-pencil' : 'bi-credit-card-2-front')}" aria-hidden="true"></i>${escapeHtml(isView ? form.dataset.titleView : (isEdit ? form.dataset.titleEdit : form.dataset.titleCreate))}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        submitButton.hidden = isView;
        cancelButton.textContent = isView ? form.dataset.closeLabel : form.dataset.cancelLabel;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = (isEdit || isView) ? trigger.dataset.paymentMethodId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit || isView;
            methodInput.value = 'PUT';
        }

        fields.forEach((field) => {
            setFieldValue(field, (isEdit || isView) ? values[field.name] : field.dataset.defaultValue);
            field.disabled = isView;
        });

        setBankSectionVisibility();
    };

    typeField?.addEventListener('change', setBankSectionVisibility);

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-payment-method-mode]');

        if (trigger) {
            setFormMode(trigger);
        }
    });

    const normalize = (value = '') => String(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();

    const initReceiptsTable = (wrapper) => {
        if (!wrapper || wrapper.dataset.receiptsReady === '1') {
            return;
        }

        wrapper.dataset.receiptsReady = '1';

        const searchInput = wrapper.querySelector('[data-payment-method-receipts-search]');
        const body = wrapper.querySelector('[data-payment-method-receipts-body]');
        const emptyState = wrapper.querySelector('[data-payment-method-receipts-empty]');
        const visibleCount = wrapper.querySelector('[data-payment-method-receipts-visible-count]');
        const totalCount = wrapper.querySelector('[data-payment-method-receipts-total-count]');
        const pagination = wrapper.querySelector('[data-payment-method-receipts-pagination]');
        const sortButtons = Array.from(wrapper.querySelectorAll('[data-payment-method-receipts-sort]'));
        const rows = Array.from(wrapper.querySelectorAll('[data-payment-method-receipt-row]'));
        const perPage = 5;
        const state = { page: 1, sortIndex: null, sortDirection: 'asc' };

        const cellSortValue = (row, index, type) => {
            const cell = row.children[index];
            const raw = cell?.dataset.sortValue || cell?.textContent || '';

            if (type === 'number') {
                return Number(String(raw).replace(/\s/g, '').replace(',', '.')) || 0;
            }

            return normalize(raw);
        };

        const matchingRows = () => {
            const term = normalize(searchInput?.value || '');
            let filteredRows = rows.filter((row) => normalize(row.textContent).includes(term));

            if (state.sortIndex !== null) {
                const sortButton = sortButtons.find((button) => button.dataset.paymentMethodReceiptsSort === String(state.sortIndex));
                const type = sortButton?.dataset.sortType || 'text';
                const direction = state.sortDirection === 'asc' ? 1 : -1;

                filteredRows = filteredRows.sort((left, right) => {
                    const leftValue = cellSortValue(left, state.sortIndex, type);
                    const rightValue = cellSortValue(right, state.sortIndex, type);

                    if (leftValue === rightValue) {
                        return 0;
                    }

                    return leftValue > rightValue ? direction : -direction;
                });
            }

            return filteredRows;
        };

        const renderPagination = (totalPages) => {
            if (!pagination) {
                return;
            }

            pagination.hidden = totalPages <= 1;
            pagination.innerHTML = '';

            if (totalPages <= 1) {
                return;
            }

            const previousLabel = pagination.dataset.previousLabel || 'Previous';
            const nextLabel = pagination.dataset.nextLabel || 'Next';
            const createButton = (label, page, disabled = false, active = false) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.disabled = disabled;
                button.className = active ? 'active' : '';
                button.addEventListener('click', () => {
                    state.page = page;
                    render();
                });

                return button;
            };

            pagination.append(createButton(previousLabel, Math.max(1, state.page - 1), state.page === 1));

            for (let page = 1; page <= totalPages; page += 1) {
                pagination.append(createButton(String(page), page, false, page === state.page));
            }

            pagination.append(createButton(nextLabel, Math.min(totalPages, state.page + 1), state.page === totalPages));
        };

        const render = () => {
            const filteredRows = matchingRows();
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));

            state.page = Math.min(state.page, totalPages);
            body.innerHTML = '';

            filteredRows
                .slice((state.page - 1) * perPage, state.page * perPage)
                .forEach((row) => body.append(row));

            if (visibleCount) {
                visibleCount.textContent = String(filteredRows.length);
            }

            if (totalCount) {
                totalCount.textContent = String(rows.length);
            }

            if (emptyState) {
                emptyState.hidden = filteredRows.length > 0;
            }

            renderPagination(totalPages);
        };

        searchInput?.addEventListener('input', () => {
            state.page = 1;
            render();
        });

        sortButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const nextIndex = Number(button.dataset.paymentMethodReceiptsSort);

                if (state.sortIndex === nextIndex) {
                    state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortIndex = nextIndex;
                    state.sortDirection = 'asc';
                }

                render();
            });
        });

        render();
    };

    const initReceiptsTables = () => {
        document.querySelectorAll('[data-payment-method-receipts-table]').forEach(initReceiptsTable);
    };

    initReceiptsTables();
    document.addEventListener('exad:table-updated', initReceiptsTables);
})();
