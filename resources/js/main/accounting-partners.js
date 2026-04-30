(() => {
    const modal = document.getElementById('partnerModal');
    const form = modal?.querySelector('.partner-form');
    const methodInput = document.getElementById('partnerMethod');
    const modeInput = document.getElementById('partnerFormMode');
    const idInput = document.getElementById('partnerId');
    const submitButton = document.getElementById('partnerSubmit');

    const fields = {
        type: document.getElementById('partnerType'),
        status: document.getElementById('partnerStatus'),
        name: document.getElementById('partnerName'),
        contactName: document.getElementById('partnerContactName'),
        contactPosition: document.getElementById('partnerContactPosition'),
        phone: document.getElementById('partnerPhone'),
        email: document.getElementById('partnerEmail'),
        address: document.getElementById('partnerAddress'),
        website: document.getElementById('partnerWebsite'),
        activityDomain: document.getElementById('partnerActivityDomain'),
        partnershipStartedAt: document.getElementById('partnerStartedAt'),
        notes: document.getElementById('partnerNotes'),
    };

    const defaults = {
        type: fields.type?.value || 'business_referrer',
        status: fields.status?.value || 'active',
    };

    const escapeHtml = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const setFieldValue = (field, value = '') => {
        if (field) {
            field.value = value ?? '';
        }
    };

    const clearValidationState = () => {
        form?.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
            field.classList.remove('is-invalid', 'is-valid');
        });
    };

    const setFormMode = (trigger) => {
        const isEdit = trigger?.dataset.partnerMode === 'edit';

        clearValidationState();
        form.action = isEdit ? trigger.dataset.partnerAction : form.dataset.createAction;
        document.getElementById('partnerModalLabel').innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-diagram-3'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.partnerId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        setFieldValue(fields.type, isEdit ? trigger.dataset.partnerType : defaults.type);
        setFieldValue(fields.status, isEdit ? trigger.dataset.partnerStatus : defaults.status);
        setFieldValue(fields.name, isEdit ? trigger.dataset.partnerName : '');
        setFieldValue(fields.contactName, isEdit ? trigger.dataset.partnerContactName : '');
        setFieldValue(fields.contactPosition, isEdit ? trigger.dataset.partnerContactPosition : '');
        setFieldValue(fields.phone, isEdit ? trigger.dataset.partnerPhone : '');
        setFieldValue(fields.email, isEdit ? trigger.dataset.partnerEmail : '');
        setFieldValue(fields.address, isEdit ? trigger.dataset.partnerAddress : '');
        setFieldValue(fields.website, isEdit ? trigger.dataset.partnerWebsite : '');
        setFieldValue(fields.activityDomain, isEdit ? trigger.dataset.partnerActivityDomain : '');
        setFieldValue(fields.partnershipStartedAt, isEdit ? trigger.dataset.partnerPartnershipStartedAt : '');
        setFieldValue(fields.notes, isEdit ? trigger.dataset.partnerNotes : '');
    };

    document.querySelectorAll('[data-partner-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });
})();
