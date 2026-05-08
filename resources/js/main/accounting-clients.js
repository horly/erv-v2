(() => {
    const modal = document.getElementById('clientModal');
    const form = modal?.querySelector('.client-form');
    const typeSelect = document.getElementById('clientType');
    const nameLabel = document.querySelector('label[for="clientName"]');
    const methodInput = document.getElementById('clientMethod');
    const modeInput = document.getElementById('clientFormMode');
    const idInput = document.getElementById('clientId');
    const submitButton = document.getElementById('clientSubmit');
    const contactList = document.querySelector('[data-client-contact-list]');
    const contactTemplate = document.getElementById('clientContactTemplate');
    const addressWrapper = document.querySelector('[data-client-address-wrapper]');
    const documentsModal = document.getElementById('clientDocumentsModal');
    const documentsTitle = document.getElementById('clientDocumentsModalLabel');
    const documentsSearch = documentsModal?.querySelector('[data-client-documents-search]');
    const documentsBody = documentsModal?.querySelector('[data-client-documents-body]');
    const documentsEmpty = documentsModal?.querySelector('[data-client-documents-empty]');
    const documentsVisibleCount = documentsModal?.querySelector('[data-client-documents-visible-count]');
    const documentsTotalCount = documentsModal?.querySelector('[data-client-documents-total-count]');
    const documentsPagination = documentsModal?.querySelector('[data-client-documents-pagination]');
    const documentsPaginationCount = documentsModal?.querySelector('[data-client-documents-pagination-count]');
    const documentsPaginationNav = documentsModal?.querySelector('[data-client-documents-pagination-nav]');
    let contactIndex = contactList?.querySelectorAll('[data-client-contact-row]').length || 0;
    let documentRows = [];
    let filteredDocumentRows = [];
    let documentSortIndex = 3;
    let documentSortDirection = 'desc';
    let documentPage = 1;
    const documentsPerPage = 5;

    const fields = {
        name: document.getElementById('clientName'),
        profession: document.getElementById('clientProfession'),
        phone: document.getElementById('clientPhone'),
        email: document.getElementById('clientEmail'),
        address: document.getElementById('clientAddress'),
        rccm: document.getElementById('clientRccm'),
        idNat: document.getElementById('clientIdNat'),
        nif: document.getElementById('clientNif'),
        bankName: document.getElementById('clientBankName'),
        accountNumber: document.getElementById('clientAccountNumber'),
        currency: document.getElementById('clientCurrency'),
        website: document.getElementById('clientWebsite'),
    };

    const escapeHtml = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const parseDatasetJson = (value, fallback) => {
        if (!value) {
            return fallback;
        }

        try {
            return JSON.parse(value);
        } catch (error) {
            const decoded = document.createElement('textarea');
            decoded.innerHTML = value;

            try {
                return JSON.parse(decoded.value);
            } catch (secondError) {
                return fallback;
            }
        }
    };

    const normalize = (value = '') => String(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();

    const setFieldValue = (field, value = '') => {
        if (field) {
            field.value = value || '';
        }
    };

    const clearValidationState = () => {
        form?.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
            field.classList.remove('is-invalid', 'is-valid');
        });
    };

    const documentSortValue = (row, index, type) => {
        const raw = index === 0
            ? row.number
            : (index === 1
                ? row.kind
                : (index === 2
                    ? row.reference
                    : (index === 3
                        ? row.date_sort
                        : (index === 4 ? row.total_sort : (index === 5 ? row.status : '')))));

        if (type === 'number') {
            return Number(raw || 0);
        }

        return normalize(raw || '');
    };

    const renderDocumentsPagination = (totalPages) => {
        if (!documentsPagination || !documentsPaginationNav) {
            return;
        }

        documentsPagination.hidden = totalPages <= 1;
        documentsPaginationNav.innerHTML = '';
        if (documentsPaginationCount) {
            documentsPaginationCount.textContent = '';
        }

        if (totalPages <= 1) {
            return;
        }

        const previousLabel = documentsPagination.dataset.previousLabel || 'Previous';
        const nextLabel = documentsPagination.dataset.nextLabel || 'Next';
        const showingLabel = documentsPagination.dataset.showingLabel || 'Showing';
        const toLabel = documentsPagination.dataset.toLabel || 'to';
        const onLabel = documentsPagination.dataset.onLabel || 'of';
        const createButton = (label, page, disabled = false, active = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = label;
            button.disabled = disabled;
            button.className = active ? 'active' : '';
            button.addEventListener('click', () => {
                documentPage = page;
                renderDocumentsRows();
            });

            return button;
        };

        const start = ((documentPage - 1) * documentsPerPage) + 1;
        const end = Math.min(documentPage * documentsPerPage, filteredDocumentRows.length);

        if (documentsPaginationCount) {
            documentsPaginationCount.textContent = `${showingLabel} ${start} ${toLabel} ${end} ${onLabel} ${filteredDocumentRows.length}`;
        }

        documentsPaginationNav.append(createButton(previousLabel, Math.max(1, documentPage - 1), documentPage === 1));

        for (let page = 1; page <= totalPages; page += 1) {
            documentsPaginationNav.append(createButton(String(page), page, false, page === documentPage));
        }

        documentsPaginationNav.append(createButton(nextLabel, Math.min(totalPages, documentPage + 1), documentPage === totalPages));
    };

    const renderDocumentsRows = () => {
        if (!documentsBody || !documentsEmpty) {
            return;
        }

        const query = normalize(documentsSearch?.value || '');
        filteredDocumentRows = documentRows
            .filter((row) => normalize(`${row.kind} ${row.reference} ${row.date} ${row.total} ${row.status}`).includes(query))
            .sort((left, right) => {
                const sortButton = documentsModal?.querySelector(`[data-client-documents-sort="${documentSortIndex}"]`);
                const sortType = sortButton?.dataset.sortType || 'text';
                const leftValue = documentSortValue(left, documentSortIndex, sortType);
                const rightValue = documentSortValue(right, documentSortIndex, sortType);
                const direction = documentSortDirection === 'asc' ? 1 : -1;

                if (leftValue === rightValue) {
                    return 0;
                }

                return leftValue > rightValue ? direction : -direction;
            });

        const totalPages = Math.max(1, Math.ceil(filteredDocumentRows.length / documentsPerPage));
        documentPage = Math.min(documentPage, totalPages);

        const pageRows = filteredDocumentRows.slice((documentPage - 1) * documentsPerPage, documentPage * documentsPerPage);

        documentsBody.innerHTML = pageRows.map((row) => `
            <tr>
                <td data-sort-value="${escapeHtml(row.number)}">${escapeHtml(row.number)}</td>
                <td>${escapeHtml(row.kind)}</td>
                <td>${escapeHtml(row.reference)}</td>
                <td data-sort-value="${escapeHtml(row.date_sort)}">${escapeHtml(row.date)}</td>
                <td class="amount-cell text-end" data-sort-value="${escapeHtml(row.total_sort)}">${escapeHtml(row.total)}</td>
                <td><span class="status-pill ${escapeHtml(row.status_class || 'status-neutral')}">${escapeHtml(row.status)}</span></td>
                <td class="text-end">
                    ${row.print_url
                        ? `<a class="table-button table-button-print" href="${escapeHtml(row.print_url)}" target="_blank" rel="noopener" aria-label="${escapeHtml(row.print_label || 'Print')}" title="${escapeHtml(row.print_label || 'Print')}"><i class="bi bi-printer" aria-hidden="true"></i></a>`
                        : '<span class="muted-dash">-</span>'}
                </td>
            </tr>
        `).join('');

        if (documentsVisibleCount) {
            documentsVisibleCount.textContent = String(filteredDocumentRows.length);
        }

        if (documentsTotalCount) {
            documentsTotalCount.textContent = String(documentRows.length);
        }

        documentsEmpty.hidden = filteredDocumentRows.length > 0;
        renderDocumentsPagination(totalPages);
    };

    const openDocumentsModal = (trigger) => {
        documentRows = parseDatasetJson(trigger.dataset.clientDocuments, []).map((row, index) => ({
            ...row,
            number: index + 1,
        }));
        filteredDocumentRows = [];
        documentSortIndex = 3;
        documentSortDirection = 'desc';
        documentPage = 1;

        if (documentsTitle) {
            documentsTitle.innerHTML = `<i class="bi bi-folder2-open" aria-hidden="true"></i>${escapeHtml(trigger.dataset.clientDocumentsTitle || '')}`;
        }

        if (documentsSearch) {
            documentsSearch.value = '';
        }

        renderDocumentsRows();
    };

    const setClientType = (type) => {
        if (typeSelect) {
            typeSelect.value = type || 'individual';
        }

        document.querySelectorAll('[data-client-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.clientPanel !== typeSelect.value;
        });

        if (nameLabel) {
            nameLabel.textContent = typeSelect.value === 'company'
                ? nameLabel.dataset.labelCompany
                : nameLabel.dataset.labelIndividual;
        }

        if (fields.name) {
            fields.name.placeholder = typeSelect.value === 'company'
                ? fields.name.dataset.placeholderCompany
                : fields.name.dataset.placeholderIndividual;
        }

        const targetRow = document.querySelector(`[data-client-panel="${typeSelect.value}"] .row`);

        if (addressWrapper && targetRow) {
            targetRow.appendChild(addressWrapper);
        }
    };

    const prepareContactRow = (contact = {}) => {
        const fragment = contactTemplate.content.cloneNode(true);
        fragment.querySelectorAll('[data-name]').forEach((field) => {
            field.name = field.dataset.name.replace('__INDEX__', String(contactIndex));
            field.removeAttribute('data-name');
        });

        const row = fragment.querySelector('[data-client-contact-row]');
        row.querySelector('[name$="[full_name]"]').value = contact.full_name || '';
        row.querySelector('[name$="[position]"]').value = contact.position || '';
        row.querySelector('[name$="[department]"]').value = contact.department || '';
        row.querySelector('[name$="[email]"]').value = contact.email || '';
        row.querySelector('[name$="[phone]"]').value = contact.phone || '';
        contactIndex += 1;

        return fragment;
    };

    const resetContacts = (contacts = []) => {
        if (!contactList || !contactTemplate) {
            return;
        }

        contactList.innerHTML = '';
        contactIndex = 0;

        const rows = contacts.length ? contacts : [{}];
        rows.forEach((contact) => contactList.appendChild(prepareContactRow(contact)));
    };

    const setFormMode = (trigger) => {
        const isEdit = trigger?.dataset.clientMode === 'edit';

        clearValidationState();
        form.action = isEdit ? trigger.dataset.clientAction : form.dataset.createAction;
        document.getElementById('clientModalLabel').innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-person-plus'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.clientId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        const type = isEdit ? trigger.dataset.clientType : 'individual';
        setClientType(type);
        setFieldValue(fields.name, isEdit ? trigger.dataset.clientName : '');
        setFieldValue(fields.profession, isEdit ? trigger.dataset.clientProfession : '');
        setFieldValue(fields.phone, isEdit ? trigger.dataset.clientPhone : '');
        setFieldValue(fields.email, isEdit ? trigger.dataset.clientEmail : '');
        setFieldValue(fields.address, isEdit ? trigger.dataset.clientAddress : '');
        setFieldValue(fields.rccm, isEdit ? trigger.dataset.clientRccm : '');
        setFieldValue(fields.idNat, isEdit ? trigger.dataset.clientIdNat : '');
        setFieldValue(fields.nif, isEdit ? trigger.dataset.clientNif : '');
        setFieldValue(fields.bankName, isEdit ? trigger.dataset.clientBankName : '');
        setFieldValue(fields.accountNumber, isEdit ? trigger.dataset.clientAccountNumber : '');
        setFieldValue(fields.currency, isEdit ? trigger.dataset.clientCurrency : '');
        setFieldValue(fields.website, isEdit ? trigger.dataset.clientWebsite : '');
        resetContacts(isEdit ? parseDatasetJson(trigger.dataset.clientContacts, []) : []);
    };

    typeSelect?.addEventListener('change', () => setClientType(typeSelect.value));

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-client-mode]');

        if (trigger) {
            setFormMode(trigger);
        }
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-client-documents-trigger]');

        if (trigger) {
            openDocumentsModal(trigger);
        }
    });

    documentsSearch?.addEventListener('input', () => {
        documentPage = 1;
        renderDocumentsRows();
    });

    documentsModal?.querySelectorAll('[data-client-documents-sort]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextIndex = Number(button.dataset.clientDocumentsSort);

            if (documentSortIndex === nextIndex) {
                documentSortDirection = documentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                documentSortIndex = nextIndex;
                documentSortDirection = button.dataset.sortType === 'date' ? 'desc' : 'asc';
            }

            renderDocumentsRows();
        });
    });

    document.querySelector('[data-add-client-contact]')?.addEventListener('click', () => {
        contactList?.appendChild(prepareContactRow({}));
    });

    contactList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-client-contact]');

        if (!button) {
            return;
        }

        const row = button.closest('[data-client-contact-row]');

        if (contactList.querySelectorAll('[data-client-contact-row]').length > 1) {
            row?.remove();
            return;
        }

        row?.querySelectorAll('input').forEach((input) => {
            input.value = '';
        });
    });

    setClientType(typeSelect?.value || 'individual');
})();
