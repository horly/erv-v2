(() => {
    const modal = document.getElementById('prospectModal');
    const form = modal?.querySelector('.prospect-form');
    const typeSelect = document.getElementById('prospectType');
    const nameLabel = document.querySelector('label[for="prospectName"]');
    const methodInput = document.getElementById('prospectMethod');
    const modeInput = document.getElementById('prospectFormMode');
    const idInput = document.getElementById('prospectId');
    const submitButton = document.getElementById('prospectSubmit');
    const contactList = document.querySelector('[data-prospect-contact-list]');
    const contactTemplate = document.getElementById('prospectContactTemplate');
    let contactIndex = contactList?.querySelectorAll('[data-prospect-contact-row]').length || 0;

    const fields = {
        name: document.getElementById('prospectName'),
        profession: document.getElementById('prospectProfession'),
        phone: document.getElementById('prospectPhone'),
        email: document.getElementById('prospectEmail'),
        address: document.getElementById('prospectAddress'),
        rccm: document.getElementById('prospectRccm'),
        idNat: document.getElementById('prospectIdNat'),
        nif: document.getElementById('prospectNif'),
        website: document.getElementById('prospectWebsite'),
        source: document.getElementById('prospectSource'),
        status: document.getElementById('prospectStatus'),
        interestLevel: document.getElementById('prospectInterest'),
        notes: document.getElementById('prospectNotes'),
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

    const setProspectType = (type) => {
        if (typeSelect) {
            typeSelect.value = type || 'individual';
        }

        document.querySelectorAll('[data-prospect-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.prospectPanel !== typeSelect.value;
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

        const row = fragment.querySelector('[data-prospect-contact-row]');
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
        const isEdit = trigger?.dataset.prospectMode === 'edit';

        clearValidationState();
        form.action = isEdit ? trigger.dataset.prospectAction : form.dataset.createAction;
        document.getElementById('prospectModalLabel').innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-person-plus'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.prospectId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        const type = isEdit ? trigger.dataset.prospectType : 'individual';
        setProspectType(type);
        setFieldValue(fields.name, isEdit ? trigger.dataset.prospectName : '');
        setFieldValue(fields.profession, isEdit ? trigger.dataset.prospectProfession : '');
        setFieldValue(fields.phone, isEdit ? trigger.dataset.prospectPhone : '');
        setFieldValue(fields.email, isEdit ? trigger.dataset.prospectEmail : '');
        setFieldValue(fields.address, isEdit ? trigger.dataset.prospectAddress : '');
        setFieldValue(fields.rccm, isEdit ? trigger.dataset.prospectRccm : '');
        setFieldValue(fields.idNat, isEdit ? trigger.dataset.prospectIdNat : '');
        setFieldValue(fields.nif, isEdit ? trigger.dataset.prospectNif : '');
        setFieldValue(fields.website, isEdit ? trigger.dataset.prospectWebsite : '');
        setFieldValue(fields.source, isEdit ? trigger.dataset.prospectSource : 'other');
        setFieldValue(fields.status, isEdit ? trigger.dataset.prospectStatus : 'new');
        setFieldValue(fields.interestLevel, isEdit ? trigger.dataset.prospectInterestLevel : 'warm');
        setFieldValue(fields.notes, isEdit ? trigger.dataset.prospectNotes : '');
        resetContacts(isEdit ? parseDatasetJson(trigger.dataset.prospectContacts, []) : []);
    };

    typeSelect?.addEventListener('change', () => setProspectType(typeSelect.value));

    document.querySelectorAll('[data-prospect-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });

    document.querySelector('[data-add-prospect-contact]')?.addEventListener('click', () => {
        contactList?.appendChild(prepareContactRow({}));
    });

    contactList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-prospect-contact]');

        if (!button) {
            return;
        }

        const row = button.closest('[data-prospect-contact-row]');

        if (contactList.querySelectorAll('[data-prospect-contact-row]').length > 1) {
            row?.remove();
            return;
        }

        row?.querySelectorAll('input').forEach((input) => {
            input.value = '';
        });
    });

    setProspectType(typeSelect?.value || 'individual');
})();
