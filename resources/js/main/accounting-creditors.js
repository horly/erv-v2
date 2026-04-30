(() => {
    const modal = document.getElementById('creditorModal');
    const form = modal?.querySelector('.creditor-form');
    const methodInput = document.getElementById('creditorMethod');
    const modeInput = document.getElementById('creditorFormMode');
    const idInput = document.getElementById('creditorId');
    const submitButton = document.getElementById('creditorSubmit');

    const fields = {
        type: document.getElementById('creditorType'),
        name: document.getElementById('creditorName'),
        phone: document.getElementById('creditorPhone'),
        email: document.getElementById('creditorEmail'),
        address: document.getElementById('creditorAddress'),
        currency: document.getElementById('creditorCurrency'),
        initialAmount: document.getElementById('creditorInitialAmount'),
        paidAmount: document.getElementById('creditorPaidAmount'),
        dueDate: document.getElementById('creditorDueDate'),
        priority: document.getElementById('creditorPriority'),
        status: document.getElementById('creditorStatus'),
        description: document.getElementById('creditorDescription'),
    };

    const defaults = {
        type: fields.type?.value || 'supplier',
        currency: fields.currency?.value || 'CDF',
        priority: fields.priority?.value || 'normal',
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
        const isEdit = trigger?.dataset.creditorMode === 'edit';

        clearValidationState();
        form.action = isEdit ? trigger.dataset.creditorAction : form.dataset.createAction;
        document.getElementById('creditorModalLabel').innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-arrow-up-right-circle'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.creditorId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        setFieldValue(fields.type, isEdit ? trigger.dataset.creditorType : defaults.type);
        setFieldValue(fields.name, isEdit ? trigger.dataset.creditorName : '');
        setFieldValue(fields.phone, isEdit ? trigger.dataset.creditorPhone : '');
        setFieldValue(fields.email, isEdit ? trigger.dataset.creditorEmail : '');
        setFieldValue(fields.address, isEdit ? trigger.dataset.creditorAddress : '');
        setFieldValue(fields.currency, isEdit ? trigger.dataset.creditorCurrency : defaults.currency);
        setFieldValue(fields.initialAmount, isEdit ? trigger.dataset.creditorInitialAmount : '0');
        setFieldValue(fields.paidAmount, isEdit ? trigger.dataset.creditorPaidAmount : '0');
        setFieldValue(fields.dueDate, isEdit ? trigger.dataset.creditorDueDate : '');
        setFieldValue(fields.description, isEdit ? trigger.dataset.creditorDescription : '');
        setFieldValue(fields.priority, isEdit ? trigger.dataset.creditorPriority : defaults.priority);
        setFieldValue(fields.status, isEdit ? trigger.dataset.creditorStatus : defaults.status);
    };

    document.querySelectorAll('[data-creditor-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });
})();
