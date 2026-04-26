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
    const adminForm = document.querySelector('.admin-form');
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

    const refreshVisibleRows = () => {
        if (!table) {
            return;
        }

        const query = searchInput?.value.trim().toLowerCase() || '';
        const rows = Array.from(table.querySelectorAll('tbody tr:not(.empty-row)'));
        const searchEmptyRow = table.querySelector('.search-empty-row');
        let visible = 0;

        rows.forEach((row) => {
            const match = row.textContent.toLowerCase().includes(query);
            row.hidden = !match;
            visible += match ? 1 : 0;
        });

        if (searchEmptyRow) {
            searchEmptyRow.hidden = query === '' || visible > 0;
        }

        if (visibleCount) {
            visibleCount.textContent = String(visible);
        }
    };

    searchInput?.addEventListener('input', refreshVisibleRows);

    table?.querySelectorAll('.table-sort').forEach((button) => {
        button.addEventListener('click', () => {
            const tbody = table.tBodies[0];
            const index = Number(button.dataset.sortIndex);
            const type = button.dataset.sortType || 'text';
            const direction = button.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
            const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));

            table.querySelectorAll('.table-sort').forEach((sortButton) => {
                sortButton.classList.remove('is-sorted-asc', 'is-sorted-desc');
                delete sortButton.dataset.sortDirection;
            });

            button.dataset.sortDirection = direction;
            button.classList.add(direction === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');

            rows.sort((leftRow, rightRow) => {
                const left = getSortValue(leftRow.cells[index], type);
                const right = getSortValue(rightRow.cells[index], type);

                if (left < right) {
                    return direction === 'asc' ? -1 : 1;
                }

                if (left > right) {
                    return direction === 'asc' ? 1 : -1;
                }

                return 0;
            });

            rows.forEach((row) => tbody.insertBefore(row, table.querySelector('.search-empty-row')));
            refreshVisibleRows();
        });
    });

    function getSortValue(cell, type) {
        const value = (cell?.textContent || '').trim().toLowerCase();

        if (type === 'number') {
            return Number(value.replace(/[^0-9.-]/g, '')) || 0;
        }

        if (type === 'date') {
            const match = value.match(/(\d{2})\/(\d{2})\/(\d{4})/);

            if (!match) {
                return 0;
            }

            return new Date(`${match[3]}-${match[2]}-${match[1]}`).getTime();
        }

        return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

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
    const setupSimpleValidation = (form) => {
        const requiredFields = Array.from(form.querySelectorAll('[data-required-message]:not([data-password-rules-target]):not([data-password-confirmation-for])'));

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

        form.addEventListener('submit', (event) => {
            const isValid = requiredFields.map((field) => validateField(field)).every(Boolean);

            if (!isValid) {
                event.preventDefault();
                requiredFields.find((field) => field.classList.contains('is-invalid'))?.focus();
            }
        });
    };
    const initPasswordValidation = (form) => {
        const passwordFields = Array.from(form.querySelectorAll('[data-password-rules-target]'));
        const confirmationFields = Array.from(form.querySelectorAll('[data-password-confirmation-for]'));

        const passwordCriteria = (value) => ({
            length: value.length >= 12,
            case: /[a-z]/.test(value) && /[A-Z]/.test(value),
            alphanumeric: /[A-Za-z]/.test(value) && /\d/.test(value),
            special: /[^A-Za-z0-9]/.test(value),
        });

        const setPasswordFieldState = (field, valid) => {
            field.classList.toggle('is-valid', valid);
            field.classList.toggle('is-invalid', field.value.length > 0 && !valid);
        };

        const validatePassword = (field) => {
            const criteria = passwordCriteria(field.value);
            const target = document.getElementById(field.dataset.passwordRulesTarget);
            const valid = Object.values(criteria).every(Boolean);

            target?.querySelectorAll('[data-rule]').forEach((rule) => {
                const isValid = criteria[rule.dataset.rule] === true;
                rule.classList.toggle('is-valid', isValid);
                rule.classList.toggle('is-invalid', field.value.length > 0 && !isValid);
            });

            setPasswordFieldState(field, valid);
            return valid;
        };

        const validateConfirmation = (field) => {
            const password = document.getElementById(field.dataset.passwordConfirmationFor);
            const target = document.getElementById(field.dataset.passwordMatchTarget);
            const hasValue = field.value.length > 0;
            const valid = hasValue && password && field.value === password.value;

            if (target) {
                target.textContent = hasValue
                    ? (valid ? target.dataset.validMessage : target.dataset.invalidMessage)
                    : '';
                target.classList.toggle('is-valid', valid);
                target.classList.toggle('is-invalid', hasValue && !valid);
            }

            setPasswordFieldState(field, valid);
            return valid;
        };

        passwordFields.forEach((field) => {
            field.addEventListener('input', () => {
                validatePassword(field);
                confirmationFields
                    .filter((confirmation) => confirmation.dataset.passwordConfirmationFor === field.id)
                    .forEach(validateConfirmation);
            });
            field.addEventListener('blur', () => validatePassword(field));
        });

        confirmationFields.forEach((field) => {
            field.addEventListener('input', () => validateConfirmation(field));
            field.addEventListener('blur', () => validateConfirmation(field));
        });

        form.addEventListener('submit', (event) => {
            const passwordsValid = passwordFields.map(validatePassword).every(Boolean);
            const confirmationsValid = confirmationFields.map(validateConfirmation).every(Boolean);

            if (!passwordsValid || !confirmationsValid) {
                event.preventDefault();
                form.querySelector('.is-invalid')?.focus();
            }
        });
    };
    if (adminForm) {
        setupSimpleValidation(adminForm);
        initPasswordValidation(adminForm);
    }

    document.querySelectorAll('[data-subscription-detail]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modal = document.getElementById('subscriptionDetailsModal');

            if (!modal) {
                return;
            }

            const setText = (selector, value) => {
                const element = modal.querySelector(selector);

                if (element) {
                    element.textContent = value || '-';
                }
            };

            const typePill = modal.querySelector('[data-detail-type]');
            const statusPill = modal.querySelector('[data-detail-status]');
            const expirationWrap = modal.querySelector('[data-detail-expiration-wrap]');
            const expirationIcon = expirationWrap?.querySelector('i');

            setText('[data-detail-name]', trigger.dataset.name);
            setText('[data-detail-type]', trigger.dataset.type);
            setText('[data-detail-limit]', trigger.dataset.limit);
            setText('[data-detail-expiration]', trigger.dataset.expiration);
            setText('[data-detail-created]', trigger.dataset.created);
            setText('[data-detail-users]', trigger.dataset.users);
            setText('[data-detail-companies]', trigger.dataset.companies);

            if (typePill) {
                typePill.className = `status-pill ${trigger.dataset.typeClass || ''}`.trim();
            }

            if (statusPill) {
                statusPill.className = `status-pill ${trigger.dataset.statusClass || ''}`.trim();
                statusPill.innerHTML = `<i class="bi bi-circle-fill" aria-hidden="true"></i> ${escapeHtml(trigger.dataset.status || '-')}`;
            }

            if (expirationIcon) {
                expirationIcon.className = `bi ${trigger.dataset.expirationIcon || 'bi-calendar3'}`;
            }

            expirationWrap?.classList.toggle('text-danger', trigger.dataset.statusClass === 'is-expired');
            expirationWrap?.classList.toggle('fw-bold', trigger.dataset.statusClass === 'is-expired');
        });
    });
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const field = document.getElementById(button.dataset.passwordToggle);

            if (!field) {
                return;
            }

            const showPassword = field.type === 'password';
            field.type = showPassword ? 'text' : 'password';
            button.innerHTML = showPassword
                ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
                : '<i class="bi bi-eye" aria-hidden="true"></i>';
        });
    });
    document.querySelectorAll('[data-delete-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', async () => {
            const form = trigger.closest('form');

            if (!form) {
                return;
            }

            const confirmed = await confirmDeleteAction({
                title: trigger.dataset.deleteTitle,
                text: trigger.dataset.deleteText,
                confirmButtonText: trigger.dataset.deleteConfirm,
                cancelButtonText: trigger.dataset.deleteCancel,
            });

            if (confirmed) {
                form.submit();
            }
        });
    });

    function confirmDeleteAction(options) {
        if (window.Swal?.fire) {
            return window.Swal.fire({
                title: options.title,
                text: options.text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: options.confirmButtonText,
                cancelButtonText: options.cancelButtonText,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true,
            }).then((result) => result.isConfirmed);
        }

        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'swal-fallback-overlay';
            overlay.innerHTML = `
                <div class="swal-fallback-dialog" role="alertdialog" aria-modal="true">
                    <span class="swal-fallback-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                    <h2>${escapeHtml(options.title)}</h2>
                    <p>${escapeHtml(options.text)}</p>
                    <div class="swal-fallback-actions">
                        <button type="button" class="swal-fallback-cancel">${escapeHtml(options.cancelButtonText)}</button>
                        <button type="button" class="swal-fallback-confirm">${escapeHtml(options.confirmButtonText)}</button>
                    </div>
                </div>
            `;

            const close = (value) => {
                overlay.classList.add('is-hiding');
                window.setTimeout(() => overlay.remove(), 180);
                resolve(value);
            };

            overlay.querySelector('.swal-fallback-cancel')?.addEventListener('click', () => close(false));
            overlay.querySelector('.swal-fallback-confirm')?.addEventListener('click', () => close(true));
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    close(false);
                }
            });

            document.body.appendChild(overlay);
            overlay.querySelector('.swal-fallback-cancel')?.focus();
        });
    }

    function escapeHtml(value = '') {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
})();