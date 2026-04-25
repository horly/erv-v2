<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion | {{ config('app.name', 'EXAD ERP') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --font-app: "JetBrains Mono", monospace;
            --blue-950: #071a3f;
            --blue-900: #0b2554;
            --blue-800: #123a78;
            --blue-600: #2563eb;
            --blue-500: #3b82f6;
            --cyan-300: #67d4ff;
            --ink: #061126;
            --muted: #51617a;
            --line: #d8e2f0;
            --field: #ffffff;
            --surface: #ffffff;
            --page: #f8fafc;
            --shadow: 0 20px 40px rgba(37, 99, 235, .17);
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --ink: #f6f9ff;
            --muted: #aab8cf;
            --line: #273a59;
            --field: #0f1b2d;
            --surface: #0b1526;
            --page: #07111f;
            --shadow: 0 20px 44px rgba(0, 0, 0, .32);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: var(--font-app);
            font-size: .92rem;
            font-weight: 500;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a,
        button,
        input {
            transition: color .2s ease, border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .auth-shell {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
            min-height: 100vh;
            background: var(--page);
        }

        .brand-side {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
            padding: clamp(2rem, 4vw, 3.2rem);
            color: #fff;
            background:
                linear-gradient(rgba(255, 255, 255, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .045) 1px, transparent 1px),
                radial-gradient(circle at 82% 72%, rgba(37, 99, 235, .36), transparent 34%),
                linear-gradient(145deg, var(--blue-950) 0%, var(--blue-900) 52%, #0f3671 100%);
            background-size: 56px 56px, 56px 56px, auto, auto;
        }

        .brand-side::after {
            position: absolute;
            right: -18%;
            bottom: -20%;
            width: 560px;
            height: 560px;
            content: "";
            background: radial-gradient(circle, rgba(80, 176, 255, .16), transparent 66%);
            pointer-events: none;
        }

        .brand-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: calc(100vh - clamp(4rem, 8vw, 6.4rem));
            max-width: 680px;
        }

        .logo-card {
            display: grid;
            width: 120px;
            height: 120px;
            place-items: center;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 22px 50px rgba(0, 0, 0, .18);
        }

        .logo-word {
            color: #151a52;
            font-size: 2.05rem;
            font-weight: 900;
            letter-spacing: -.05em;
            line-height: 1;
        }

        .logo-sub {
            margin-top: .2rem;
            color: #172554;
            font-size: .48rem;
            font-weight: 800;
            letter-spacing: -.02em;
            text-align: center;
        }

        .brand-copy {
            padding: clamp(3.5rem, 10vh, 6.2rem) 0 clamp(2.2rem, 6vh, 4rem);
        }

        .brand-title {
            max-width: 570px;
            margin: 0 0 1.3rem;
            font-size: clamp(2.35rem, 5vw, 4.1rem);
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.08;
        }

        .brand-title span {
            display: block;
            color: var(--cyan-300);
        }

        .brand-lead {
            max-width: 560px;
            margin: 0;
            color: #dbeafe;
            font-size: clamp(1rem, 1.6vw, 1.16rem);
            line-height: 1.65;
        }

        .feature-list {
            display: grid;
            gap: 1.1rem;
            max-width: 640px;
        }

        .feature-item {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr);
            gap: .95rem;
            align-items: center;
        }

        .feature-icon {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border: 1px solid rgba(147, 197, 253, .24);
            border-radius: 10px;
            background: rgba(147, 197, 253, .12);
            color: #9fd4ff;
            font-size: 1.16rem;
        }

        .feature-title {
            margin: 0 0 .25rem;
            color: #fff;
            font-weight: 800;
        }

        .feature-text {
            margin: 0;
            color: #bfd6f5;
            font-size: .93rem;
            line-height: 1.45;
        }

        .form-side {
            position: relative;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 3.5rem);
            background: var(--surface);
        }

        .top-tools {
            position: absolute;
            top: clamp(1rem, 2.8vw, 1.7rem);
            right: clamp(1rem, 2.8vw, 2.8rem);
            display: flex;
            gap: .7rem;
            align-items: center;
        }

        .icon-button,
        .language-button,
        .password-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface);
            color: var(--ink);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
        }

        .icon-button:hover,
        .language-button:hover,
        .password-toggle:hover {
            border-color: rgba(37, 99, 235, .45);
            color: var(--blue-600);
            transform: translateY(-1px);
        }

        .language-button {
            gap: .45rem;
            padding: 0 .85rem;
            font-weight: 700;
        }

        .login-wrap {
            width: min(100%, 460px);
            margin: auto;
            padding-top: 3rem;
        }

        .access-badge {
            display: inline-flex;
            gap: .38rem;
            align-items: center;
            margin-bottom: 1.35rem;
            padding: .45rem .72rem;
            border-radius: 5px;
            background: #eef5ff;
            color: #0b55ff;
            font-size: .68rem;
            font-weight: 850;
            letter-spacing: .32em;
            text-transform: uppercase;
        }

        [data-theme="dark"] .access-badge {
            background: rgba(37, 99, 235, .16);
            color: #8ec5ff;
        }

        .login-title {
            margin: 0 0 .85rem;
            color: var(--ink);
            font-size: clamp(1.8rem, 4vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.15;
        }

        .login-description {
            max-width: 430px;
            margin: 0 0 2.3rem;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.65;
        }

        .form-label {
            margin-bottom: .55rem;
            color: var(--ink);
            font-size: .92rem;
            font-weight: 750;
        }

        .field-row {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 54px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--field);
            overflow: hidden;
        }

        .field-row:focus-within {
            border-color: rgba(37, 99, 235, .72);
            box-shadow: 0 0 0 .22rem rgba(37, 99, 235, .12);
        }

        .field-icon {
            display: grid;
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            place-items: center;
            color: #6b7a90;
        }

        .form-control {
            min-width: 0;
            min-height: 54px;
            padding: .75rem 1rem .75rem 0;
            border: 0;
            background: transparent;
            color: var(--ink);
            box-shadow: none;
            font-size: 1rem;
        }

        .form-control::placeholder {
            color: #99a7bc;
        }

        .form-control:focus {
            background: transparent;
            color: var(--ink);
            box-shadow: none;
        }

        .password-toggle {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            box-shadow: none;
        }

        .field-meta {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
        }

        .forgot-link,
        .admin-link {
            color: #0b55ff;
            font-weight: 750;
            text-decoration: none;
        }

        .forgot-link:hover,
        .admin-link:hover {
            color: #003cc7;
            text-decoration: underline;
        }

        .form-check-input {
            width: 1.05rem;
            height: 1.05rem;
            border-color: #b8c5d8;
            cursor: pointer;
        }

        .form-check-input:checked {
            border-color: var(--blue-600);
            background-color: var(--blue-600);
        }

        .form-check-label {
            color: var(--muted);
            font-size: .94rem;
            cursor: pointer;
        }

        .invalid-feedback {
            color: #dc2626;
            font-size: .84rem;
            font-weight: 650;
        }

        .was-validated .mb-3:has(.form-control:invalid) > .invalid-feedback {
            display: block;
        }

        .primary-button {
            display: inline-flex;
            width: 100%;
            min-height: 56px;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border: 0;
            border-radius: 9px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: var(--shadow);
            color: #fff;
            font-weight: 850;
        }

        .primary-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            transform: translateY(-1px);
        }

        .login-note {
            margin: 1.35rem 0 0;
            color: var(--muted);
            font-size: .92rem;
            text-align: center;
        }

        .page-footer {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem clamp(1.5rem, 4vw, 3rem);
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: .84rem;
        }

        .page-footer a {
            color: var(--muted);
            text-decoration: none;
        }

        .page-footer a:hover {
            color: var(--blue-600);
        }

        @media (max-width: 991.98px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .brand-side,
            .form-side {
                min-height: auto;
            }

            .brand-inner {
                min-height: auto;
            }

            .brand-copy {
                padding: 3.5rem 0 2.3rem;
            }

            .form-side {
                padding-top: 6rem;
                padding-bottom: 5rem;
            }

            .page-footer {
                position: static;
                margin-top: 3rem;
            }
        }

        @media (max-width: 575.98px) {
            .brand-side {
                padding: 1.3rem;
            }

            .logo-card {
                width: 92px;
                height: 92px;
            }

            .logo-word {
                font-size: 1.55rem;
            }

            .brand-copy {
                padding: 2.7rem 0 2rem;
            }

            .feature-item {
                grid-template-columns: 40px minmax(0, 1fr);
            }

            .feature-icon {
                width: 40px;
                height: 40px;
            }

            .form-side {
                padding: 5.2rem 1.15rem 2rem;
            }

            .top-tools {
                right: 1rem;
            }

            .login-wrap {
                padding-top: 1rem;
            }

            .field-meta {
                align-items: flex-start;
                flex-direction: column;
                gap: .4rem;
            }

            .page-footer {
                align-items: flex-start;
                flex-direction: column;
                padding-right: 1.15rem;
                padding-left: 1.15rem;
            }
        }
    </style>
