# Historique complet du projet - EXAD ERP

Ce document conserve le contexte de developpement du projet EXAD ERP afin qu'il soit versionne avec GitHub. Apres un `git clone` sur une autre machine, il doit permettre de comprendre les prompts utilises, les decisions prises, les problemes rencontres, les corrections appliquees et les prochaines taches.

Important : ne jamais ajouter ici de secrets issus du fichier `.env` comme `APP_KEY`, mots de passe, tokens, identifiants SSH ou informations de production. Les comptes de demonstration peuvent etre mentionnes, mais leurs mots de passe ne doivent pas etre recopies dans cette documentation.

## Objectif general

EXAD ERP est une application Laravel 12 destinee a devenir une base ERP/SaaS avec authentification, gestion des roles, abonnements, entreprises et modules metier.

Le projet a evolue d'un squelette Laravel vers une base comprenant :

- Authentification avec Laravel Fortify.
- Roles `superadmin`, `admin` et `user`.
- Redirection apres login selon le role.
- Console superadmin.
- Gestion des abonnements.
- Gestion des utilisateurs rattaches a des abonnements.
- Gestion des entreprises rattachees a des abonnements et administrateurs.
- Gestion de champs entreprise : RCCM, ID NAT, NIF, site web, slogan, pays, logo, email, adresse.
- Gestion de telephones et comptes bancaires d'entreprise.
- Interface multilingue FR/EN.
- Middleware de langue et middleware d'acces superadmin.
- Tests fonctionnels pour les parcours principaux.
- Dossier `docs/prompts` pour garder l'historique du projet.

## Repertoire et depot actuels

Chemin actuel du projet :

```text
D:\CODEX\exad-erp
```

Branche Git actuelle :

```text
main
```

Remote Git actuel :

```text
origin git@github.com:horly/erv-v2.git
```

Historique Git observe :

```text
9808322 Ajout entreprise administrateur
c9bd322 Details de l'abonnement dans la page utilisateurs cote superadmin
bbcf18e Modification de l'abonnement effectue, je dois maintenant entamer la partie suppression de l'abonnement
6840fbd First commit
```

Etat Git apres creation de cette documentation :

```text
?? docs/
```

## Journal chronologique detaille

### 2026-04-25 - Creation initiale du projet Laravel EXAD ERP

Prompt utilisateur :

```text
peux-tu me creer un projet Laravel EXAD ERP
```

Actions realisees :

- Verification de l'ancien dossier de travail : `C:\Users\ANDELO\Desktop\CODEX`.
- Le dossier etait vide, donc creation possible d'un projet propre.
- Verification de PHP avec `php -v`.
- Verification de Composer avec `composer --version`.
- Creation du projet Laravel via Composer.

Commande utilisee :

```powershell
composer create-project laravel/laravel exad-erp
```

Constats techniques :

- PHP detecte : PHP 8.2.12 via XAMPP.
- PHP utilise : `C:\xampp\php\php.exe`.
- Composer detecte : Composer 2.8.10.
- Avertissement recurrent : l'extension PHP `imagick` est referencee mais absente.

Decision :

- L'avertissement `imagick` n'etait pas bloquant pour Laravel.
- Continuer l'installation.

### 2026-04-25 - Probleme Composer et correction du cache Laravel

Probleme rencontre :

- La commande Composer a depasse le delai de l'outil, mais le projet a ete cree.
- `composer install` a installe les dependances, puis `artisan package:discover` a echoue avec une erreur Windows d'acces refuse sur `bootstrap/cache`.

Erreur observee :

```text
rename(...bootstrap\cache\pac684B.tmp, ...bootstrap\cache\packages.php): Acces refuse (code: 5)
```

Analyse :

- Le probleme concernait des fichiers generes de cache Laravel.
- Laravel etait deja installe.
- `php artisan --version` retournait `Laravel Framework 12.57.0`.

Correction appliquee :

- Suppression des caches generes problematiques :
  - `bootstrap/cache/packages.php`
  - `bootstrap/cache/services.php`
  - `bootstrap/cache/pac684B.tmp`
- Relance de la decouverte des packages :

```powershell
php artisan package:discover --ansi
```

Resultat :

- Decouverte des packages reussie.
- Projet Laravel operationnel.

