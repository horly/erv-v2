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

    document.querySelectorAll('[data-payment-method-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });
})();
