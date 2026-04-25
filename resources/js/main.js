(() => {
    const shell = document.querySelector('.main-shell');
    const root = document.documentElement;
    const themeButton = document.getElementById('themeButton');
    const languageMenu = document.querySelector('.language-menu');
    const languageButton = document.getElementById('languageButton');
    const profileMenu = document.querySelector('.profile-menu');
    const profileButton = document.getElementById('profileButton');
    const searchInput = document.getElementById('companySearch');
    const table = document.getElementById('companyTable');
    const visibleCount = document.getElementById('visibleCount');
    const flashToast = document.querySelector('.flash-toast');
    const subscriptionForm = document.querySelector('.subscription-form');
    const storageKey = 'exad-theme';

    const applyTheme = (theme) => {
        const darkMode = theme === 'dark';
        root.dataset.theme = theme;
        document.body.dataset.theme = theme;
        shell.dataset.theme = theme;
        themeButton.setAttribute('aria-label', darkMode ? 'Activer le mode clair' : 'Activer le mode sombre');
        themeButton.setAttribute('title', darkMode ? 'Mode clair' : 'Mode sombre');
        themeButton.innerHTML = darkMode
            ? '<i class="bi bi-moon-stars-fill" aria-hidden="true"></i>'
            : '<i class="bi bi-brightness-high-fill" aria-hidden="true"></i>';
    };

    const closeMenus = (except = null) => {
        if (except !== 'language') {
            languageMenu?.classList.remove('open');
            languageButton?.setAttribute('aria-expanded', 'false');
        }

        if (except !== 'profile') {
            profileMenu?.classList.remove('open');
            profileButton?.setAttribute('aria-expanded', 'false');
        }
    };

    applyTheme(localStorage.getItem(storageKey) || root.dataset.theme || 'light');

    themeButton?.addEventListener('click', () => {
        const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(storageKey, nextTheme);
        applyTheme(nextTheme);
    });

    languageButton?.addEventListener('click', () => {
        const isOpen = languageMenu.classList.toggle('open');
        languageButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        closeMenus('language');
    });

    profileButton?.addEventListener('click', () => {
        const isOpen = profileMenu.classList.toggle('open');
        profileButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        closeMenus('profile');
    });

    document.addEventListener('click', (event) => {
        if (!languageMenu?.contains(event.target) && !profileMenu?.contains(event.target)) {
            closeMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenus();
        }
    });

    searchInput?.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        let visible = 0;

        table?.querySelectorAll('tbody tr').forEach((row) => {
            if (row.classList.contains('empty-row')) {
                return;
            }

            const match = row.textContent.toLowerCase().includes(query);
            row.hidden = !match;
            visible += match ? 1 : 0;
        });

        if (visibleCount) {
            visibleCount.textContent = String(visible);
        }
    });

    if (flashToast) {
        const autohideDuration = Number(flashToast.dataset.autohide || 15000);
        flashToast.style.setProperty('--flash-duration', `${autohideDuration}ms`);

        const hideToast = () => {
            flashToast.classList.add('is-hiding');
            window.setTimeout(() => flashToast.remove(), 220);
        };

        flashToast.querySelector('.flash-close')?.addEventListener('click', hideToast);
        window.setTimeout(hideToast, autohideDuration);
    }

    if (subscriptionForm) {
        const requiredFields = Array.from(subscriptionForm.querySelectorAll('[data-required-message]'));
        const modalTitle = document.getElementById('subscriptionModalLabel');
        const submitButton = document.getElementById('subscriptionSubmit');
        const methodInput = document.getElementById('subscriptionMethod');
        const modeInput = document.getElementById('subscriptionFormMode');
        const idInput = document.getElementById('subscriptionId');
        const nameInput = document.getElementById('subscriptionName');
        const typeInput = document.getElementById('subscriptionType');
        const expiryInput = document.getElementById('subscriptionExpiry');

        const setFieldState = (field, state) => {
            field.classList.toggle('is-invalid', state === 'invalid');
            field.classList.toggle('is-valid', state === 'valid');
        };

        const validateField = (field, showEmptyError = true) => {
            const hasValue = field.value.trim() !== '';

            if (!hasValue) {
                setFieldState(field, showEmptyError ? 'invalid' : null);
                return false;
            }

            setFieldState(field, 'valid');
            return true;
        };

        requiredFields.forEach((field) => {
            field.addEventListener('input', () => validateField(field, false));
            field.addEventListener('change', () => validateField(field, false));
            field.addEventListener('blur', () => validateField(field));
        });

        const clearFieldStates = () => {
            subscriptionForm.querySelectorAll('.is-invalid, .is-valid').forEach((field) => {
                field.classList.remove('is-invalid', 'is-valid');
            });
        };

        const setSubscriptionFormMode = (trigger) => {
            const isEdit = trigger.dataset.subscriptionMode === 'edit';

            clearFieldStates();
            subscriptionForm.action = isEdit ? trigger.dataset.subscriptionAction : subscriptionForm.dataset.createAction;
            modalTitle.textContent = isEdit ? subscriptionForm.dataset.titleEdit : subscriptionForm.dataset.titleCreate;
            submitButton.textContent = isEdit ? subscriptionForm.dataset.submitEdit : subscriptionForm.dataset.submitCreate;
            modeInput.value = isEdit ? 'edit' : 'create';
            idInput.value = isEdit ? trigger.dataset.subscriptionId : '';

            if (methodInput) {
                methodInput.disabled = !isEdit;
                methodInput.value = 'PUT';
            }

            nameInput.value = isEdit ? trigger.dataset.subscriptionName : '';
            typeInput.value = isEdit ? trigger.dataset.subscriptionType : 'standard';
            expiryInput.value = isEdit ? trigger.dataset.subscriptionExpiresAt : expiryInput.defaultValue;
        };

        document.querySelectorAll('[data-subscription-mode]').forEach((trigger) => {
            trigger.addEventListener('click', () => setSubscriptionFormMode(trigger));
        });

        subscriptionForm.addEventListener('submit', (event) => {
            const validationResults = requiredFields.map((field) => validateField(field));
            const isValid = validationResults.every(Boolean);

            if (!isValid) {
                event.preventDefault();
                requiredFields.find((field) => field.classList.contains('is-invalid'))?.focus();
            }
        });
    }
})();