### 2026-04-25 - Premiere personnalisation EXAD ERP

Fichiers inspectes :

- `routes/web.php`
- `resources/views/welcome.blade.php`
- `.env`
- `package.json`

Modifications realisees :

- Mise a jour du nom d'application dans `.env` : `APP_NAME="EXAD ERP"`.
- Remplacement de la page Laravel par defaut par un tableau de bord ERP.

Interface creee a ce moment-la :

- Tableau de bord EXAD ERP.
- Navigation : Tableau de bord, Ventes, Achats, Stock, Comptabilite, Ressources humaines.
- Indicateurs : chiffre d'affaires, commandes ouvertes, articles en stock, factures a traiter.
- Modules : Gestion commerciale, Stock et approvisionnement, Finance, Ressources humaines.
- Bloc de prochaines etapes : roles, modeles, base de donnees.

Decision :

- Demarrer simplement avec Blade et CSS, sans ajouter de dependance frontend supplementaire.

### 2026-04-25 - Verification Laravel et tests initiaux

Commandes lancees :

```powershell
php artisan route:list
php artisan test
```

Resultat initial :

- Les routes Laravel etaient detectees.
- Les tests Laravel par defaut passaient : `2 passed`.
- L'avertissement `imagick` restait visible mais non bloquant.

### 2026-04-25 - Serveur local et script de demarrage

Objectif :

- Lancer l'application localement.

Approches testees :

```powershell
php artisan serve --host=127.0.0.1 --port=8000
Start-Process php ...
Start-Process C:\xampp\php\php.exe ...
Start-Process powershell.exe ...
Start-Job ...
Start-Process cmd.exe ...
```

Constat :

- `php artisan serve` fonctionnait au premier plan.
- Les lancements caches/en arriere-plan depuis l'outil se fermaient rapidement.
- `Invoke-WebRequest http://127.0.0.1:8000` ne repondait pas pendant ces tentatives.

Fichier ajoute :

```text
serve-exad.bat
```

Role :

- Se placer dans le dossier du projet.
- Lancer Laravel avec `C:\xampp\php\php.exe`.
- Ecouter sur `127.0.0.1:8000`.
- Rediriger les logs vers `storage\logs\serve.log`.

Commande recommandee :

```powershell
cd D:\CODEX\exad-erp
.\serve-exad.bat
```

URL locale :

```text
http://127.0.0.1:8000
```

### 2026-04-25 - Base SQLite et migrations

Constat :

- Base locale presente : `database/database.sqlite`.

Commande lancee :

```powershell
php artisan migrate --force
```

Resultat :

```text
INFO Nothing to migrate.
```

Decision :

- Garder SQLite pour le developpement local.
- Prevoir MySQL plus tard si besoin pour production ou multi-utilisateurs.

### 2026-04-25 - Premier commit fonctionnel du projet

Commit observe :

```text
6840fbd First commit
```

Ce commit contient beaucoup plus qu'un squelette Laravel. Il inclut notamment :

- Installation Laravel.
- Laravel Fortify.
- Actions Fortify : creation utilisateur, reset password, update password, update profile.
- Controleurs :
  - `AuthController`
  - `LanguageController`
  - `MainController`
- Middleware :
  - `SetLocale`
- Requete :
  - `StoreUserRequest`
- Regle :
  - `StrongPassword`
- Modeles :
  - `User`
  - `Company`
  - `Subscription`
- Policy :
  - `CompanyPolicy`
- Providers :
  - `AppServiceProvider`
  - `FortifyServiceProvider`
- Configuration Fortify.
- Migrations Laravel de base.
- Migration ERP `2026_04_25_000001_create_erp_user_access_tables.php`.
- Migration Fortify pour colonnes 2FA.
- Langues FR/EN pour auth et interface principale.
- Assets Bootstrap et Bootstrap Icons dans `public/vendor`.
- Logo EXAD dans `public/img/logo/exad-1200x1200.jpg`.
- Vues : login, main, dashboard admin initial, welcome.
- CSS : app, login, main.
- JS : app, bootstrap, login, main.
- Tests initiaux.

### 2026-04-25 - Mise en place de l'authentification Fortify

Elements en place :

