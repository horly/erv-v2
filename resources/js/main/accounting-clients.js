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
    let contactIndex = contactList?.querySelectorAll('[data-client-contact-row]').length || 0;

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

    document.querySelectorAll('[data-client-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
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
