(() => {
    const modal = document.getElementById('taxModal');
    const form = modal?.querySelector('.tax-form');
    const methodInput = document.getElementById('taxHttpMethod');
    const modeInput = document.getElementById('taxFormMode');
    const idInput = document.getElementById('taxId');
    const submitButton = document.getElementById('taxSubmit');
    const title = document.getElementById('taxModalLabel');
    const fields = Array.from(form?.querySelectorAll('[data-tax-field]') || []);

    if (!form) {
        return;
    }

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
        form.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
            field.classList.remove('is-invalid', 'is-valid');
        });
    };

    const setFieldValue = (field, value = '') => {
        const nextValue = value ?? field.dataset.defaultValue ?? '';

        if (field.type === 'checkbox') {
            field.checked = String(nextValue) === '1';
        } else {
            field.value = String(nextValue);
        }

        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setFormMode = (trigger) => {
        const isEdit = trigger?.dataset.taxMode === 'edit';
        const values = isEdit ? decodeValues(trigger.dataset.taxValues) : {};

        clearValidationState();
        form.action = isEdit ? trigger.dataset.taxAction : form.dataset.createAction;
        title.innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : 'bi-percent'}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.taxId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        fields.forEach((field) => {
            setFieldValue(field, isEdit ? values[field.name] : field.dataset.defaultValue);
        });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-tax-mode]');

        if (trigger) {
            setFormMode(trigger);
        }
    });
})();