- `laravel/fortify` ajoute dans `composer.json`.
- `config/fortify.php` present.
- `FortifyServiceProvider` configure.
- Vue de login personnalisee via `AuthController@login`.
- Rate limiting sur login et two-factor.
- Reponse de login personnalisee via `App\Http\Responses\LoginResponse`.

Decision fonctionnelle :

- Apres connexion, l'utilisateur est redirige selon son role :
  - `superadmin` vers `admin.dashboard`.
  - autres utilisateurs vers `main`.

### 2026-04-25 - Roles et permissions utilisateurs

Modele principal : `app/Models/User.php`.

Roles definis :

- `superadmin`
- `admin`
- `user`

Methodes ajoutees :

- `isSuperadmin()`
- `isAdmin()`
- `isUser()`
- `canManageCompany()`
- `redirectRouteAfterLogin()`

Champs utilisateurs ajoutes :

- `subscription_id`
- `role`
- `address`
- `phone_number`
- `grade`

Relation ajoutee :

- Un utilisateur appartient a un abonnement.
- Un utilisateur peut etre lie a plusieurs entreprises via `company_user` avec permissions.

Permissions pivot entreprise :

- `can_view`
- `can_create`
- `can_update`
- `can_delete`

### 2026-04-25 - Abonnements et acces ERP

Migration ERP principale :

```text
database/migrations/2026_04_25_000001_create_erp_user_access_tables.php
```

Tables creees ou modifiees :

- `subscriptions`
- Ajout de champs a `users`
- `companies`
- `company_user`

Modele `Subscription` :

- Champs : `name`, `code`, `type`, `status`, `company_limit`, `expires_at`.
- Relations : `users`, `companies`.
- Methode `isCurrentlyActive()`.
- Methode `statusForExpiration()`.

### 2026-04-25 - Internationalisation FR/EN

Fichiers concernes :

