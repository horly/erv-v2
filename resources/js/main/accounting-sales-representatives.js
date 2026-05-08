(() => {
    const modal = document.getElementById('representativeModal');
    const form = modal?.querySelector('.representative-form');
    const methodInput = document.getElementById('representativeMethod');
    const modeInput = document.getElementById('representativeFormMode');
    const idInput = document.getElementById('representativeId');
    const submitButton = document.getElementById('representativeSubmit');

    const fields = {
        type: document.getElementById('representativeType'),
        status: document.getElementById('representativeStatus'),
        name: document.getElementById('representativeName'),
        phone: document.getElementById('representativePhone'),
        email: document.getElementById('representativeEmail'),
        address: document.getElementById('representativeAddress'),
        salesArea: document.getElementById('representativeSalesArea'),
        currency: document.getElementById('representativeCurrency'),
        monthlyTarget: document.getElementById('representativeMonthlyTarget'),
        annualTarget: document.getElementById('representativeAnnualTarget'),
        commissionRate: document.getElementById('representativeCommissionRate'),
        notes: document.getElementById('representativeNotes'),
    };

    const defaults = {
        type: fields.type?.value || 'internal',
        status: fields.status?.value || 'active',
        currency: fields.currency?.value || 'CDF',
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
        const isEdit = trigger?.dataset.representativeMode === 'edit';

        clearValidationState();
        form.action = isEdit ? trigger.dataset.representativeAction : form.dataset.createAction;
        document.getElementById('representativeModalLabel').innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-briefcase'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.representativeId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        setFieldValue(fields.type, isEdit ? trigger.dataset.representativeType : defaults.type);
        setFieldValue(fields.status, isEdit ? trigger.dataset.representativeStatus : defaults.status);
        setFieldValue(fields.name, isEdit ? trigger.dataset.representativeName : '');
        setFieldValue(fields.phone, isEdit ? trigger.dataset.representativePhone : '');
        setFieldValue(fields.email, isEdit ? trigger.dataset.representativeEmail : '');
        setFieldValue(fields.address, isEdit ? trigger.dataset.representativeAddress : '');
        setFieldValue(fields.salesArea, isEdit ? trigger.dataset.representativeSalesArea : '');
        setFieldValue(fields.currency, isEdit ? trigger.dataset.representativeCurrency : defaults.currency);
        setFieldValue(fields.monthlyTarget, isEdit ? trigger.dataset.representativeMonthlyTarget : '0');
        setFieldValue(fields.annualTarget, isEdit ? trigger.dataset.representativeAnnualTarget : '0');
        setFieldValue(fields.commissionRate, isEdit ? trigger.dataset.representativeCommissionRate : '0');
        setFieldValue(fields.notes, isEdit ? trigger.dataset.representativeNotes : '');
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-representative-mode]');

        if (trigger) {
            setFormMode(trigger);
        }
    });
})();
