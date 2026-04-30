(() => {
    const modal = document.getElementById('stockResourceModal');
    const form = modal?.querySelector('.stock-resource-form');
    const methodInput = document.getElementById('stockResourceMethod');
    const modeInput = document.getElementById('stockResourceFormMode');
    const idInput = document.getElementById('stockResourceId');
    const submitButton = document.getElementById('stockResourceSubmit');
    const title = document.getElementById('stockResourceModalLabel');

    const fields = Array.from(form?.querySelectorAll('[data-stock-field]') || []);

    const escapeHtml = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const clearValidationState = () => {
        form?.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
            field.classList.remove('is-invalid', 'is-valid');
        });
    };

    const setFieldValue = (field, value = '') => {
        field.value = value ?? field.dataset.defaultValue ?? '';
    };

    const setFormMode = (trigger) => {
        const isEdit = trigger?.dataset.stockMode === 'edit';
        let values = {};

        clearValidationState();

        if (isEdit && trigger.dataset.stockValues) {
            try {
                values = JSON.parse(trigger.dataset.stockValues);
            } catch {
                values = {};
            }
        }

        form.action = isEdit ? trigger.dataset.stockAction : form.dataset.createAction;
        title.innerHTML = `<i class="bi ${isEdit ? 'bi-pencil' : form.dataset.icon}" aria-hidden="true"></i>${escapeHtml(isEdit ? form.dataset.titleEdit : form.dataset.titleCreate)}`;
        submitButton.textContent = isEdit ? form.dataset.submitEdit : form.dataset.submitCreate;
        modeInput.value = isEdit ? 'edit' : 'create';
        idInput.value = isEdit ? trigger.dataset.stockId : '';

        if (methodInput) {
            methodInput.disabled = !isEdit;
            methodInput.value = 'PUT';
        }

        fields.forEach((field) => setFieldValue(field, isEdit ? values[field.name] : field.dataset.defaultValue));
    };

    document.querySelectorAll('[data-stock-mode]').forEach((trigger) => {
        trigger.addEventListener('click', () => setFormMode(trigger));
    });
})();