- `config/app.php`
- `app/Http/Middleware/SetLocale.php`
- `app/Http/Controllers/LanguageController.php`
- `lang/fr/auth.php`
- `lang/en/auth.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- plus tard `lang/fr/admin.php` et `lang/en/admin.php`.

Decisions :

- Locale par defaut : francais.
- Locale fallback : anglais.
- Locales supportees : `fr`, `en`.
- Route `/lang/{locale}` pour changer de langue via session.
- Middleware `SetLocale` ajoute au groupe web.

### 2026-04-25 - Middleware superadmin et gestion d'erreurs

Fichier :

```text
app/Http/Middleware/EnsureUserIsSuperadmin.php
```

Role :

- Bloquer l'acces aux routes admin si l'utilisateur n'est pas superadmin.
- Rediriger vers la page precedente ou vers `main`.
- Ajouter une erreur de session `authorization`.

Configuration :

- Alias middleware `superadmin` dans `bootstrap/app.php`.
- Middleware `SetLocale` ajoute au web stack.
- Gestion du `TokenMismatchException` pour rediriger vers login avec message de session expiree.

### 2026-04-25 - Gestion des abonnements cote superadmin

Commit observe :

```text
bbcf18e Modification de l'abonnement effectue, je dois maintenant entamer la partie suppression de l'abonnement
```

Fichiers touches dans ce commit :

- `MainController`
- `EnsureUserIsSuperadmin`
- `LoginResponse`
- `Subscription`
- `FortifyServiceProvider`
- `bootstrap/app.php`
- Migration `2026_04_25_235959_add_plan_fields_to_subscriptions_table.php`
- `DatabaseSeeder`
- Traductions admin/auth/main FR/EN
- CSS admin/main
- JS main
- Vues admin dashboard/subscriptions
- Routes
- Tests

Fonctionnalites ajoutees ou renforcees :

- Page superadmin des abonnements.
- Creation d'abonnements.
- Modification d'abonnements.
- Types d'abonnement : `standard`, `pro`, `business`.
- Limites d'entreprises selon le type : standard = 1, pro = 3, business = illimite.
- Date d'expiration.
- Statut actif/expire calcule selon expiration.
- Seed de donnees de demonstration avec abonnement, admin, user et superadmin.

Note securite :

- Le seeder contient des comptes de demonstration avec mots de passe. Ne pas recopier ces mots de passe dans la documentation et ne pas les utiliser en production.

### 2026-04-26 - Details d'abonnement dans la page utilisateurs

Commit observe :

```text
c9bd322 Details de l'abonnement dans la page utilisateurs cote superadmin
```

Fichiers touches :

- `AdminController`
- `MainController`
- `Subscription`
- Traductions admin FR/EN
- `resources/css/admin/dashboard.css`
- `resources/js/main.js`
- Vues : admin dashboard, subscriptions, users, main
- Routes
- Tests

Fonctionnalites :

- Page utilisateurs cote superadmin.
- Affichage des details d'abonnement sur la page utilisateurs.
- Chargement des abonnements avec compteurs `users_count` et `companies_count`.
- Tri des utilisateurs avec superadmin en premier puis les plus recents.
- Options d'abonnement disponibles pour formulaires.
- Tests pour verifier les compteurs et l'affichage des utilisateurs.

### 2026-04-26 - Ajout de la gestion entreprise administrateur

Commit observe :

```text
9808322 Ajout entreprise administrateur
```

Fichiers touches :

- `AdminController`
- `Company`
- `CompanyAccount`
- `CompanyPhone`
- `config/countries.php`
- Migration `2026_04_26_190617_update_companies_business_fields.php`
- Traductions admin FR/EN
- `resources/css/admin/dashboard.css`
- `resources/js/main.js`
- Vues admin : companies, companies-create, dashboard, subscriptions, users
- Routes
- Tests

Fonctionnalites ajoutees :

- Liste des entreprises dans l'admin.
- Formulaire de creation d'entreprise.
- Liaison obligatoire a un abonnement.
- Liaison obligatoire a un administrateur.
- Verification que l'administrateur appartient au meme abonnement.
- Controle de limite d'entreprises selon abonnement.
- Upload de logo entreprise vers `storage/app/public/company-logos`.
- Champs entreprise :
  - nom
  - pays
  - slogan
  - RCCM
  - ID NAT
  - NIF
  - email
  - site web
  - adresse
  - logo
- Ajout de telephones multiples via `company_phones`.
- Ajout de comptes bancaires multiples via `company_accounts`.
- Association de l'admin a l'entreprise dans `company_user` avec toutes les permissions.

Migration entreprise :

```text
database/migrations/2026_04_26_190617_update_companies_business_fields.php
```

Changements migration :

- Ajout des champs business sur `companies`.
- Creation de `company_phones`.
- Creation de `company_accounts`.
- Correction des emails entreprise vides avec une valeur locale avant de rendre `email` obligatoire.

### 2026-04-26 - Routes applicatives actuelles

Routes principales observees :

- `GET /` -> redirection vers login via `MainController@root`.
- `GET /main` -> page principale protegee par auth.
- `GET /admin/dashboard` -> dashboard superadmin.
- `GET /admin/subscriptions` -> liste abonnements.
- `POST /admin/subscriptions` -> creation abonnement.
- `PUT /admin/subscriptions/{subscription}` -> modification abonnement.
- `DELETE /admin/subscriptions/{subscription}` -> suppression abonnement.
- `GET /admin/users` -> liste utilisateurs.
- `POST /admin/users` -> creation utilisateur.
- `PUT /admin/users/{account}` -> modification utilisateur.
- `DELETE /admin/users/{account}` -> suppression utilisateur.
- `POST /admin/admins` -> creation admin.
- `GET /admin/companies` -> liste entreprises.
- `GET /admin/companies/create` -> formulaire creation entreprise.
- `POST /admin/companies` -> creation entreprise.
- `GET /lang/{locale}` -> changement de langue.

Middleware routes admin :

```text
auth + superadmin
```

### 2026-04-26 - Page principale utilisateur/admin

Controleur : `MainController`.

Comportement :

- `/` redirige vers login.
- `/main` exige une session authentifiee.
- Un superadmin qui arrive sur `/main` est redirige vers `admin.dashboard`.
- Un admin voit les entreprises de son abonnement.
- Un user voit les entreprises rattachees via la relation pivot `company_user`.

Vue principale :

```text
resources/views/main/main.blade.php
```

### 2026-04-26 - Tests fonctionnels presents

Fichier :

```text
tests/Feature/ExampleTest.php
```

Couverture observee :

- `/` redirige vers `/login`.
- Un admin peut ouvrir `/main` et voir son entreprise.
- Un superadmin peut ouvrir `/admin/dashboard`.
- Un admin non superadmin est redirige depuis `/admin/dashboard`.
- Un superadmin peut ouvrir la page abonnements.
- Un admin non superadmin est redirige depuis la page abonnements.
- Un superadmin peut creer un abonnement business.
- Un abonnement expire est affiche comme expire.
- Un superadmin peut ouvrir la page utilisateurs.
- La page utilisateurs expose les compteurs utilisateurs/entreprises de l'abonnement.
- Un superadmin peut creer un utilisateur depuis la page utilisateurs.
- La creation admin rejette un email invalide et un mot de passe absent.
- La page utilisateurs affiche superadmin puis les utilisateurs les plus recents.
- Un superadmin peut modifier un utilisateur sans changer son mot de passe.
- Un superadmin peut supprimer un utilisateur.
- Le login superadmin redirige vers le dashboard admin.

### 2026-04-27 - Deplacement du projet et correction du repertoire

Contexte utilisateur :

- Le projet avait ete cree initialement dans `Desktop\CODEX`.
- Le dossier a ensuite ete deplace vers un disque plus securise.

Nouveau repertoire confirme :

```text
D:\CODEX\exad-erp
```

Erreur de ma part :

- En cherchant le projet, j'ai d'abord trouve `C:\Users\ANDELO\CascadeProjects\laravel-auth`.
- J'y ai cree par erreur un premier fichier `docs/prompts/project-history.md`.
- L'utilisateur a corrige : ce n'etait pas le bon repertoire.
- Le fichier ajoute par erreur dans `laravel-auth` a ete supprime.

Correction :

- Bascule definitive vers `D:\CODEX\exad-erp`.
- Verification du depot Git, de la branche et du remote.
- Creation du dossier d'historique dans le bon projet.

### 2026-04-27 - Demande d'un dossier d'historique des prompts

Prompt utilisateur :

```text
Cree dans mon projet un dossier appele docs/prompts ou project-history/prompts afin d'y sauvegarder l'historique des prompts utilises pendant le developpement.

