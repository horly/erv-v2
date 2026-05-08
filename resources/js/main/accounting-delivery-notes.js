(() => {
    const integerQuantity = (value) => {
        const quantity = Number(String(value || '0').replace(',', '.'));

        if (!Number.isFinite(quantity) || quantity <= 0) {
            return 0;
        }

        return Math.floor(quantity);
    };

    document.querySelectorAll('[data-delivery-line-card]').forEach((card) => {
        const quantityField = card.querySelector('[data-delivery-quantity]');
        const section = card.querySelector('[data-delivery-serial-section]');
        const serialList = section?.querySelector('[data-delivery-serial-list]');

        if (!quantityField || !section || !serialList) {
            return;
        }

        const lineIndex = section.dataset.lineIndex;
        const label = section.dataset.serialLabel || 'Serial number';
        const placeholder = section.dataset.serialPlaceholder || '';

        const serialValues = () => Array.from(serialList.querySelectorAll('input'))
            .map((input) => input.value);

        const renderSerialFields = () => {
            const values = serialValues();
            const count = integerQuantity(quantityField.value);

            serialList.innerHTML = '';

            for (let index = 0; index < count; index += 1) {
                const wrapper = document.createElement('label');
                wrapper.className = 'delivery-serial-field';

                const caption = document.createElement('span');
                caption.textContent = `${label} ${index + 1}`;

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.name = `lines[${lineIndex}][serial_numbers][${index}]`;
                input.placeholder = placeholder;
                input.value = values[index] || '';

                wrapper.append(caption, input);
                serialList.appendChild(wrapper);
            }
        };

        quantityField.addEventListener('input', renderSerialFields);
        quantityField.addEventListener('change', renderSerialFields);
        renderSerialFields();
    });
})();
