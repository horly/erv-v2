(() => {
    const modal = document.getElementById('currencyModal');
    const form = modal?.querySelector('.currency-form');
    const methodInput = document.getElementById('currencyMethod');
    const modeInput = document.getElementById('currencyFormMode');
    const idInput = document.getElementById('currencyId');
    const submitButton = document.getElementById('currencySubmit');
    const cancelButton = document.getElementById('currencyCancel');
    const title = document.getElementById('currencyModalLabel');
    const fields = Array.from(form?.querySelectorAll('[data-currency-field]') || []);

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
        const mode = trigger?.dataset.currencyMode || 'create';
        const isEdit = mode === 'edit';
        const isView = mode === 'view';
        const values = (isEdit || isView) ? decodeValues(trigger.dataset.currencyValues) : {};

        clearValidationState();

        form.action = isEdit ? trigger.dataset.currencyAction : form.dataset.createAction;
        title.innerHTML = `<i class="bi ${isView ? 'bi-eye' : (isEdit ? 'bi-pencil' : 'bi-currency-exchange')}" aria-hidden="true"></i>${escapeHtml(isView ? form.dataset.titleView : (isEdit ? form.dataset.titleEdit : form.dataset.titleCreate))}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        submitButton.hidden = isView;
        cancelButton.textContent = isView ? form.dataset.closeLabel : form.dataset.cancelLabel;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = (isEdit || isView) ? trigger.dataset.currencyId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit || isView;
            methodInput.value = 'PUT';
        }

        fields.forEach((field) => {
            setFieldValue(field, (isEdit || isView) ? values[field.name] : field.dataset.defaultValue);
            field.disabled = isView;
        });
    };

    document.querySelectorAll('[data-currency-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });
})();