Dans ce dossier, ajoute un fichier Markdown pour documenter :

les prompts importants utilises ;
les reponses ou decisions principales ;
les modifications apportees au projet ;
les prochaines taches a continuer.

Le but est que ce dossier soit versionne avec GitHub, afin que je puisse cloner le projet sur une autre machine et reprendre le travail avec tout le contexte necessaire.
```

Decision :

- Utiliser `docs/prompts`.
- Creer `docs/prompts/project-history.md`.
- Garder le fichier en Markdown pour lecture facile sur GitHub.

### 2026-04-27 - Demande d'historique vraiment complet

Prompt utilisateur :

```text
tu n'as pas tout mis en tout cas il y a beaucoup des choses qu'on avait fais que tu n'as pas mis
```

Correction apportee :

- Analyse du depot lui-meme au lieu de se limiter a la conversation recente.
- Lecture de l'historique Git.
- Lecture des routes, controleurs, modeles, migrations, middlewares, providers, tests et composer.json.
- Reecriture de ce fichier pour inclure les fonctionnalites deja presentes : Fortify, roles, superadmin, abonnements, entreprises, langues, tests et Git.

## Fichiers importants du projet

### Backend Laravel

- `routes/web.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/LanguageController.php`
- `app/Http/Controllers/MainController.php`
- `app/Http/Controllers/AdminController.php`
- `app/Http/Middleware/SetLocale.php`
- `app/Http/Middleware/EnsureUserIsSuperadmin.php`
- `app/Http/Responses/LoginResponse.php`
- `app/Providers/FortifyServiceProvider.php`
- `app/Rules/StrongPassword.php`
- `app/Http/Requests/StoreUserRequest.php`
- `app/Policies/CompanyPolicy.php`

### Modeles

- `app/Models/User.php`
- `app/Models/Subscription.php`
- `app/Models/Company.php`
- `app/Models/CompanyPhone.php`
- `app/Models/CompanyAccount.php`

### Migrations metier

- `database/migrations/2026_04_25_000001_create_erp_user_access_tables.php`
- `database/migrations/2026_04_25_183442_add_two_factor_columns_to_users_table.php`
- `database/migrations/2026_04_25_235959_add_plan_fields_to_subscriptions_table.php`
- `database/migrations/2026_04_26_190617_update_companies_business_fields.php`

### Vues principales

- `resources/views/auth/login.blade.php`
- `resources/views/main/main.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/subscriptions.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/views/admin/companies.blade.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/views/welcome.blade.php`

### Assets et langues

- `resources/css/auth/login.css`
- `resources/css/main.css`
- `resources/css/admin/dashboard.css`
- `resources/js/auth/login.js`
- `resources/js/main.js`
- `lang/fr/auth.php`
- `lang/en/auth.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `config/countries.php`
- `public/img/logo/exad-1200x1200.jpg`

