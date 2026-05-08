(() => {
    const modal = document.getElementById('debtorModal');
    const form = modal?.querySelector('.debtor-form');
    const methodInput = document.getElementById('debtorMethod');
    const modeInput = document.getElementById('debtorFormMode');
    const idInput = document.getElementById('debtorId');
    const submitButton = document.getElementById('debtorSubmit');

    const fields = {
        type: document.getElementById('debtorType'),
        name: document.getElementById('debtorName'),
        phone: document.getElementById('debtorPhone'),
        email: document.getElementById('debtorEmail'),
        address: document.getElementById('debtorAddress'),
        currency: document.getElementById('debtorCurrency'),
        initialAmount: document.getElementById('debtorInitialAmount'),
        receivedAmount: document.getElementById('debtorReceivedAmount'),
        dueDate: document.getElementById('debtorDueDate'),
        status: document.getElementById('debtorStatus'),
        description: document.getElementById('debtorDescription'),
    };

    const defaults = {
        type: fields.type?.value || 'client',
        currency: fields.currency?.value || 'CDF',
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
        const isEdit = trigger?.dataset.debtorMode === 'edit';

        clearValidationState();
        form.action = isEdit ? trigger.dataset.debtorAction : form.dataset.createAction;
        document.getElementById('debtorModalLabel').innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-arrow-down-left-circle'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.debtorId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        setFieldValue(fields.type, isEdit ? trigger.dataset.debtorType : defaults.type);
        setFieldValue(fields.name, isEdit ? trigger.dataset.debtorName : '');
        setFieldValue(fields.phone, isEdit ? trigger.dataset.debtorPhone : '');
        setFieldValue(fields.email, isEdit ? trigger.dataset.debtorEmail : '');
        setFieldValue(fields.address, isEdit ? trigger.dataset.debtorAddress : '');
        setFieldValue(fields.currency, isEdit ? trigger.dataset.debtorCurrency : defaults.currency);
        setFieldValue(fields.initialAmount, isEdit ? trigger.dataset.debtorInitialAmount : '0');
        setFieldValue(fields.receivedAmount, isEdit ? trigger.dataset.debtorReceivedAmount : '0');
        setFieldValue(fields.dueDate, isEdit ? trigger.dataset.debtorDueDate : '');
        setFieldValue(fields.description, isEdit ? trigger.dataset.debtorDescription : '');
        setFieldValue(fields.status, isEdit ? trigger.dataset.debtorStatus : defaults.status);
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-debtor-mode]');

        if (trigger) {
            setFormMode(trigger);
        }
    });
})();