</head>
<body>
    <main class="auth-shell" data-theme="light">
        <section class="brand-side" aria-label="Présentation de la plateforme ERP">
            <div class="brand-inner">
                <div class="logo-card" aria-label="EXAD Solution & Services">
                    <div>
                        <div class="logo-word">EXAD</div>
                        <div class="logo-sub">Solution & Services</div>
                    </div>
                </div>

                <div>
                    <div class="brand-copy">
                        <h1 class="brand-title">
                            Pilotez votre entreprise
                            <span>avec précision.</span>
                        </h1>
                        <p class="brand-lead">
                            Une plateforme ERP unifiée pour la finance, les ressources humaines,
                            les opérations et la relation client.
                        </p>
                    </div>

                    <div class="feature-list" aria-label="Bénéfices ERP">
                        <article class="feature-item">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-bar-chart-fill"></i></span>
                            <div>
                                <p class="feature-title">Décisions data-driven</p>
                                <p class="feature-text">Tableaux de bord temps réel et reporting consolidé.</p>
                            </div>
                        </article>
                        <article class="feature-item">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></span>
                            <div>
                                <p class="feature-title">Sécurité de niveau entreprise</p>
                                <p class="feature-text">Conformité, rôles utilisateurs et chiffrement de bout en bout.</p>
                            </div>
                        </article>
                        <article class="feature-item">
                            <span class="feature-icon" aria-hidden="true"><i class="bi bi-diagram-3-fill"></i></span>
                            <div>
                                <p class="feature-title">Modules intégrés</p>
                                <p class="feature-text">Comptabilité, stocks, ventes, RH — un seul environnement.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-side" aria-label="Formulaire de connexion">
            <div class="top-tools">
                <button class="icon-button" type="button" id="themeButton" aria-label="Activer le mode sombre" title="Mode sombre">
                    <i class="bi bi-brightness-high-fill" aria-hidden="true"></i>
                </button>
                <button class="language-button" type="button" id="languageButton" aria-label="Changer la langue" title="Changer la langue">
                    <i class="bi bi-globe2" aria-hidden="true"></i>
                    <span>FR</span>
                    <i class="bi bi-chevron-down small" aria-hidden="true"></i>
                </button>
            </div>

            <div class="login-wrap">
                <span class="access-badge">Espace collaborateur</span>
                <h2 class="login-title">Connexion à votre compte</h2>
                <p class="login-description">
                    Entrez vos identifiants professionnels pour accéder à la plateforme EXAD ERP.
                </p>

                <form method="POST" action="#" class="needs-validation" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse e-mail professionnelle</label>
                        <div class="field-row">
                            <span class="field-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                class="form-control"
                                placeholder="prenom.nom@entreprise.com"
                                autocomplete="email"
                                required
                            >
                        </div>
                        <div class="invalid-feedback">Veuillez saisir une adresse e-mail professionnelle valide.</div>
                    </div>

                    <div class="mb-3">
                        <div class="field-meta mb-2">
                            <label for="password" class="form-label mb-0">Mot de passe</label>
                            <a href="#" class="forgot-link small">Mot de passe oublié ?</a>
                        </div>
                        <div class="field-row">
                            <span class="field-icon"><i class="bi bi-lock" aria-hidden="true"></i></span>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                minlength="6"
                                required
                            >
                            <button class="password-toggle" type="button" id="passwordToggle" aria-label="Afficher le mot de passe">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Le mot de passe est requis.</div>
                    </div>

                    <div class="form-check d-flex align-items-center gap-2 mb-4">
                        <input class="form-check-input mt-0" type="checkbox" value="1" id="remember" name="remember" checked>
                        <label class="form-check-label" for="remember">Se souvenir de moi sur cet appareil</label>
                    </div>

                    <button class="primary-button" type="submit">
                        Se connecter
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="login-note">
                    Pas encore de compte ?
                    <a href="#" class="admin-link">Contactez votre administrateur</a>
                </p>
            </div>

            <footer class="page-footer">
                <span>© 2026 EXAD ERP — Tous droits réservés.</span>
                <a href="#">Confidentialité</a>
            </footer>
        </section>
    </main>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        (() => {
            const shell = document.querySelector('.auth-shell');
            const themeButton = document.getElementById('themeButton');
            const languageButton = document.getElementById('languageButton');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const form = document.querySelector('.needs-validation');

            passwordToggle.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                passwordToggle.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                passwordToggle.innerHTML = isPassword
                    ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
                    : '<i class="bi bi-eye" aria-hidden="true"></i>';
            });

            themeButton.addEventListener('click', () => {
                const darkMode = shell.dataset.theme !== 'dark';
                shell.dataset.theme = darkMode ? 'dark' : 'light';
                themeButton.setAttribute('aria-label', darkMode ? 'Activer le mode clair' : 'Activer le mode sombre');
                themeButton.setAttribute('title', darkMode ? 'Mode clair' : 'Mode sombre');
                themeButton.innerHTML = darkMode
                    ? '<i class="bi bi-moon-stars-fill" aria-hidden="true"></i>'
                    : '<i class="bi bi-brightness-high-fill" aria-hidden="true"></i>';
            });

            languageButton.addEventListener('click', () => {
                const label = languageButton.querySelector('span');
                label.textContent = label.textContent.trim() === 'FR' ? 'EN' : 'FR';
            });

            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            });
        })();
    </script>
</body>
</html>
