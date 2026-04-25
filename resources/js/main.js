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
})();
