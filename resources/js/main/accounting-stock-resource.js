(() => {
    const modal = document.getElementById('stockResourceModal');
    const form = modal?.querySelector('.stock-resource-form');
    const methodInput = document.getElementById('stockResourceMethod');
    const modeInput = document.getElementById('stockResourceFormMode');
    const idInput = document.getElementById('stockResourceId');
    const submitButton = document.getElementById('stockResourceSubmit');
    const cancelButton = document.getElementById('stockResourceCancel');
    const title = document.getElementById('stockResourceModalLabel');

    const fields = Array.from(form?.querySelectorAll('[data-stock-field]') || []);
    const categorySelect = form?.querySelector('[name="category_id"]');
    const subcategorySelect = form?.querySelector('[name="subcategory_id"]');
    const warehouseSelect = form?.querySelector('[name="default_warehouse_id"]');
    const relatedModal = document.getElementById('stockRelatedModal');
    const relatedTitle = document.getElementById('stockRelatedModalLabel');
    const relatedSearch = relatedModal?.querySelector('[data-related-search]');
    const relatedBody = relatedModal?.querySelector('[data-related-table-body]');
    const relatedEmpty = relatedModal?.querySelector('[data-related-empty]');
    const relatedVisibleCount = relatedModal?.querySelector('[data-related-visible-count]');
    const relatedTotalCount = relatedModal?.querySelector('[data-related-total-count]');
    const relatedPagination = relatedModal?.querySelector('[data-related-pagination]');
    let relatedRows = [];
    let relatedFilteredRows = [];
    let relatedSortKey = 'reference';
    let relatedSortDirection = 'asc';
    let relatedPage = 1;
    let relatedKind = 'subcategories';
    const relatedPerPage = 5;

    const escapeHtml = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const clearValidationState = () => {
        form?.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
            field.classList.remove('is-invalid', 'is-valid');
        });
    };

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

    const setFieldValue = (field, value = '') => {
        const nextValue = value ?? field.dataset.defaultValue ?? '';

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
        const mode = trigger?.dataset.stockMode || 'create';
        const isEdit = mode === 'edit';
        const isView = mode === 'view';
        let values = {};

        clearValidationState();

        if ((isEdit || isView) && trigger.dataset.stockValues) {
            values = decodeValues(trigger.dataset.stockValues);
        }

        form.action = isEdit ? trigger.dataset.stockAction : form.dataset.createAction;
        title.innerHTML = `<i class="bi ${isView ? 'bi-eye' : (isEdit ? 'bi-pencil' : form.dataset.icon)}" aria-hidden="true"></i>${escapeHtml(isView ? form.dataset.titleView : (isEdit ? form.dataset.titleEdit : form.dataset.titleCreate))}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        submitButton.hidden = isView;
        cancelButton.textContent = isView ? form.dataset.closeLabel : form.dataset.cancelLabel;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = (isEdit || isView) ? trigger.dataset.stockId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit || isView;
            methodInput.value = 'PUT';
        }

        fields.forEach((field) => {
            setFieldValue(field, (isEdit || isView) ? values[field.name] : field.dataset.defaultValue);
            field.disabled = isView;
        });
    };

    const syncWarehouseFromCategory = () => {
        if (!categorySelect || !warehouseSelect) {
            return;
        }

        const selectedCategory = categorySelect.selectedOptions[0];
        const warehouseId = selectedCategory?.dataset.warehouseId;

        if (warehouseId) {
            warehouseSelect.value = warehouseId;
            warehouseSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const syncCategoryFromSubcategory = () => {
        if (!subcategorySelect || !categorySelect) {
            return;
        }

        const selectedSubcategory = subcategorySelect.selectedOptions[0];
        const categoryId = selectedSubcategory?.dataset.categoryId;

        if (categoryId) {
            categorySelect.value = categoryId;
            categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const renderRelatedRows = () => {
        if (!relatedBody || !relatedEmpty || !relatedPagination) {
            return;
        }

        const query = (relatedSearch?.value || '').toLowerCase().trim();
        relatedFilteredRows = relatedRows
            .filter((row) => Object.values(row).some((value) => String(value || '').toLowerCase().includes(query)))
            .sort((first, second) => {
                const firstValue = String(first[relatedSortKey] || '');
                const secondValue = String(second[relatedSortKey] || '');

                return relatedSortDirection === 'asc'
                    ? firstValue.localeCompare(secondValue, undefined, { numeric: true })
                    : secondValue.localeCompare(firstValue, undefined, { numeric: true });
            });

        const totalPages = Math.max(1, Math.ceil(relatedFilteredRows.length / relatedPerPage));
        relatedPage = Math.min(relatedPage, totalPages);
        const pageRows = relatedFilteredRows.slice((relatedPage - 1) * relatedPerPage, relatedPage * relatedPerPage);

        relatedBody.innerHTML = pageRows.map((row) => `
            <tr>
                <td>${escapeHtml(row.reference)}</td>
                <td>${escapeHtml(row.name)}</td>
                <td>${escapeHtml(row.unit)}</td>
                ${relatedKind === 'categories'
                    ? `<td>${escapeHtml(row.subcategory)}</td>`
                    : `<td class="text-end amount-cell">${escapeHtml(row.sale_price)}</td>`}
            </tr>
        `).join('');

        relatedEmpty.hidden = relatedFilteredRows.length > 0;
        if (relatedVisibleCount) relatedVisibleCount.textContent = String(relatedFilteredRows.length);
        if (relatedTotalCount) relatedTotalCount.textContent = String(relatedRows.length);

        if (relatedFilteredRows.length > relatedPerPage) {
            const previousLabel = relatedPagination.dataset.previousLabel || 'Previous';
            const nextLabel = relatedPagination.dataset.nextLabel || 'Next';
            relatedPagination.hidden = false;
            relatedPagination.innerHTML = `
                <button type="button" ${relatedPage === 1 ? 'disabled' : ''} data-related-page="${relatedPage - 1}">${escapeHtml(previousLabel)}</button>
                ${Array.from({ length: totalPages }, (_, index) => {
                    const page = index + 1;
                    return `<button type="button" class="${page === relatedPage ? 'active' : ''}" data-related-page="${page}">${page}</button>`;
                }).join('')}
                <button type="button" ${relatedPage === totalPages ? 'disabled' : ''} data-related-page="${relatedPage + 1}">${escapeHtml(nextLabel)}</button>
            `;
        } else {
            relatedPagination.hidden = true;
            relatedPagination.innerHTML = '';
        }
    };

    const openRelatedModal = (trigger) => {
        relatedRows = decodeValues(trigger.dataset.stockRelatedRows || '');
        relatedPage = 1;
        relatedSortKey = 'reference';
        relatedSortDirection = 'asc';
        relatedKind = trigger.dataset.stockRelatedKind || 'subcategories';

        if (relatedTitle) {
            relatedTitle.innerHTML = `<i class="bi bi-box-seam" aria-hidden="true"></i>${escapeHtml(trigger.dataset.stockRelatedTitle || '')}`;
        }

        if (relatedEmpty) {
            relatedEmpty.textContent = trigger.dataset.stockRelatedEmpty || relatedEmpty.textContent;
        }

        if (relatedSearch) {
            relatedSearch.value = '';
        }

        renderRelatedRows();
    };

    document.querySelectorAll('[data-stock-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });

    categorySelect?.addEventListener('change', syncWarehouseFromCategory);
    subcategorySelect?.addEventListener('change', syncCategoryFromSubcategory);

    document.querySelectorAll('[data-stock-related-rows]').forEach((trigger) => {
        trigger.addEventListener('click', () => openRelatedModal(trigger));
    });

    relatedSearch?.addEventListener('input', () => {
        relatedPage = 1;
        renderRelatedRows();
    });

    relatedModal?.querySelectorAll('[data-related-sort]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextKey = button.dataset.relatedSort;
            relatedSortDirection = relatedSortKey === nextKey && relatedSortDirection === 'asc' ? 'desc' : 'asc';
            relatedSortKey = nextKey;
            renderRelatedRows();
        });
    });

    relatedPagination?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-related-page]');

        if (!button || button.disabled) {
            return;
        }

        relatedPage = Number(button.dataset.relatedPage || '1');
        renderRelatedRows();
    });
})();
