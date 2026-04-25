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

            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            });
        })();

