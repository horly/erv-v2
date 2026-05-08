(() => {
    const modal = document.getElementById('supplierModal');
    const form = modal?.querySelector('.supplier-form');
    const typeSelect = document.getElementById('supplierType');
    const nameLabel = document.querySelector('label[for="supplierName"]');
    const methodInput = document.getElementById('supplierMethod');
    const modeInput = document.getElementById('supplierFormMode');
    const idInput = document.getElementById('supplierId');
    const submitButton = document.getElementById('supplierSubmit');
    const contactList = document.querySelector('[data-supplier-contact-list]');
    const contactTemplate = document.getElementById('supplierContactTemplate');
    const addressWrapper = document.querySelector('[data-supplier-address-wrapper]');
    let contactIndex = contactList?.querySelectorAll('[data-supplier-contact-row]').length || 0;

    const fields = {
        name: document.getElementById('supplierName'),
        profession: document.getElementById('supplierProfession'),
        phone: document.getElementById('supplierPhone'),
        email: document.getElementById('supplierEmail'),
        address: document.getElementById('supplierAddress'),
        rccm: document.getElementById('supplierRccm'),
        idNat: document.getElementById('supplierIdNat'),
        nif: document.getElementById('supplierNif'),
        bankName: document.getElementById('supplierBankName'),
        accountNumber: document.getElementById('supplierAccountNumber'),
        currency: document.getElementById('supplierCurrency'),
        website: document.getElementById('supplierWebsite'),
        status: document.getElementById('supplierStatus'),
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

    const setsupplierType = (type) => {
        if (typeSelect) {
            typeSelect.value = type || 'individual';
        }

        document.querySelectorAll('[data-supplier-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.supplierPanel !== typeSelect.value;
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

        const targetRow = document.querySelector(`[data-supplier-panel="${typeSelect.value}"] .row`);

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

        const row = fragment.querySelector('[data-supplier-contact-row]');
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
        const isEdit = trigger?.dataset.supplierMode === 'edit';

        clearValidationState();
        form.action = isEdit ? trigger.dataset.supplierAction : form.dataset.createAction;
        document.getElementById('supplierModalLabel').innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-person-plus'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.supplierId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        const type = isEdit ? trigger.dataset.supplierType : 'individual';
        setsupplierType(type);
        setFieldValue(fields.name, isEdit ? trigger.dataset.supplierName : '');
        setFieldValue(fields.profession, isEdit ? trigger.dataset.supplierProfession : '');
        setFieldValue(fields.phone, isEdit ? trigger.dataset.supplierPhone : '');
        setFieldValue(fields.email, isEdit ? trigger.dataset.supplierEmail : '');
        setFieldValue(fields.address, isEdit ? trigger.dataset.supplierAddress : '');
        setFieldValue(fields.rccm, isEdit ? trigger.dataset.supplierRccm : '');
        setFieldValue(fields.idNat, isEdit ? trigger.dataset.supplierIdNat : '');
        setFieldValue(fields.nif, isEdit ? trigger.dataset.supplierNif : '');
        setFieldValue(fields.bankName, isEdit ? trigger.dataset.supplierBankName : '');
        setFieldValue(fields.accountNumber, isEdit ? trigger.dataset.supplierAccountNumber : '');
        setFieldValue(fields.currency, isEdit ? trigger.dataset.supplierCurrency : '');
        setFieldValue(fields.website, isEdit ? trigger.dataset.supplierWebsite : '');
        setFieldValue(fields.status, isEdit ? trigger.dataset.supplierStatus : 'active');
        resetContacts(isEdit ? parseDatasetJson(trigger.dataset.supplierContacts, []) : []);
    };

    typeSelect?.addEventListener('change', () => setsupplierType(typeSelect.value));

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-supplier-mode]');

        if (trigger) {
            setFormMode(trigger);
        }
    });

    document.querySelector('[data-add-supplier-contact]')?.addEventListener('click', () => {
        contactList?.appendChild(prepareContactRow({}));
    });

    contactList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-supplier-contact]');

        if (!button) {
            return;
        }

        const row = button.closest('[data-supplier-contact-row]');

        if (contactList.querySelectorAll('[data-supplier-contact-row]').length > 1) {
            row?.remove();
            return;
        }

        row?.querySelectorAll('input').forEach((input) => {
            input.value = '';
        });
    });

    setsupplierType(typeSelect?.value || 'individual');
})();