## Commandes utiles

Lancer le projet :

```powershell
cd D:\CODEX\exad-erp
.\serve-exad.bat
```

Tester le projet :

```powershell
php artisan test
```

Voir les routes :

```powershell
php artisan route:list
```

Ajouter cette documentation au prochain commit :

```powershell
cd D:\CODEX\exad-erp
git add docs/prompts/project-history.md
git commit -m "docs: add complete project development history"
git push origin main
```

Cloner sur l'autre PC :

```powershell
git clone git@github.com:horly/erv-v2.git
cd erv-v2
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Si les assets frontend doivent etre reconstruits :

```powershell
npm install
npm run build
```

## Decisions principales a retenir

- Le bon repertoire local est `D:\CODEX\exad-erp`.
- Le projet est sur la branche `main`.
- Le remote actuel est `git@github.com:horly/erv-v2.git`.
- Le dossier d'historique est `docs/prompts`.
- Le fichier d'historique est `docs/prompts/project-history.md`.
- Laravel Fortify est le socle d'authentification.
- Les roles importants sont `superadmin`, `admin`, `user`.
- Les routes `/admin/*` sont reservees au superadmin.
- Les admins et users utilisent `/main`.
- Les abonnements controlent les limites d'entreprises.
- Les entreprises sont rattachees a un abonnement et a un administrateur.
- L'application supporte francais et anglais.
- SQLite est utilise localement pour le developpement.
- `.env` reste local et ne doit pas etre versionne.

## Prochaines taches recommandees

- Committer ce dossier `docs/prompts`.
- Pousser sur GitHub.
- Verifier que GitHub affiche bien `docs/prompts/project-history.md`.
- Cloner sur l'autre PC et verifier l'installation.
- Nettoyer les comptes de demonstration avant production.
- Deplacer tout secret ou mot de passe demo vers des variables d'environnement si necessaire.
- Enrichir `README.md` avec les instructions d'installation et de reprise.
- Ajouter tests pour creation d'entreprise, limite d'abonnement, upload logo, telephones et comptes bancaires.
- Ajouter validation et affichage plus complet des entreprises cote admin/user.
- Continuer les modules ERP metier : clients, produits, ventes, achats, stocks, factures, paiements, caisse, rapports.
- Continuer a mettre a jour ce fichier apres chaque decision importante.

## Modele pour les prochaines entrees

### YYYY-MM-DD - Titre court de la session

Prompt utilisateur :

```text
Coller ici le prompt important.
```

Reponses / decisions principales :

- Decision 1.
- Decision 2.

Actions realisees :

- Action 1.
- Action 2.

Modifications apportees :

- Fichier ou dossier modifie.
- Fonctionnalite ajoutee.

Problemes rencontres :

- Probleme 1.
- Correction appliquee.

Prochaines taches :

- Tache 1.
- Tache 2.

### 2026-04-27 - Regle de synchronisation .env.example et export base de donnees

Prompt utilisateur :

```text
Je souhaite que a chaque fois que tu modifie mon fichier .env duplique le contenu dans .en.example pour que lorsque je clone mon projet sur autre machine je puisse recuperer les elements.
J'aurai besoin aussi a chaque fois de recuperer tout les les elemnts de ma base de donnees dans un fichier pour me permettre d'exporter la bd dans l'autre machine
```

Decisions :

- A chaque modification de `.env`, mettre a jour `.env.example` pour garder les memes cles et valeurs non sensibles.
- Ne pas copier les secrets dans `.env.example` : `APP_KEY`, mots de passe, tokens, secrets, cles privees, cles API et cles d'acces doivent rester vides.
- Ajouter un script reutilisable pour synchroniser `.env.example` depuis `.env` sans secrets.
- Ajouter un script reutilisable pour exporter la base MySQL configuree dans `.env`.
- Ajouter un script reutilisable pour importer l'export SQL sur une autre machine.

Fichiers ajoutes ou modifies :

- `.env.example` synchronise avec `.env` sans secrets.
- `scripts/sync-env-example.ps1`.
- `scripts/export-database.ps1`.
- `scripts/import-database.ps1`.
- `database/exports/README.md`.

Commandes utiles :

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\sync-env-example.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\export-database.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\import-database.ps1
```

Resultat actuel :

- La synchronisation `.env.example` a reussi.
- L'export MySQL n'a pas pu etre genere car MySQL/XAMPP ne repondait pas sur `127.0.0.1:3306` au moment de l'execution.
- Des que MySQL est lance, relancer `scripts/export-database.ps1` pour creer `database/exports/erp_database.sql`.

Regle pour la suite :

- Si `.env` est modifie pendant une session, relancer ensuite `scripts/sync-env-example.ps1`.
- Si la base de donnees est modifiee avec des donnees a transporter vers une autre machine, relancer ensuite `scripts/export-database.ps1` et versionner l'export SQL si son contenu peut etre partage.

### 2026-04-27 - Correction boucle de redirection navigateur

Probleme utilisateur :

```text
Cette page ne fonctionne pas
127.0.0.1 vous a redirige a de trop nombreuses reprises.
ERR_TOO_MANY_REDIRECTS
```

Diagnostic :

- Laravel etait accessible cote serveur.
- `/login` repondait correctement en `200 OK` sans cookies.
- MySQL et la base `erp_database` etaient accessibles.
- Les migrations etaient executees.
- Le probleme correspondait a une boucle de redirection cote navigateur/session.
- Le middleware `EnsureUserIsSuperadmin` redirigeait un utilisateur non-superadmin vers l'URL precedente quand il tentait d'ouvrir une route admin, ce qui pouvait creer une boucle avec certains historiques/cookies navigateur.

Correction appliquee :

- Simplification de `app/Http/Middleware/EnsureUserIsSuperadmin.php`.
- Un utilisateur non-superadmin est maintenant toujours redirige vers la route `main`, au lieu de l'URL precedente.
- Vidage des caches Laravel avec `php artisan optimize:clear`.
- Vidage de la table `sessions` pour supprimer les anciennes sessions navigateur.
- Redemarrage du serveur local Laravel sur `127.0.0.1:8000`.

Verification :

- `/login` repond en `200 OK`.
- `php artisan route:list` fonctionne.
- `php artisan test` passe avec `17 passed` et `56 assertions`.

Action conseillee cote navigateur :

- Ouvrir directement `http://127.0.0.1:8000/login`.
- Si le navigateur garde encore l'ancienne boucle, supprimer les cookies du site `127.0.0.1` ou tester en navigation privee.

### 2026-04-27 - Correction definitive de la boucle /login -> / -> /login

Probleme :

- Le navigateur affichait `ERR_TOO_MANY_REDIRECTS` sur `127.0.0.1`.
- La premiere correction du middleware superadmin n'etait pas suffisante.

Cause reelle identifiee :

- Le middleware `guest` de Laravel redirige automatiquement un utilisateur deja authentifie qui visite `/login`.
- Comme aucune route `dashboard` ou `home` n'existait, Laravel utilisait `/` comme destination par defaut.
- Dans ce projet, `/` redirige vers `/login`.
- Cela creait une boucle pour un utilisateur deja connecte : `/login -> / -> /login -> /`.

Correction appliquee :

- Ajout dans `bootstrap/app.php` :

```php
$middleware->redirectUsersTo(fn () => route('main'));
```

- Ainsi, un utilisateur deja connecte qui visite `/login` est envoye vers `/main` au lieu de `/`.
- Ajout d'un test de non-regression : un utilisateur authentifie qui ouvre `/login` doit etre redirige vers `main`.
- Vidage des caches Laravel.
- Vidage de la table `sessions`.
- Redemarrage du serveur local.

Verification :

- `curl -I -L --max-redirs 10 http://127.0.0.1:8000/` donne maintenant `/ -> /login -> 200 OK`.
- `php artisan test` passe avec `18 passed` et `58 assertions`.

Note importante :

- L'application Laravel tourne sur `http://127.0.0.1:8000` avec `artisan serve`.
- `http://127.0.0.1` sans port va sur Apache/XAMPP port 80, pas directement sur ce serveur Laravel.

### 2026-04-27 - Lien fil d'Ariane vers la liste des entreprises

Prompt utilisateur :

```text
sur la page : admin/companies/create
rend "entreprises" cliquable pour me ramener vers la page companies
```

Modification appliquee :

- Dans `resources/views/admin/companies-create.blade.php`, le segment `Entreprises` du fil d'Ariane est maintenant un lien vers `route('admin.companies')`.
- Ajout de la classe CSS `breadcrumb-link` dans `resources/css/admin/dashboard.css` pour garder le style du fil d'Ariane avec un hover visible.
- Ajout d'un test fonctionnel confirmant que `/admin/companies/create` contient un lien vers la page `admin.companies`.

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=company_create` passe.

### 2026-04-27 - Catalogue mondial des pays avec indicatifs et TVA

Prompt utilisateur :

```text
lors de la selection du pays quand on ajoute une entreprise, je souhaite que tu mets tous les pays du monde avec leurs indicatifs telephoniques, % tva, les pays qui n'ont pas de tva tu mets zero ces informations vont me servir plus tard
```

Modification appliquee :

- Remplacement de `config/countries.php` par un catalogue structure de pays.
- Chaque pays contient :
  - `iso` : code ISO alpha-2.
  - `name` : nom du pays affiche dans le formulaire.
  - `phone_code` : indicatif telephonique international.
  - `vat_rate` : taux standard TVA/GST de reference en pourcentage.
- Les pays sans TVA generale ou sans TVA nationale exploitable sont mis a `0`.
- Le formulaire `admin/companies/create` affiche maintenant le pays sous la forme : `Pays (+indicatif - TVA x,xx%)`.
- Les options HTML contiennent aussi `data-iso`, `data-phone-code` et `data-vat-rate` pour reutilisation future en JavaScript ou dans d'autres modules ERP.
- La validation cote serveur a ete adaptee pour accepter les noms depuis la nouvelle structure.

Fichiers modifies :

- `config/countries.php`.
- `app/Http/Controllers/AdminController.php`.
- `resources/views/admin/companies-create.blade.php`.
- `tests/Feature/ExampleTest.php`.

Verification :

- Ajout de tests pour verifier le catalogue pays et le rendu du select.
- `php artisan test` passe avec `21 passed` et `70 assertions`.

Note fiscale :

- Les taux TVA/GST sont des valeurs de reference pour pre-remplissage ERP.
- Ils doivent etre verifies avant toute generation de documents fiscaux officiels, car certains pays ont des taux regionaux, sectoriels ou des changements frequents.

### 2026-04-27 - Affichage bilingue des pays

Prompt utilisateur :

```text
n'oublie pas que mon projet est bilingue donc lorsque je traduit l'application en anglais les informations du pays doivent également s'afficher en anglais
```

Modification appliquee :

- Le catalogue `config/countries.php` contient maintenant des noms de pays en francais et en anglais : `name_fr` et `name_en`.
- Le champ historique `name` reste disponible et correspond au nom francais pour compatibilite avec les donnees existantes.
- Le formulaire `admin/companies/create` affiche le nom du pays selon la langue active :
  - francais : `Congo (RDC) (+243 - TVA 16,00%)`.
  - anglais : `Congo (DRC) (+243 - VAT 16,00%)`.
- Les options du select exposent aussi `data-name-fr` et `data-name-en` pour reutilisation future.
- La liste des entreprises affiche le pays dans la langue active quand la valeur sauvegardee correspond au catalogue.
- La validation continue d'accepter le nom francais sauvegarde en base pour ne pas casser les entreprises deja creees.

Fichiers modifies :

- `config/countries.php`.
- `resources/views/admin/companies-create.blade.php`.
- `resources/views/admin/companies.blade.php`.
- `tests/Feature/ExampleTest.php`.

Verification :

- Ajout de tests pour l'affichage anglais du select pays.
- `php artisan test` passe avec `22 passed` et `73 assertions`.
