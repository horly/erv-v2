(() => {
            const shell = document.querySelector('.auth-shell');
            const root = document.documentElement;
            const themeButton = document.getElementById('themeButton');
            const languageMenu = document.querySelector('.language-menu');
            const languageButton = document.getElementById('languageButton');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const form = document.querySelector('.needs-validation');
            const storageKey = 'exad-theme';
            const submitLoadingLabel = document.documentElement.lang?.toLowerCase().startsWith('en')
                ? 'Processing...'
                : 'Traitement...';

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

            applyTheme(localStorage.getItem(storageKey) || root.dataset.theme || 'light');

            passwordToggle.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                passwordToggle.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                passwordToggle.innerHTML = isPassword
                    ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
                    : '<i class="bi bi-eye" aria-hidden="true"></i>';
            });

            themeButton.addEventListener('click', () => {
                const darkMode = root.dataset.theme !== 'dark';
                const nextTheme = darkMode ? 'dark' : 'light';
                localStorage.setItem(storageKey, nextTheme);
                applyTheme(nextTheme);
            });

            languageButton?.addEventListener('click', () => {
                const isOpen = languageMenu.classList.toggle('open');
                languageButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.addEventListener('click', (event) => {
                if (!languageMenu?.contains(event.target)) {
                    languageMenu?.classList.remove('open');
                    languageButton?.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    languageMenu?.classList.remove('open');
                    languageButton?.setAttribute('aria-expanded', 'false');
                }
            });

            const setFormSubmitting = (submitter = form.querySelector('button[type="submit"], input[type="submit"]')) => {
                if (form.dataset.submitting === 'true') {
                    return;
                }

                form.dataset.submitting = 'true';
                form.classList.add('is-submitting');

                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
                    control.dataset.originalDisabled = control.disabled ? 'true' : 'false';
                    control.disabled = true;
                    control.setAttribute('aria-disabled', 'true');
                });

                if (submitter?.tagName === 'BUTTON') {
                    submitter.dataset.submitLoading = 'true';
                    submitter.dataset.originalHtml = submitter.innerHTML;
                    submitter.setAttribute('aria-busy', 'true');
                    submitter.innerHTML = `<span class="submit-spinner" aria-hidden="true"></span><span>${submitLoadingLabel}</span>`;
                }
            };

            const resetFormSubmitting = () => {
                delete form.dataset.submitting;
                form.classList.remove('is-submitting');

                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
                    if (control.dataset.originalDisabled === 'false') {
                        control.disabled = false;
                    }

                    control.removeAttribute('aria-disabled');
                    control.removeAttribute('aria-busy');

                    if (control.dataset.originalHtml) {
                        control.innerHTML = control.dataset.originalHtml;
                    }

                    delete control.dataset.submitLoading;
                    delete control.dataset.originalDisabled;
                    delete control.dataset.originalHtml;
                });
            };

            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    resetFormSubmitting();
                } else if (form.dataset.submitting === 'true') {
                    event.preventDefault();
                } else {
                    setFormSubmitting(event.submitter);
                }

                form.classList.add('was-validated');
            });

            window.addEventListener('pageshow', resetFormSubmitting);
        })();
