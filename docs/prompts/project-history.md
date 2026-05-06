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

### 2026-04-28 - Creation du fichier .env depuis .env.example

Prompt utilisateur :

```text
crée un fichier .env et rempli le data par rapport à .env.example
```

Actions realisees :

- Verification que le fichier `.env` n'existait pas encore dans le nouveau repertoire local `d:\App\Codex\erv-v2`.
- Creation du fichier `.env` par copie de `.env.example`.
- Generation d'une cle `APP_KEY` locale valide et ajout dans `.env`.

Probleme rencontre :

- La commande `php artisan key:generate` a echoue avec l'erreur :

```text
Class "Locale" not found
```

- L'erreur vient de `config/countries.php` et indique probablement que l'extension PHP `intl` n'est pas activee sur cette machine.

Correction appliquee :

- Generation manuelle d'une cle Laravel compatible au format `base64`.
- Remplacement de `APP_KEY=` dans `.env` par la cle generee.

Decision securite :

- `.env.example` n'a pas ete modifie avec la valeur de `APP_KEY`, car cette cle est un secret local.
- La regle reste : synchroniser `.env.example` avec `.env` uniquement pour les cles et valeurs non sensibles.

### 2026-04-28 - Rappel de mise a jour systematique de l'historique

Prompt utilisateur :

```text
n'oublie pas de toujours mettre à jour l'historique des prompts
```

Decision :

- A chaque session ou modification importante, mettre a jour `docs/prompts/project-history.md`.
- Documenter les prompts utilisateur importants, les decisions, les fichiers modifies, les problemes rencontres et les prochaines taches.
- Continuer a ne jamais inscrire de secrets dans ce fichier, notamment les valeurs reelles de `.env`, mots de passe, tokens, cles API ou `APP_KEY`.

### 2026-04-28 - Import de la base de donnees depuis database/exports

Prompt utilisateur :

```text
dans le repertoir database/exports/erp_database.sql 
importe cette pase de données
```

Actions realisees :

- Verification de l'existence du fichier `database/exports/erp_database.sql`.
- Verification de la configuration MySQL dans `.env` :
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_DATABASE=erp_database`
  - `DB_USERNAME=root`
- Lecture de l'en-tete du fichier SQL pour confirmer qu'il contient `CREATE DATABASE` et `USE erp_database`.
- Tentative d'import avec `scripts/import-database.ps1`.

Probleme rencontre :

- Le script d'import a echoue car la commande `mysql` n'etait pas disponible dans le `PATH` Windows.

Correction appliquee :

- Utilisation directe de l'executable MySQL/XAMPP :

```powershell
C:\xampp\mysql\bin\mysql.exe
```

- Import effectue avec succes depuis `database/exports/erp_database.sql`.

Verification :

- La base `erp_database` contient les tables principales :
  - `users`
  - `subscriptions`
  - `companies`
  - `company_phones`
  - `company_accounts`
  - `company_user`
  - tables Laravel de cache, jobs, sessions et migrations.
- Comptages verifies apres import :
  - `users` : 6
  - `companies` : 2
  - `subscriptions` : 8

Note pour la suite :

- Pour faciliter les prochains imports, soit ajouter `C:\xampp\mysql\bin` au `PATH`, soit adapter `scripts/import-database.ps1` pour detecter automatiquement `C:\xampp\mysql\bin\mysql.exe` lorsque `mysql` n'est pas disponible.

### 2026-04-28 - Diagnostic erreur Class "Locale" not found

Prompt utilisateur :

```text
j'ai cette erreur pourquoi ?
```

Erreur observee :

```text
In countries.php line 240:
Class "Locale" not found
```

Diagnostic :

- L'erreur vient de `config/countries.php`, ligne 240.
- Le fichier utilise `Locale::getDisplayRegion(...)` pour trouver le nom anglais d'un pays.
- La classe PHP `Locale` est fournie par l'extension PHP `intl`.
- Sur cette machine, `php -m` ne montre pas l'extension `intl`.
- Le PHP CLI utilise le fichier de configuration `C:\xampp\php\php.ini`.

Cause :

- L'extension `intl` n'est pas activee dans le PHP de XAMPP utilise par la commande `php artisan serve`.

Correction recommandee :

- Activer `extension=intl` dans `C:\xampp\php\php.ini`.
- Redemarrer le terminal, puis relancer `php artisan serve`.
- Alternative code possible : rendre `config/countries.php` tolerant si `Locale` est absente, avec un fallback vers le nom francais.

### 2026-04-28 - Correction code de l'erreur Locale absente

Prompt utilisateur :

```text
j'ai toujours l'erreur, regarde l'historique de mes prompts peut etre que ça va t'aider
```

Diagnostic confirme :

- L'historique montrait que l'erreur `Class "Locale" not found` etait deja apparue pendant la generation de `.env`.
- Verification de PHP :
  - `php --ini` charge `C:\xampp\php\php.ini`.
  - `php -m` ne liste toujours pas `intl`.
  - `C:\xampp\php\php.ini` contient encore `;extension=intl`, donc l'extension reste commentee.

Correction appliquee :

- Modification de `config/countries.php`.
- La ligne utilisant `Locale::getDisplayRegion(...)` est maintenant protegee par `class_exists(Locale::class)`.
- Si l'extension `intl` est absente, le code utilise un fallback vers le nom francais du pays au lieu de bloquer Laravel.

Verification :

- `php artisan --version` fonctionne et retourne `Laravel Framework 12.58.0`.
- `php artisan route:list --except-vendor` fonctionne.
- `php artisan test --filter=country` passe avec `3 passed` et `13 assertions`.

Note :

- Activer `intl` dans XAMPP reste recommande pour de meilleurs noms de pays anglais automatiques.
- Mais l'application ne depend plus obligatoirement de cette extension pour demarrer.

### 2026-04-28 - Correction UTF-8 du champ pays dans le formulaire entreprise

Prompt utilisateur :

```text
Sur la page /admin/compagnies/create 
sur le formulaire dans le champs pays le text n'est pas formaté en UTF-8
```

Probleme observe :

- Dans le champ `Pays` du formulaire `admin/companies/create`, certains noms de pays accentues etaient affiches sous forme corrompue, par exemple `CÃ...te d'Ivoire`.
- Le probleme ne venait pas de Blade ni du navigateur : le fichier source `config/countries.php` contenait deja des textes mal encodes.

Correction appliquee :

- Remplacement des noms de pays francais corrompus dans `config/countries.php` par de vraies chaines UTF-8.
- Exemples corriges :
  - `Côte d'Ivoire`
  - `Algérie`
  - `Bénin`
  - `Équateur`
  - `Égypte`
  - `Guinée équatoriale`
  - `Îles Marshall`
  - `États-Unis`
- Ajout d'une verification de non-regression dans `tests/Feature/ExampleTest.php` :
  - le catalogue doit contenir `Côte d'Ivoire`;
  - le formulaire doit afficher `Côte d'Ivoire (+225 - TVA 18,00%)`;
  - le formulaire ne doit pas contenir la sequence corrompue `CÃ`.

Verification :

- Recherche confirmee : plus aucune sequence `Ã`, `Â` ou `â` dans `config/countries.php`.
- `php artisan test --filter=country` passe avec `3 passed` et `16 assertions`.

### 2026-04-28 - Standard loading pour tous les formulaires

Prompt utilisateur :

```text
Je souhaite uniformiser le comportement de tous les formulaires existants et futurs de mon application.

Pour chaque formulaire de création ou de modification, par exemple les pages create et edit, lorsqu’un utilisateur clique sur le bouton d’enregistrement, de création, de mise à jour ou de soumission, il faut afficher automatiquement un état de chargement.

Le bouton doit afficher un loading, être temporairement désactivé pour éviter les doubles soumissions, puis revenir à son état normal une fois l’action terminée ou en cas d’erreur.

Ce même modèle doit être appliqué à tous les formulaires existants et devra également servir de standard pour tous les nouveaux formulaires que je créerai par la suite, afin de garder une interface cohérente dans toute l’application
```

Decision :

- Mettre en place un comportement JavaScript global plutot que de traiter chaque formulaire un par un.
- Tous les formulaires qui chargent `resources/js/main.js` ou `resources/js/auth/login.js` ont maintenant automatiquement un etat de soumission.
- Le standard peut etre desactive sur un formulaire precis avec l'attribut `data-no-submit-loading`.
- Un texte personnalise peut etre defini sur un bouton avec `data-loading-label`.

Comportement ajoute :

- Au submit valide :
  - le formulaire recoit la classe `is-submitting`;
  - les boutons de soumission du formulaire sont desactives;
  - le bouton declencheur recoit `aria-busy="true"`;
  - le bouton affiche un spinner et le libelle `Traitement...` en francais ou `Processing...` en anglais.
- Si la validation JavaScript bloque le formulaire, le bouton revient immediatement a son etat normal.
- Lors d'un retour navigateur via cache (`pageshow`), les boutons sont reinitialises.
- Les suppressions avec confirmation sont aussi protegees contre les doubles clics apres confirmation.

Fichiers modifies :

- `resources/js/main.js`
- `resources/js/auth/login.js`
- `resources/css/admin/dashboard.css`
- `resources/css/auth/login.css`

Verification :

- `php artisan test` passe avec `22 passed` et `76 assertions`.

### 2026-04-28 - Placeholder par defaut pour logos entreprise absents

Prompt utilisateur :

```text
Lors de la création d’une entreprise, l’ajout du logo est déjà optionnel.

Actuellement, lorsqu’une entreprise est enregistrée sans logo, cela provoque un bug d’affichage de l’image dans la liste ou les vues concernées.

Je souhaite donc mettre en place une gestion par défaut :

si une entreprise n’a pas de logo,
alors afficher automatiquement une icône par défaut ou une image placeholder à la place du logo,
afin d’éviter toute erreur d’affichage et de garder une interface propre et cohérente.

Ce comportement doit être appliqué partout où le logo de l’entreprise est affiché.
```

Decision :

- Ne plus afficher une balise `<img>` quand aucun logo exploitable n'existe.
- Centraliser la resolution du logo dans le modele `Company` via l'attribut `logo_url`.
- Considerer comme logo absent :
  - une valeur `logo` vide ou nulle;
  - un chemin local qui ne correspond a aucun fichier dans le disque public;
  - tout chemin local casse ou supprime.

Modifications appliquees :

- Ajout de `getLogoUrlAttribute()` dans `app/Models/Company.php`.
- Mise a jour de `resources/views/admin/companies.blade.php` :
  - si `logo_url` existe, afficher l'image;
  - sinon afficher une icone Bootstrap `bi-building` dans un bloc `placeholder-logo`.
- Amelioration du style `.placeholder-logo` dans `resources/css/admin/dashboard.css`.
- Ajout d'un test de non-regression dans `tests/Feature/ExampleTest.php`.

Verification :

- Le test `companies_page_uses_placeholder_when_logo_is_missing` passe.
- `php artisan test` passe avec `23 passed` et `81 assertions`.

### 2026-04-28 - Diagnostic logos entreprise presents en base mais non affiches

Prompt utilisateur :

```text
dans ma base de donnée j'ai 2 entreprises qui ont un logo mais ne s'affiche toujours pas
```

Diagnostic :

- La table `companies` contient deux chemins de logo :
  - `Test Entreprise` : `company-logos/iiW6TXFrFR73wEWiSWgGlWRpdsX5RuHnAq4DeFrG.jpg`
  - `EXAD` : `company-logos/sJkem1sKETkIF88RE0z25XXFex4UONOUApjQ0flC.jpg`
- Le fichier du logo `EXAD` existe bien dans `storage/app/public/company-logos`.
- Le fichier du logo `Test Entreprise` n'existe pas dans `storage/app/public/company-logos`.
- Le lien public `public/storage` etait absent, donc les images stockees dans `storage/app/public` ne pouvaient pas etre servies par le navigateur.

Correction appliquee :

- Execution de la commande Laravel :

```powershell
php artisan storage:link
```

- Creation du lien `public/storage` vers `storage/app/public`.
- Verification que le fichier `public/storage/company-logos/sJkem1sKETkIF88RE0z25XXFex4UONOUApjQ0flC.jpg` est maintenant accessible cote filesystem.

Resultat attendu :

- Le logo `EXAD` doit maintenant s'afficher apres actualisation de la page.
- `Test Entreprise` continue d'afficher le placeholder, car le chemin existe en base mais le fichier image correspondant est absent du disque.

Note pour les clones futurs :

- Apres un `git clone`, executer `php artisan storage:link` pour rendre les fichiers publics accessibles.
- Les fichiers uploads dans `storage/app/public` doivent etre copies/exportes avec la base si l'on veut conserver les logos sur une autre machine.

Verification :

- `php artisan test --filter=companies_page_uses_placeholder_when_logo_is_missing` passe.

### 2026-04-28 - Correction APP_URL pour affichage des logos publics

Prompt utilisateur :

```text
ça ne marche toujoues pas le dernier entreprise EXAD que je viens d'ajouter j'ai changé le logo mais il y a un bug d'affichage et rien n'a changé
```

Diagnostic :

- Le fichier logo EXAD existe bien dans `storage/app/public/company-logos`.
- Le lien `public/storage` existe bien apres `php artisan storage:link`.
- L'URL directe `http://127.0.0.1:8000/storage/company-logos/...jpg` retourne bien `200`.
- La configuration Laravel generait cependant les URLs de fichiers publics avec `http://localhost/storage`, car `APP_URL=http://localhost`.
- Comme l'application tourne avec `php artisan serve` sur `http://127.0.0.1:8000`, l'image etait demandee sur le mauvais host/port.

Correction appliquee :

- Modification de `.env` :

```text
APP_URL=http://127.0.0.1:8000
```

- Synchronisation de `.env.example` avec la meme valeur non sensible.
- Vidage du cache Laravel :

```powershell
php artisan optimize:clear
```

Verification :

- `php artisan config:show app.url` retourne `http://127.0.0.1:8000`.
- `php artisan config:show filesystems.disks.public.url` retourne `http://127.0.0.1:8000/storage`.
- `php artisan test --filter=companies_page_uses_placeholder_when_logo_is_missing` passe.

Action conseillee cote navigateur :

- Actualiser la page avec `Ctrl + F5`.
- Si le serveur Laravel etait deja lance avant la modification, l'arreter puis relancer `php artisan serve`.

### 2026-04-28 - Export/import des fichiers publics uploades

Prompt utilisateur :

```text
ça marche pour EXAD mais pas pour test entreprise qui a aussi un logo. 
test entreprise je l'avais enregistré dans une autre machine peut etre git n'a pas importé les fichiers se trouvant dans public
```

Diagnostic :

- Confirmation : Git n'importe pas les fichiers uploades de `storage/app/public`.
- `storage/app/public/.gitignore` ignore tous les fichiers sauf `.gitignore`.
- La base de donnees conserve seulement le chemin du logo, par exemple `company-logos/iiW6TXFrFR73wEWiSWgGlWRpdsX5RuHnAq4DeFrG.jpg`.
- Si le fichier image correspondant n'existe pas dans `storage/app/public/company-logos`, le logo ne peut pas s'afficher meme si la base contient une valeur.
- Sur cette machine, seul le fichier du logo EXAD existe dans `storage/app/public/company-logos`.
- Le logo de `Test Entreprise` doit etre recupere depuis l'autre machine ou reuploade.

Correction / amelioration appliquee :

- Ajout du script `scripts/export-public-storage.ps1` pour creer une archive :

```text
database/exports/public-storage.zip
```

- Ajout du script `scripts/import-public-storage.ps1` pour restaurer les fichiers publics uploades dans `storage/app/public`.
- Mise a jour de `database/exports/README.md` avec les commandes d'export/import des fichiers publics.

Commandes utiles :

Depuis la machine qui contient tous les logos :

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\export-public-storage.ps1
```

Sur l'autre machine apres recuperation de `database/exports/public-storage.zip` :

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\import-public-storage.ps1
php artisan storage:link
```

Verification :

- L'archive `database/exports/public-storage.zip` a ete generee.
- Elle contient actuellement le logo EXAD seulement, car le fichier du logo `Test Entreprise` est absent de cette machine.
- `php artisan test --filter=companies_page_uses_placeholder_when_logo_is_missing` passe.

### 2026-04-28 - Select mondial des devises pour les comptes bancaires entreprise

Prompt utilisateur :

```text
lors de l'ajout du numéro de compte dans l'entreprise. Je préfère que ça soit un select avec toutes les devises du monde comme tu l'as fais avec les pays. 
Affiche le nom de la devise, et l'iso code
```

Decision :

- Remplacer le champ texte libre `Devise` par un select alimente par un catalogue de devises.
- Enregistrer en base le code ISO 4217 de la devise, par exemple `CDF`, `USD`, `EUR`.
- Afficher dans le formulaire le nom de la devise suivi du code ISO, par exemple `Franc congolais (CDF)`.
- Garder le comportement bilingue FR/EN comme pour les pays.

Modifications appliquees :

- Ajout de `config/currencies.php`.
- Chaque devise contient :
  - code ISO 4217 comme cle;
  - `name_fr`;
  - `name_en`.
- Modification de `AdminController@createCompany` pour passer `currencies` a la vue.
- Modification de la validation `accounts.*.currency` pour accepter uniquement les codes presents dans `config('currencies')`.
- Modification de `resources/views/admin/companies-create.blade.php` :
  - les lignes de compte existantes utilisent maintenant un `<select>`;
  - le template des lignes ajoutees dynamiquement utilise aussi le meme `<select>`.
- Modification de `resources/js/main.js` pour reinitialiser aussi les `<select>` quand la derniere ligne dynamique est videe.

Verification :

- Ajout de tests pour verifier :
  - l'affichage francais `Franc congolais (CDF)`, `Dollar américain (USD)`, `Euro (EUR)`;
  - l'affichage anglais `Congolese franc (CDF)`, `United States dollar (USD)`;
  - l'enregistrement d'une entreprise avec compte bancaire en devise `CDF`.
- `php artisan test --filter=currency` passe avec `3 passed` et `10 assertions`.
- `php artisan test` passe avec `26 passed` et `91 assertions`.

### 2026-04-28 - Symboles monetaires et standard d'affichage des devises

Prompt utilisateur :

```text
ajoute également les symboles nommétaire sur la devise si possibles et je souhaites que les noms de la devise s'affiche en ordre alphabetique. 
Enregistre le comportement car nous allons l'appliquer partout où l'on mettra la devise.
```

Decision :

- Le code ISO 4217 reste la valeur enregistree en base, par exemple `CDF`, `USD`, `EUR`.
- L'affichage utilisateur standard devient :

```text
Nom de la devise (CODE ISO - symbole)
```

- Exemples :
  - `Franc congolais (CDF - FC)`
  - `Dollar américain (USD - $)`
  - `Euro (EUR - €)`
- Quand un symbole n'est pas connu, le catalogue peut utiliser le code ISO comme fallback.
- Les devises doivent etre triees alphabetiquement par nom affiche, selon la langue active.
- Ce comportement doit etre reutilise partout ou une devise sera affichee ou selectionnee.

Modifications appliquees :

- Ajout de `symbol` aux devises dans `config/currencies.php`.
- Ajout de `app/Support/CurrencyCatalog.php` pour centraliser le comportement :
  - `CurrencyCatalog::all()`
  - `CurrencyCatalog::sorted()`
  - `CurrencyCatalog::label()`
- `AdminController@createCompany` utilise maintenant `CurrencyCatalog::sorted()` pour fournir les devises deja triees a la vue.
- La validation serveur continue d'utiliser les codes ISO connus via `CurrencyCatalog::all()`.
- Le formulaire `admin/companies/create` affiche maintenant les options sous la forme `Nom (ISO - symbole)`.

Verification :

- Les tests devises verifient maintenant les symboles et l'ordre alphabetique.
- `php artisan test --filter=currency` passe avec `3 passed` et `12 assertions`.
- `php artisan test` passe avec `26 passed` et `93 assertions`.

### 2026-04-28 - Edition/suppression entreprise et base des sites de production

Prompt utilisateur :

```text
pour la modification de l'entreprise, lorsque l'utilisateur clique sur l'icone de modification, il est redirigé vers le meme formulaire de création. Mais cette fois-ci c'est pour mettre à jour les elements de l'entreprise. 
Les entreprises ont des sites de production. 
voici les champs pour le site de production : 
- Nom (chaine de caractère obligatoire)
- Type (Production, Entrepot, Bureau, Boutique, archive, autres) champs obligatoire
- code (chaine de caractère non obligatoire)
- responsable (selectionner tous les admin appartenant à l'abonnement de l'utilisateur connecté) 
- ville (non obligatoire) 
- téléphone (non obligatoire) 
- adresse (non obligatoire)
- modules (obligatoires; voici les modules : Comptabilité (facturation); Ressources Humaines, Archivage, GED (Gestion de courrier))
- email (non obligatoite)
- la devise de gestion gestion du site (obligatoire) 

Pour la suppression de l'entreprise. tu sais déjà comment faire en suivant le design que je t'avais dit de faire pour toutes les suppressions. 
Mais seul les entreprises ne poussedant pas de site seront supprimable 

Pour l'interface de site de production de crée pas encore, dans un premier temps fais d'abord la base de données. 

Dans la liste d'entreprises crée aussi une rubrique pour afficher le nombre de sites que l'entreprise a
```

Decisions :

- Reutiliser le formulaire `admin/companies/create` pour la creation et la modification d'entreprise.
- Ajouter les routes REST minimales pour les entreprises :
  - `GET /admin/companies/{company}/edit`
  - `PUT /admin/companies/{company}`
  - `DELETE /admin/companies/{company}`
- Ne pas creer l'interface de gestion des sites pour l'instant.
- Creer seulement la base de donnees et le modele des sites.
- Bloquer la suppression d'une entreprise qui possede au moins un site.
- Afficher le nombre de sites dans la liste des entreprises.

Base de donnees ajoutee :

- Migration `database/migrations/2026_04_28_000001_create_company_sites_table.php`.
- Table `company_sites` avec :
  - `company_id`
  - `responsible_id`
  - `name`
  - `type`
  - `code`
  - `city`
  - `phone`
  - `email`
  - `address`
  - `modules` en JSON
  - `currency`
- Types prevus :
  - `production`
  - `warehouse`
  - `office`
  - `shop`
  - `archive`
  - `other`
- Modules prevus :
  - `accounting`
  - `human_resources`
  - `archiving`
  - `document_management`

Modifications appliquees :

- Ajout du modele `app/Models/CompanySite.php`.
- Ajout de la relation `Company::sites()`.
- `AdminController` :
  - chargement de `sites_count` dans la liste des entreprises;
  - ajout de `editCompany`;
  - ajout de `updateCompany`;
  - ajout de `destroyCompany`;
  - factorisation des regles de validation entreprise;
  - suppression impossible si l'entreprise possede des sites.
- `resources/views/admin/companies.blade.php` :
  - nouvelle colonne `Sites`;
  - bouton modifier redirige vers le formulaire d'edition;
  - bouton supprimer avec confirmation;
  - bouton supprimer desactive si `sites_count > 0`.
- `resources/views/admin/companies-create.blade.php` :
  - formulaire reutilisable pour creation et modification;
  - pre-remplissage des champs entreprise;
  - pre-remplissage des telephones et comptes bancaires;
  - preview du logo existant en modification;
  - methode `PUT` en mode edition.
- Traductions FR/EN ajoutees pour :
  - edition entreprise;
  - suppression entreprise;
  - message d'impossibilite de suppression avec sites.

Verification :

- Migration locale executee avec succes :

```powershell
php artisan migrate --force
```

- `php artisan route:list --except-vendor` affiche les nouvelles routes entreprise.
- `php artisan test --filter=company` passe avec `10 passed` et `41 assertions`.
- `php artisan test` passe avec `31 passed` et `115 assertions`.

### 2026-04-28 - Dashboard admin avec graphiques ApexCharts et donnees reelles

Prompt utilisateur :

```text
Sur base des images joins. 
Mets à jours le tableau de bord admin avec des shart réels ApexCharts par exemple, crée un fichier js isolé uniquement pour cette page. 
recupère les informations réels de ma base de données pour le graphique
```

Decision :

- Remplacer les faux graphiques SVG statiques du dashboard par des graphiques ApexCharts.
- Garder un fichier JavaScript isole uniquement pour cette page :

```text
resources/js/admin/dashboard.js
```

- Calculer toutes les donnees dans `AdminController@dashboard` depuis la base de donnees.
- Exposer les donnees a la vue via un bloc JSON `dashboardChartData`.

Donnees reelles ajoutees :

- KPI :
  - abonnements;
  - utilisateurs;
  - administrateurs;
  - entreprises;
  - sites.
- Graphique evolution mensuelle :
  - abonnements crees;
  - utilisateurs crees.
- Graphique repartition des roles :
  - admin;
  - superadmin;
  - user.
- Graphique utilisateurs par entreprise :
  - donnees issues de `companies` avec `users_count`.
- Graphique activite globale annuelle :
  - abonnements;
  - entreprises;
  - utilisateurs.
- Top entreprises :
  - classement selon nombre de sites puis nombre d'utilisateurs.
- Activite recente :
  - nouveaux utilisateurs;
  - nouveaux abonnements;
  - nouveaux sites.

Modifications appliquees :

- `AdminController@dashboard` prepare maintenant `chartData`, `topCompanies` et `recentActivities`.
- `resources/views/admin/dashboard.blade.php` contient les conteneurs ApexCharts :
  - `subscriptionsEvolutionChart`;
  - `rolesDistributionChart`;
  - `usersByCompanyChart`;
  - `globalActivityChart`.
- Ajout de `resources/js/admin/dashboard.js`.
- La vue charge ApexCharts depuis CDN puis le JS isole du dashboard.
- `resources/css/admin/dashboard.css` contient les styles des nouveaux blocs :
  - charts Apex;
  - top entreprises;
  - activite recente.
- Traductions FR/EN ajoutees :
  - `top_companies`;
  - `recent_activity`;
  - `recent_new_user`;
  - `recent_new_subscription`;
  - `recent_new_site`.

Verification :

- Le test `superadmin_can_open_admin_dashboard` verifie maintenant :
  - le JSON `dashboardChartData`;
  - les conteneurs ApexCharts;
  - la presence du JS dedie.
- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.
- `php artisan test` passe avec `31 passed` et `122 assertions`.

### 2026-04-28 - Periode par defaut du dashboard sur l'annee

Prompt utilisateur :

```text
par défut ça doit etre l'année
```

Modification appliquee :

- Dans `resources/views/admin/dashboard.blade.php`, le bouton actif du selecteur de periode est maintenant `Année` au lieu de `Mois`.

Verification :

- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.

### 2026-04-28 - Rectification periode dashboard et bascule Semaine/Mois/Annee

Prompt utilisateur :

```text
- première image quand je clique sur semaine 
- deuxième image quand je clique sur mois
- troisième image quand je clique sur années

rectification lorsque je charge la page c'est le mois qui est séléctionné
```

Correction appliquee :

- Le bouton actif par defaut est revenu sur `Mois`.
- Les boutons `Semaine`, `Mois` et `Année` possedent maintenant `data-dashboard-period`.
- `AdminController@dashboard` fournit maintenant les donnees du graphique d'evolution pour trois periodes :
  - `week`;
  - `month`;
  - `year`.
- `resources/js/admin/dashboard.js` met a jour le graphique ApexCharts au clic sur une periode :
  - series abonnements;
  - series utilisateurs;
  - labels de l'axe X.

Verification :

- Le test dashboard verifie que `Mois` est actif par defaut et que les periodes sont exposees au JS.
- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.
- `php artisan test` passe avec `31 passed` et `126 assertions`.

### 2026-04-28 - Correction visuelle des icones d'activite recente

Prompt utilisateur :

```text
les icones ne s'affiche pas correctement.
Prends l'exemple du deuxième image. 
et lorsque tu vas rectifié reduit légèrement taille de texte
```

Correction appliquee :

- Ajustement CSS de la timeline `Activité récente`.
- Les icones sont maintenant centrees dans leur cercle.
- Les cercles d'icone sont legerement reduits.
- La ligne verticale est realignee au centre des icones.
- Le titre et le sujet de l'activite s'affichent sur la meme ligne, comme dans la reference.
- La taille du texte de l'activite recente est legerement reduite.

Fichier modifie :

- `resources/css/admin/dashboard.css`

Verification :

- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.

### 2026-04-28 - Remplacement des icones activite recente par SVG inline

Prompt utilisateur :

```text
ça ne marche toujours pas si possible utilise meme fontawesome pour les icones
```

Diagnostic :

- Font Awesome n'est pas present localement dans `public/vendor`.
- Le probleme venait du rendu des icones Bootstrap Icons dans les cercles de la timeline : elles etaient decalees et debordaient visuellement.

Correction appliquee :

- Remplacement des icones Bootstrap Icons uniquement dans la section `Activité récente` par des SVG inline.
- Chaque type d'activite a son icone SVG :
  - utilisateur;
  - site;
  - abonnement.
- Les SVG sont centres via `.activity-icon svg` dans `resources/css/admin/dashboard.css`.
- Cette solution evite une dependance CDN supplementaire et garantit un alignement stable.

Fichiers modifies :

- `resources/views/admin/dashboard.blade.php`
- `resources/css/admin/dashboard.css`

Verification :

- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.

### 2026-04-28 - Correction definitive icones activite recente

Prompt utilisateur :

```text
ça ne fonctionne toujours pas les icones sont toujours en desordres
```

Correction appliquee :

- Abandon des bibliotheques d'icones pour la timeline `Activité récente`.
- Remplacement des icones par des symboles typographiques simples et centres :
  - utilisateur : `+`
  - site : `⌖`
  - abonnement : `◆`
- Utilisation de `display: grid` et `place-items: center` sur `.activity-icon`.
- Suppression du rendu SVG complexe pour eviter tout decalage de viewBox, police ou alignement.
- Vidage du cache des vues Laravel avec `php artisan view:clear`.

Fichiers modifies :

- `resources/views/admin/dashboard.blade.php`
- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

### 2026-05-05 - Devises proforma limitees aux devises du site

Prompt utilisateur :

```text
Pour la selection de la devise merci de donner seulement la possibilité de choisir les devises du site
```

Correction appliquee :

- La page de creation proforma et le modal proforma affichent uniquement les devises actives configurees sur le site.
- La devise de base du site est creee/garantie avant affichage de la page.
- La validation serveur refuse maintenant une devise qui n'est pas active sur le site.
- Ajout d'un test pour verifier qu'une devise globale non configuree sur le site n'est ni affichee ni acceptee.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

### 2026-05-05 - Recherche dans les selects Article et Service des proformas

Prompt utilisateur :

```text
je souhaite que pour les articles et services que tu mettes un select avec possibilité de recherche
```

Correction appliquee :

- Ajout d'un select recherche maison pour les champs Article et Service des lignes de facture proforma.
- Conservation des champs select natifs pour la soumission et la validation Laravel.
- Recherche insensible aux accents et a la casse, avec selection au clic ou via Entree sur le premier resultat visible.
- Application automatique aux lignes existantes et aux lignes ajoutees dynamiquement.

Fichiers modifies :

- `resources/views/main/modules/partials/proforma-line-row.blade.php`
- `resources/js/main/accounting-proforma-invoices.js`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-05-05 - Methode de remise sur les lignes proforma

Prompt utilisateur :

```text
donne la possibilité à l'utilisateur de choisir la méthode d'application de la remise, soit brute ou en %
```

Correction appliquee :

- Ajout d'un champ Methode remise sur chaque ligne de facture proforma.
- Deux modes disponibles : Montant et %.
- Les calculs JS et serveur prennent maintenant en charge les remises en pourcentage.
- Les anciennes lignes restent compatibles : sans methode envoyee, la remise est traitee comme un montant.
- Ajout de la colonne `discount_type` dans les lignes de proforma et mise a jour de l'export SQL.

Fichiers modifies :

- `app/Models/AccountingProformaInvoiceLine.php`
- `app/Http/Controllers/MainController.php`
- `database/migrations/2026_05_05_000001_add_discount_type_to_accounting_proforma_invoice_lines_table.php`
- `database/exports/erp_database.sql`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/partials/proforma-line-row.blade.php`
- `resources/js/main/accounting-proforma-invoices.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

### 2026-05-05 - Devises des articles et services limitees au site

Prompt utilisateur :

```text
pareil pour les articles et services
```

Correction appliquee :

- Les formulaires d'articles et de services affichent uniquement les devises actives configurees sur le site.
- La devise de base du site est garantie avant affichage et validation des ressources stock/service.
- La validation serveur refuse maintenant une devise non active ou non configuree sur le site pour les articles et les services.
- Ajout de tests couvrant l'affichage des devises du site et le rejet d'une devise hors site.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.

### 2026-04-29 - Authentification a deux facteurs par QR Code

Prompt utilisateur :

```text
Dans mon projet Laravel, je souhaite ajouter une fonctionnalite d'authentification a deux facteurs dans la page Profil de chaque utilisateur.
```

Implementation :

- Activation de la fonctionnalite 2FA native Laravel Fortify avec confirmation obligatoire par code TOTP.
- Ajout du trait `TwoFactorAuthenticatable` sur le modele `User`.
- Masquage des secrets 2FA dans les donnees serialisees et cast de `two_factor_confirmed_at`.
- Creation de reponses Fortify personnalisees pour :
  - rediriger apres validation 2FA selon le role de l'utilisateur;
  - afficher une erreur claire en cas de code invalide.
- Ajout de la page `/two-factor-challenge` pour verifier le code TOTP apres email/mot de passe.
- Ajout dans la page Profil d'une section "Authentification a deux facteurs" :
  - statut `Non configure` ou `Active`;
  - bouton de configuration;
  - QR Code TOTP compatible 2FAS, Google Authenticator, Microsoft Authenticator et Authy;
  - champ de confirmation du code a 6 chiffres;
  - affichage du QR Code deja configure;
  - desactivation securisee par mot de passe actuel.
- Ajout d'un rate limiting sur la confirmation du code depuis le profil et utilisation du throttle Fortify sur le challenge de connexion.
- Ajout des traductions FR/EN et du style responsive de la section 2FA.

Fichiers modifies :

- `config/fortify.php`
- `app/Models/User.php`
- `app/Providers/FortifyServiceProvider.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`
- `app/Http/Responses/FailedTwoFactorLoginResponse.php`
- `routes/web.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/auth/two-factor-challenge.blade.php`
- `resources/css/main.css`
- `lang/fr/profile.php`
- `lang/en/profile.php`
- `tests/Feature/ExampleTest.php`

Verification :

- `php -l` passe sur les fichiers PHP modifies.
- `php artisan route:list --name=two-factor` confirme les routes Fortify et Profil.
- `php artisan test --filter=profile` passe.
- `php artisan test --filter=two_factor` passe.
- `php artisan test` passe avec 59 tests et 353 assertions.

### 2026-04-29 - Correction des traductions francaises du profil 2FA

Prompt utilisateur :

```text
fais bien la traduction stp
```

Correction appliquee :

- Nettoyage complet du fichier `lang/fr/profile.php` en UTF-8 correct.
- Correction des libelles 2FA affiches dans la page Profil :
  - `Configurer l’authentification à deux facteurs`;
  - `Désactiver l’authentification à deux facteurs`;
  - badges, messages, erreurs et page de verification 2FA.
- Correction des accents deja casses dans les libelles existants du profil.

Fichier modifie :

- `lang/fr/profile.php`

Verification :

- `php -l lang/fr/profile.php` passe.
- `php artisan test --filter=profile` passe.

### 2026-04-29 - Prechargement du site et des permissions dans le modal utilisateur admin

Prompt utilisateur :

```text
QUAND je modifie un utilisateur qui est deja affecte a un site. il faut selectionner son site dans le formulaire et afficher les modules et les permissions qui lui sont attribuees
```

Correction appliquee :

- Conservation de l'ID du site deja affecte dans les attributs `data-user-site-id`, y compris pour un utilisateur admin gere par l'admin connecte.
- Mise a jour du script du modal utilisateur pour precharger le site selectionne en modification.
- Affichage automatique des modules et permissions existantes du site affecte.
- Pour un utilisateur avec le role admin, les permissions restent cochees et verrouillees, tout en affichant le site de reference deja affecte.

Fichier modifie :

- `resources/views/main/users.blade.php`

Verification :

- `php artisan test --filter=admin` passe.
- `php artisan test` passe avec 59 tests et 353 assertions.

### 2026-04-29 - Renforcement du prechargement du modal utilisateur

Prompt utilisateur :

```text
ça ne s'affiche toujours pas sur le formulaire
```

Correction appliquee :

- Ajout d'un prechargement robuste au moment de l'ouverture effective du modal `userModal`.
- Le script conserve le bouton ou la ligne qui a declenche l'edition, puis reapplique :
  - le site deja affecte;
  - les modules du site;
  - les permissions existantes.
- Le select du site est force avant le rendu des modules pour eviter les problemes d'ordre entre le script global et le script specifique aux permissions.
- Le comportement fonctionne aussi bien avec le clic sur l'icone modifier qu'avec le clic sur la ligne du tableau.

Fichier modifie :

- `resources/views/main/users.blade.php`

Verification :

- Tests cibles de gestion utilisateurs admin passes.
- `php artisan test` passe avec 59 tests et 353 assertions.

### 2026-04-29 - Correction definitive du JSON des permissions utilisateur

Prompt utilisateur :

```text
mais tu n'y arrive pas. ça ne fonctionne toujours pas
```

Diagnostic :

- Les attributs HTML `data-user-modules` et `data-user-module-permissions` etaient double-echappes.
- Le navigateur recevait des valeurs de type `&quot;` dans le dataset.
- `JSON.parse()` echouait avant le rendu des modules, ce qui laissait le select site vide et le panneau permissions sur le message "Selectionnez un site".

Correction appliquee :

- Remplacement du rendu `{{ e(json_encode(...)) }}` par des attributs simples contenant `@json(...)`.
- Ajout d'une fonction `parseDatasetJson()` tolerante pour decoder les anciennes valeurs HTML encodees si necessaire.
- Ajout d'assertions de test pour garantir que les attributs contiennent un JSON exploitable par le navigateur.
- Vidage du cache des vues avec `php artisan view:clear`.

Fichiers modifies :

- `resources/views/main/users.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- Verification du HTML rendu localement : `data-user-site-id`, `data-user-modules` et `data-user-module-permissions` sortent avec des valeurs propres.
- `php artisan test --filter=admin_can_manage_subscription_users` passe.
- `php artisan test --filter=managed_admin` passe.
- `php artisan test` passe avec 59 tests et 357 assertions.

### 2026-04-29 - Correction de l'erreur JS dans le formulaire de modification utilisateur admin

Prompt utilisateur :

```text
je parle quand l'admin modifie les utilisateurs.
ça ne marche toujours pas
```

Diagnostic :

- Les donnees HTML du bouton de modification etaient enfin correctes.
- Mais la fonction JavaScript `parseDatasetJson()` avait ete ajoutee dans le bloc du modal historique de connexion.
- Le bloc du modal utilisateur appelait cette fonction sans l'avoir dans sa portee, ce qui provoquait une erreur JavaScript au clic sur "modifier".
- L'erreur stoppait le remplissage du site, des modules et des permissions dans le formulaire admin.

Correction appliquee :

- Ajout de `parseDatasetJson()` dans le bloc JavaScript propre au modal utilisateur.
- Vidage du cache des vues avec `php artisan view:clear`.

Fichier modifie :

- `resources/views/main/users.blade.php`

Verification :

- `php artisan test --filter=admin_can_manage_subscription_users` passe.
- `php artisan test --filter=managed_admin` passe.
- `php artisan test` passe avec 59 tests et 357 assertions.

### 2026-04-29 - Synchronisation explicite du modal utilisateur et des permissions

Prompt utilisateur :

```text
ça ne fonctionne toujours pas
```

Correction appliquee :

- Ajout d'un evenement JavaScript `user-form-mode-applied` dans le script global `resources/js/main.js` des que le formulaire utilisateur est rempli en mode creation ou edition.
- Le script specifique de `resources/views/main/users.blade.php` ecoute cet evenement et applique immediatement :
  - le site affecte;
  - les modules;
  - les permissions existantes.
- Le select du site n'est plus desactive pour les admins afin que le site de reference reste visible dans le formulaire.
- Vidage du cache des vues avec `php artisan view:clear`.

Fichiers modifies :

- `resources/js/main.js`
- `resources/views/main/users.blade.php`
- `docs/prompts/project-history.md`

Verification :

- Tests cibles de gestion utilisateurs admin passes.
- `php artisan test` passe avec 59 tests et 353 assertions.

### 2026-04-29 - Page profil commune a tous les utilisateurs

Prompt utilisateur :

```text
fais moi une page de profil pour tous les utilisateurs.
Modification informations, Changement de mail, mot de passe etc...
```

Implementation :

- Ajout d'une page profil commune accessible par tous les utilisateurs authentifies via `/profile`.
- Ajout des routes :
  - `profile.edit`;
  - `profile.information.update`;
  - `profile.email.update`;
  - `profile.password.update`.
- Creation du `ProfileController` avec trois actions separees :
  - mise a jour des informations personnelles;
  - changement d'adresse e-mail avec confirmation du mot de passe actuel;
  - changement du mot de passe avec confirmation du mot de passe actuel et regles de securite fortes.
- Creation de la vue `resources/views/profile/edit.blade.php`.
- Ajout des traductions dediees dans `lang/fr/profile.php` et `lang/en/profile.php`.
- Ajout du style de la page profil dans `resources/css/main.css`.
- Remplacement de tous les liens `Profil` des dropdowns utilisateur pour pointer vers la nouvelle page.
- Conservation du comportement standard des formulaires :
  - etat de chargement au submit;
  - boutons temporairement desactives;
  - validations affichees sous les champs.

Fichiers modifies :

- `app/Http/Controllers/ProfileController.php`
- `routes/web.php`
- `resources/views/profile/edit.blade.php`
- `resources/css/main.css`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/views/admin/subscriptions.blade.php`
- `resources/views/admin/companies.blade.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/views/main/main.blade.php`
- `resources/views/main/users.blade.php`
- `resources/views/main/company-form.blade.php`
- `resources/views/main/company-sites.blade.php`
- `resources/views/main/pending-access.blade.php`
- `lang/fr/profile.php`
- `lang/en/profile.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=profile` passe.
- `php artisan test --filter="email|password"` passe.
- `php artisan test` passe : 53 tests, 304 assertions.

### 2026-04-29 - Photo de profil avec Cropper.js

Prompt utilisateur :

```text
oui je parlerle bien de cropper.js
J'ai besoin que tu intègre la possibilité de changer la photo de profil mais en utilisant un modal et cropper pour choissir la dimension du photo à uploader
```

Implementation :

- Ajout de la colonne `profile_photo_path` dans la table `users`.
- Ajout de la route `profile.photo.update` pour mettre a jour la photo de profil.
- Ajout de l'action `ProfileController::updatePhoto`.
- Stockage des photos recadrees dans le disque public, sous `profile-photos/`.
- Suppression de l'ancienne photo lors du remplacement.
- Ajout d'un panneau "Photo de profil" sur la page `/profile`.
- Integration de Cropper.js dans un modal dedie :
  - recadrage carre;
  - zoom avant/arriere;
  - rotation gauche/droite;
  - generation d'une image finale 512x512 avant envoi.
- Affichage de la photo de profil dans l'avatar du dropdown de la page profil.
- Ajout du script isole `public/js/profile.js`.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers modifies :

- `app/Http/Controllers/ProfileController.php`
- `app/Models/User.php`
- `database/migrations/2026_04_29_000001_add_profile_photo_path_to_users_table.php`
- `database/exports/erp_database.sql`
- `resources/views/profile/edit.blade.php`
- `resources/css/main.css`
- `public/js/profile.js`
- `lang/fr/profile.php`
- `lang/en/profile.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=profile` passe.
- `php artisan test` passe : 54 tests, 311 assertions.
- `php artisan migrate` execute avec succes.

### 2026-04-29 - Correction navigation page profil superadmin

Prompt utilisateur :

```text
la barre de navigation ne fonctionne pas sur la page de profil
```

Correction :

- La page profil utilisait une barre de navigation simple, adaptee aux espaces main, meme pour le superadmin.
- Pour un superadmin, la page profil utilise maintenant la meme structure que les pages admin :
  - `dashboard-shell`;
  - sidebar superadmin;
  - bouton de reduction de sidebar;
  - liens Tableau de bord, Abonnements, Utilisateurs et Entreprises;
  - topbar admin.
- Les utilisateurs simples et admins gardent la navigation simple de l'espace main.

Fichiers modifies :

- `resources/views/profile/edit.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=profile` passe.
- `php artisan test` passe : 56 tests, 322 assertions.

### 2026-04-29 - Correction interactions header page profil

Prompt utilisateur :

```text
tu n'as pas compris je n'arrive pas à traduire, ni changer le mode ni me deconnecter la barre de navigation ne fonctionne pas sur la page profil
```

Diagnostic :

- La page profil chargeait le script commun avec `asset('js/main.js')`.
- Le projet n'a pas de fichier `public/js/main.js`.
- Les autres pages injectent directement `resources/js/main.js`.
- A cause de ce script absent, les interactions du header ne demarraient pas sur `/profile` :
  - changement de theme;
  - dropdown de langue;
  - dropdown profil;
  - comportement standard des formulaires.

Correction :

- Remplacement de l'appel `asset('js/main.js')` par l'injection inline de `resources/js/main.js`, comme sur les autres pages.
- Ajout d'une assertion de test pour garantir que le script commun est bien present sur la page profil.
- Ajout d'une assertion pour eviter le retour a `/js/main.js`.

Fichiers modifies :

- `resources/views/profile/edit.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=profile` passe.
- `php artisan test` passe : 56 tests, 324 assertions.

### 2026-04-29 - Generalisation des photos de profil

Prompt utilisateur :

```text
la photo se charge seulement sur la page profil mais pas sur toutes les pages.
Je souhaite que la photo soit chargé partout et pour tous les utilisateurs
```

Correction :

- Creation du partial `resources/views/partials/user-avatar.blade.php`.
- Le partial affiche automatiquement :
  - la photo de profil si `profile_photo_url` existe;
  - l'initiale de l'utilisateur en fallback.
- Remplacement des avatars du dropdown dans les pages main et admin.
- Remplacement des avatars dans les listes utilisateurs qui affichent des comptes.
- Ajout du support image dans les avatars du CSS admin.
- Ajout d'un test pour verifier que la photo s'affiche :
  - dans la navigation main;
  - dans la liste des utilisateurs admin;
  - dans la navigation superadmin.

Fichiers modifies :

- `resources/views/partials/user-avatar.blade.php`
- `resources/views/main/main.blade.php`
- `resources/views/main/users.blade.php`
- `resources/views/main/company-form.blade.php`
- `resources/views/main/company-sites.blade.php`
- `resources/views/main/pending-access.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/views/admin/subscriptions.blade.php`
- `resources/views/admin/companies.blade.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/css/admin/dashboard.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=profile_photo` passe.
- `php artisan test` passe : 57 tests, 331 assertions.

### 2026-04-28 - Gestion utilisateurs admin : sites et responsables

Prompt utilisateur :

```text
enlève téléphone mets le nom du site affecté à l'utilisateur.
Un admin peut etre resposable de toutes les sites des entreprises de l'abonnement car il a toutes les accès mais les utilisateurs normaux ne peuvent qu'etre affecté sur site et ne peuvent qu'etre responsable sur un seul site
```

Correction appliquee :

- Suppression de la colonne telephone dans le tableau de gestion des utilisateurs cote admin.
- Conservation de la colonne site avec le nom du site affecte pour les utilisateurs simples.
- Affichage de `Tous les sites` et `Tous les acces` pour les comptes admin, car ils ont automatiquement acces a tous les sites et modules de l'abonnement.
- Lors de la creation/modification d'un utilisateur admin, le site n'est plus obligatoire : l'admin est synchronise automatiquement sur tous les sites de l'abonnement avec toutes les permissions.
- Les utilisateurs simples restent limites a un seul site d'affectation.
- Ajout d'une validation serveur : un utilisateur simple ne peut etre responsable que d'un seul site, tandis qu'un admin peut etre responsable de plusieurs sites.
- Ajout de la relation `responsibleSites` sur le modele `User`.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Models/User.php`
- `resources/views/main/users.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="managed_admin|responsible|managed_users"` passe.
- `php artisan test --filter="admin_can_manage_subscription_users_with_site_permissions|user_management"` passe.
- `php artisan test` passe avec 47 tests et 276 assertions.

### 2026-04-28 - Colonne role dans la gestion utilisateurs admin

Prompt utilisateur :

```text
ajoute également la colonne role avec ce style
```

Correction appliquee :

- Ajout de la colonne `Role` dans le tableau `/main/users`.
- Reutilisation du style standard des badges de role deja applique cote superadmin :
  - `role-admin` pour Admin;
  - `role-user` pour Utilisateur.
- La colonne est triable et conserve le meme formatage que les autres tableaux.
- Ajustement des colspans des lignes vides et de recherche.

Fichiers modifies :

- `resources/views/main/users.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="admin_can_manage_subscription_users_with_site_permissions|user_management"` passe.
- `php artisan test` passe avec 47 tests et 276 assertions.

### 2026-04-28 - Modal historique de connexion utilisateurs

Prompt utilisateur :

```text
enleve le tiret sur l'action de l'admin.
ajoute le button historique à coté de modifier et supprimer qui va ouvrir un modal permettant de voir l'historique de connexion des utilisateurs comme dans la deuxième image
```

Correction appliquee :

- Suppression du tiret dans la colonne actions pour l'admin connecte.
- Ajout d'un bouton `Historique` avec l'icone `bi-clock-history` sur chaque ligne utilisateur.
- Pour l'admin connecte, seule l'action historique est disponible afin de conserver la protection contre l'auto-modification.
- Ajout d'un modal d'historique de connexion avec tableau pagine :
  - numero;
  - device;
  - IP;
  - date.
- Ajout d'un endpoint JSON pagine : `/main/users/{account}/login-history`.
- Ajout d'une table persistante `user_login_histories`.
- Enregistrement automatique de chaque connexion reussie via l'evenement Laravel `Login`.
- Detection simple du navigateur et de la plateforme pour afficher un libelle du type `Edge on Windows`.
- Mise a jour de l'export `database/exports/erp_database.sql`.

Fichiers modifies :

- `app/Models/UserLoginHistory.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/MainController.php`
- `database/migrations/2026_04_28_000005_create_user_login_histories_table.php`
- `database/exports/erp_database.sql`
- `routes/web.php`
- `resources/views/main/users.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` execute.
- `php artisan test` passe avec 48 tests et 283 assertions.

### 2026-04-28 - Historique de connexion cote superadmin

Prompt utilisateur :

```text
pareil coté utilisateurs super admin ajoute aussi l'historique de connexion
```

Correction appliquee :

- Ajout du bouton `Historique` dans le tableau global des utilisateurs cote superadmin.
- Le bouton est disponible aussi sur la ligne du superadmin, meme si les actions modifier/supprimer restent bloquees.
- Ajout d'un modal d'historique de connexion identique au cote admin :
  - numero;
  - device;
  - IP;
  - date;
  - pagination par 5 lignes.
- Ajout d'un endpoint JSON protege par le middleware superadmin : `/admin/users/{account}/login-history`.
- Reutilisation de la table persistante `user_login_histories`.
- Ajout du style du modal et du bouton historique dans la feuille superadmin.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `routes/web.php`
- `resources/views/admin/users.blade.php`
- `resources/css/admin/dashboard.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="login_history|superadmin_can_open_users_page"` passe.
- `php artisan test` passe avec 49 tests et 290 assertions.

### 2026-04-28 - Standard tableaux dans les modals

Prompt utilisateur :

```text
ne change pas le style de mes tableaux sur tous les modals fais toujours le meme style comme tu l'avais fais ce modal joint ajuste juste un truc, encercle mes tableaux border, suelement les tableaux qui s'affichent sur les modales et n'oublie jamais le tri, la recherche et la pagination
```

Correction appliquee :

- Conservation du style existant des tableaux de modals, base sur le modal `Utilisateurs affectes`.
- Ajout d'une bordure et d'un rayon autour des tableaux affiches dans les modals uniquement.
- Suppression du style a bandes grises du tableau d'historique de connexion afin de revenir au rendu standard :
  - header clair;
  - lignes separees;
  - table encadree;
  - typographie coherente avec les autres modals.
- Ajout de la recherche dans les modals d'historique de connexion.
- Ajout du tri dans les modals d'historique de connexion.
- Conservation de la pagination par 5 lignes.
- Les endpoints d'historique acceptent maintenant `search`, `sort`, `direction` et `page`.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Http/Controllers/AdminController.php`
- `resources/views/main/users.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/css/main.css`
- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="login_history"` passe.
- `php artisan test` passe avec 49 tests et 290 assertions.

### 2026-04-28 - Ajustement bordure et etat vide des tableaux de modals

Prompt utilisateur :

```text
le border seulement syr le tableau. la pagination ne s'afficche que si les element depassent 5
et si il n'ya aucun element à affiche le tableau mais indique qu'il y a aucun element proprement
```

Correction appliquee :

- La bordure arrondie encadre maintenant uniquement le tableau, pas la barre de recherche ni le pied de pagination.
- La pagination des historiques de connexion est masquee quand le total est inferieur ou egal a 5 elements.
- En absence d'historique, le tableau reste visible avec une ligne vide propre indiquant qu'aucune connexion n'est enregistree.
- Le compteur de lignes reste affiche dans l'entete du modal.

Fichiers modifies :

- `resources/views/main/users.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/css/main.css`
- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="login_history"` passe.
- `php artisan test` passe avec 49 tests et 290 assertions.

### 2026-04-28 - Largeur modal site a 700px

Prompt utilisateur :

```text
reduit legerement la taille du modal mets 700px
```

Correction appliquee :

- Reduction de la largeur maximale des modals de site de `980px` a `700px`.
- Conservation de la marge responsive `calc(100% - 2rem)` pour les petits ecrans.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-05-06 - Persistance adresse tiers entreprise

Prompt utilisateur :

```text
j'ai modifié pour un client, fournisseur et prospectsles adresses mais elles ne sont pas enregistré
```

Correction appliquee :

- Les payloads backend n'effacent plus l'adresse lorsque le tiers est de type entreprise.
- L'adresse est maintenant conservee pour les clients, fournisseurs et prospects, quel que soit le type.
- La conversion d'un prospect entreprise vers client conserve aussi l'adresse.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`

Verification :

- `php artisan test --filter=clients` passe.
- `php artisan test --filter=suppliers` passe.
- `php artisan test --filter=prospects` passe.

### 2026-05-06 - Validite obligatoire des proformas

Prompt utilisateur :

```text
lors de la création de la facture proforma, change date d'expiration en validité de l'offre et champ doit être obligatoire, par défaut mettre la date courante.
pour les proforma déjà enregistré dont la date d'expiration est vide mettre la où la proforma a été enregistré
```

Correction appliquee :

- Le libelle proforma `Date d'expiration` est remplace par `Validite de l'offre`.
- Le champ `expiration_date` est maintenant obligatoire cote validation.
- La date courante est preselectionnee par defaut sur la creation de proforma.
- Une migration remplit les anciennes proformas sans validite avec leur date de creation.
- Le PDF proforma et la liste utilisent le nouveau libelle.
- L'export SQL a ete mis a jour avec la migration et le champ `expiration_date` non nul.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `lang/fr/validation.php`
- `lang/en/validation.php`
- `database/migrations/2026_05_06_000001_fill_proforma_offer_validity_dates.php`
- `database/exports/erp_database.sql`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` execute.
- `php artisan test --filter=proforma` passe.

### 2026-05-06 - Retours ligne notes conditions PDF

Prompt utilisateur :

```text
par rapport à note et condition, je souhaite que lorsqu'il y a un retour à la ligne que cela soit également applique sur l'aperçu de la facture
```

Correction appliquee :

- Le rendu PDF des conditions/notes de proforma respecte maintenant les retours a la ligne saisis dans le formulaire.
- Le texte est conserve tel quel et affiche avec `white-space: pre-line` dans l'apercu PDF.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Position modalite paiement proforma

Prompt utilisateur :

```text
lors de la création et la modification de proforma mettre modalité de paiement au dessus de notes et conditions
```

Correction appliquee :

- Le champ `Modalite de paiement` a ete deplace dans la page de creation proforma.
- Le meme deplacement a ete applique dans le modal de modification proforma.
- Le champ est maintenant affiche juste au-dessus de `Notes` et `Conditions`.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - TVA proforma par defaut selon pays entreprise

Prompt utilisateur :

```text
la tva par défaut qui doit etre appliqué est la tva du pays au qual appartient l'entreprise
```

Correction appliquee :

- Le taux TVA par defaut des proformas est calcule depuis le pays de l'entreprise.
- Le formulaire de creation proforma utilise ce taux au lieu de `0`.
- Le modal de modification utilise aussi ce taux comme valeur par defaut lors d'une nouvelle proforma.
- Les proformas existantes conservent leur propre taux lors de la modification.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test` passe avec 76 tests et 756 assertions.

### 2026-05-01 - Articles et services rattaches aux categories

Prompt utilisateur :

```text
pareil avec catégorie article/service la possibilité d'afficher tous les article/service appartenant à une catégorie
```

Correction appliquee :

- Ajout du bouton d'affichage des articles rattaches sur les categories d'articles.
- Ajout du bouton d'affichage des services rattaches sur les categories de services.
- Reutilisation du meme modal standard que les sous-categories : recherche, tri, pagination et message vide centre.
- Les titres et messages vides s'adaptent maintenant selon categorie ou sous-categorie.
- Eager loading des relations `items.unit` et `services.unit` pour eviter les chargements multiples.
- Ajout des traductions FR/EN pour les categories.
- Ajout d'assertions pour verifier les payloads des categories stock et services.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l resources/views/main/modules/accounting-stock-resource.blade.php` passe.
- `php -l resources/views/main/modules/accounting-service-resource.blade.php` passe.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 39 assertions.
- `php artisan test --filter=accounting_service_pages` passe avec 1 test et 35 assertions.
- `php artisan view:clear` execute.
- `php artisan test` passe avec 76 tests et 764 assertions.
- `php artisan view:clear` execute.
- `php artisan test` passe avec 76 tests et 764 assertions.

### 2026-05-01 - Sous-categorie dans les modals de categorie

Prompt utilisateur :

```text
maintenant dans catégorie enleve prix de vente mets le sous catégorie correspondant
```

Correction appliquee :

- Dans le modal des articles rattaches a une categorie, remplacement de la colonne `Prix de vente` par `Sous-categorie`.
- Dans le modal des services rattaches a une categorie, remplacement de la colonne `Prix de vente` par `Sous-categorie`.
- Les modals des sous-categories gardent le prix de vente.
- Le payload des categories ne transporte plus le prix de vente, mais la sous-categorie correspondante.
- Ajout du mode `categories` dans les scripts stock/services pour rendre la bonne derniere colonne selon le contexte.
- Eager loading des sous-categories rattachees aux articles/services des categories.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `resources/js/main/accounting-service-resource.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources/js/main/accounting-stock-resource.js` passe.
- `node --check resources/js/main/accounting-service-resource.js` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l resources/views/main/modules/accounting-stock-resource.blade.php` passe.
- `php -l resources/views/main/modules/accounting-service-resource.blade.php` passe.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 39 assertions.
- `php artisan test --filter=accounting_service_pages` passe avec 1 test et 35 assertions.

### 2026-04-30 - Alignement du bouton supprimer sur la ligne des inputs client

Prompt utilisateur :

```text
à coté de l'input meme migne
```

Correction appliquee :

- Le bouton de suppression du contact client est maintenant une colonne de la rangée Bootstrap.
- Il est aligné sur la même ligne que les inputs `Nom complet` et `Fonction ou grade`.
- Le CSS précédent en grille a été retiré pour éviter un positionnement séparé.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-04-30 - Standard placeholders sur les formulaires

Prompt utilisateur :

```text
toujours mettre des placeholder sur les champs de formulaire
```

Decision enregistree :

- Tous les nouveaux champs de formulaire doivent avoir un placeholder explicite.
- Les placeholders doivent rester cohérents avec le libelle du champ et la langue active.

Correction appliquee :

- Ajout des placeholders sur tous les champs du formulaire client.
- Le placeholder du champ nom bascule selon le type de client : particulier ou entreprise.
- Les champs de contact ajoutes dynamiquement recoivent aussi leurs placeholders.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/js/main/accounting-clients.js`
- `docs/prompts/project-history.md`

### 2026-04-30 - Erreurs sous les champs du formulaire client

Prompt utilisateur :

```text
oui mais applique ça sur le formulaire actuel du client merci
```

Correction appliquee :

- Le formulaire client n'affiche plus les erreurs de validation dans le toast global.
- En cas d'erreur, le modal client reste ouvert.
- Les erreurs de validation sont affichees directement sous les champs concernes.
- Les champs obligatoires du formulaire client disposent d'un `invalid-feedback` utilisable par la validation front.
- Les attributs de validation client/contact ont ete ajoutes aux fichiers de traduction FR/EN pour obtenir des messages plus propres.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `lang/fr/validation.php`
- `lang/en/validation.php`
- `docs/prompts/project-history.md`

### 2026-04-30 - Reference automatique des clients

Prompt utilisateur :

```text
Ajoute le numéro de référence pour chaque client, le système dois pouvoir générer lui meme une référence pour chaque client
```

Correction appliquee :

- Ajout de la colonne `reference` sur `accounting_clients`.
- Generation automatique des references client au format `CLT-000001`, `CLT-000002`, etc.
- Migration de rattrapage pour completer les references des clients deja existants.
- Affichage de la reference dans le tableau clients avec tri.
- Ajout des traductions FR/EN pour le libelle `Reference`.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers modifies :

- `database/migrations/2026_04_30_000002_add_reference_to_accounting_clients_table.php`
- `app/Models/AccountingClient.php`
- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `database/exports/erp_database.sql`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` execute et la migration de reference client est appliquee.
- `php artisan test --filter=accounting_clients` passe avec 19 assertions.
- `php artisan test` passe avec 65 tests et 479 assertions.

### 2026-04-30 - Positionnement lateral du bouton supprimer contact client

Prompt utilisateur :

```text
l'icone dois se positionner à coté
```

Correction appliquee :

- Le bouton de suppression d'un contact client n'est plus positionne en absolu.
- La carte de contact utilise maintenant une grille avec les champs a gauche et le bouton de suppression a droite, aligne sur la ligne des champs.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=site_form_validation` passe.

### 2026-04-28 - Gestion des utilisateurs cote admin avec sites et permissions

Prompt utilisateur :

```text
Gestion des utilisateurs (Admin) – Navigation, affectation et permissions

Ajouter "Gestion des utilisateurs" dans le dropdown, entre Profil et Deconnexion.
L'admin doit pouvoir ajouter, modifier, supprimer des utilisateurs.
L'admin affecte un site, pas un abonnement.
Apres selection du site, afficher les modules disponibles et permettre de cocher les permissions Ajouter, Modifier, Supprimer par utilisateur.
```

Correction appliquee :

- Ajout des routes `main.users`, `main.users.store`, `main.users.update`, `main.users.destroy`.
- Ajout du lien `Gestion des utilisateurs` dans les dropdowns admin du main, des entreprises et des sites.
- Creation de la page `main/users.blade.php` avec tableau recherche/tri/pagination, bouton `Nouvel utilisateur`, modal creation/modification et suppression avec le style existant.
- L'admin gere uniquement les utilisateurs de son abonnement.
- Le formulaire admin affecte un utilisateur a un site, pas a un abonnement.
- Les modules du site selectionne sont affiches dynamiquement dans le modal.
- Ajout de permissions par module :
  - acces au module;
  - ajouter;
  - modifier;
  - supprimer.
- Extension du pivot `company_site_user` avec :
  - `module_permissions` JSON;
  - `can_create`;
  - `can_update`;
  - `can_delete`.
- Synchronisation automatique de l'affectation site et de l'affectation entreprise correspondante.
- Mise a jour de `database/exports/erp_database.sql` avec la nouvelle migration et les nouvelles colonnes.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `app/Models/User.php`
- `app/Models/CompanySite.php`
- `database/migrations/2026_04_28_000004_add_permissions_to_company_site_user.php`
- `database/exports/erp_database.sql`
- `resources/views/main/users.blade.php`
- `resources/views/main/main.blade.php`
- `resources/views/main/company-sites.blade.php`
- `resources/views/main/company-form.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `lang/fr/validation.php`
- `lang/en/validation.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test` passe : 43 tests, 249 assertions.

### 2026-04-28 - Correction colonne module_permissions manquante

Prompt utilisateur :

```text
j'ai cette erreur
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'company_site_user.module_permissions'
```

Diagnostic :

- La migration `2026_04_28_000004_add_permissions_to_company_site_user` etait encore en attente sur la base locale.
- Le modele Eloquent lisait deja `company_site_user.module_permissions`, ce qui provoquait l'erreur SQL.

Correction appliquee :

- Execution de `php artisan migrate` pour ajouter les colonnes du pivot `company_site_user`.
- Suppression du chargement eager-load inutile de `users` dans la page des sites d'entreprise, car cette page n'utilise pas ces donnees.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate:status` confirme que la migration est `Ran`.
- `php artisan test --filter="company_sites|manage_subscription_users"` passe.

### 2026-04-28 - Permissions automatiques pour les utilisateurs admin

Prompt utilisateur :

```text
un utilisateur avec le role admin a automatiquement toutes les permissions dans les modules disponibles du site.
Donc lorsque je selectionne le role admin toutes les permissions s'actives et se coches avec pas de possibilités de modifications et quand par exemple je remets en user la possibilité de cocher les permissions s'actives
```

Correction appliquee :

- Dans le modal de gestion des utilisateurs cote admin :
  - selection du role `admin` coche automatiquement tous les modules du site;
  - toutes les permissions `Ajouter`, `Modifier`, `Supprimer` sont cochees;
  - les cases sont desactivees pour empecher la modification manuelle;
  - le retour au role `user` reactive les cases pour une gestion flexible.
- Cote serveur, les permissions admin sont normalisees avant validation :
  - tous les modules du site sont selectionnes;
  - toutes les permissions par module sont forcees a `true`.
- Ajout d'un test garantissant qu'un utilisateur cree avec le role `admin` recoit toutes les permissions du site, meme sans envoyer les cases depuis le formulaire.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/users.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="manage_subscription_users|managed_admin_user"` passe.

### 2026-04-28 - Protection edition compte admin connecte

Prompt utilisateur :

```text
l'admin connecté dans la session encous ne doit pas pouvoir modifier ses propres informations ici.
Le mot de passe et la confirmation du mot de passe ne doivent pas etre sur une meme ligne
```

Correction appliquee :

- Suppression des actions de modification et suppression sur la ligne de l'admin connecte.
- Suppression des attributs `data-user-*` sur la ligne de l'admin connecte afin qu'elle ne puisse pas ouvrir le modal par clic sur la ligne.
- Protection serveur : une requete directe `PUT /main/users/{admin-connecte}` redirige sans modifier le compte.
- Le champ mot de passe et le champ confirmation du mot de passe occupent chacun une ligne complete dans le modal.
- Ajout d'un test de non-regression pour empecher la modification du compte connecte depuis cette page.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/users.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="manage_subscription_users|cannot_update_self|managed_admin_user"` passe.
- `php artisan test` passe : 45 tests, 263 assertions.

### 2026-04-28 - Succession des champs mot de passe utilisateur

Prompt utilisateur :

```text
il y a un probleme ici par rapport à la succession.
analyse les autres formulaires d'utilisateurs que tu travaillé
```

Correction appliquee :

- Alignement du modal de gestion utilisateurs admin sur le formulaire superadmin :
  - champ mot de passe;
  - regles du mot de passe;
  - champ confirmation;
  - feedback de correspondance.
- Mise a jour du JavaScript commun pour changer aussi le label de confirmation en mode edition.
- Ajout de `autocomplete="new-password"` sur les champs mot de passe et confirmation pour eviter l'auto-remplissage navigateur.
- Ajout de `data-password-optional` au rendu initial pour respecter le mode edition meme apres retour de validation serveur.
- Application du meme durcissement autocomplete/data optionnel au formulaire superadmin des utilisateurs.

Fichiers modifies :

- `resources/js/main.js`
- `resources/views/main/users.blade.php`
- `resources/views/admin/users.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="manage_subscription_users|superadmin_can_open_users_page"` passe.
- `php artisan test` passe : 45 tests, 267 assertions.

### 2026-04-28 - Ordre liste utilisateurs admin

Prompt utilisateur :

```text
le premier utilisateur doit toujours etre l'admin connecté dans la session en cours et le deuxième, c'est l'utilisateurs ajouté en dernier
```

Correction appliquee :

- Modification du tri de `/main/users` :
  - l'admin connecte est toujours affiche en premiere ligne;
  - les autres utilisateurs sont tries par `id` descendant pour afficher l'utilisateur ajoute en dernier juste apres.
- Utilisation de `id DESC` plutot que `created_at DESC`, car plusieurs utilisateurs peuvent etre crees dans la meme seconde et rendre l'ordre instable.
- Ajout d'un test de non-regression sur l'ordre d'affichage.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="user_management_lists|manage_subscription_users|cannot_update_self"` passe.
- `php artisan test` passe : 46 tests, 270 assertions.
- `php artisan test` passe : 44 tests, 259 assertions.

### 2026-04-28 - Renforcement UI permissions admin

Prompt utilisateur :

```text
ça ne fonctionne pas
```

Correction appliquee :

- Renforcement du JavaScript du modal `Gestion des utilisateurs`.
- Suppression de la dependance a `CSS.escape` dans la lecture des permissions existantes.
- Ajout d'une fonction dediee qui force l'etat admin apres :
  - changement de role;
  - changement de site;
  - ouverture du modal en creation ou modification;
  - rendu initial.
- Pour le role `admin`, tous les modules et toutes les permissions sont coches et verrouilles.
- Pour le role `user`, les cases redeviennent modifiables.

Fichiers modifies :

- `resources/views/main/users.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="manage_subscription_users|managed_admin_user"` passe.

### 2026-04-28 - Validation du formulaire site dans le modal

Prompt utilisateur :

```text
lorsque je valide le formulaire du site sans remplir les champs obligatoire, le modale se ferme et m'ouvre un toatr; ce n'est pas comme ça que doit se passer.
Lorsque je ne valide pas les champs obligatoires, le modal doit rester actif et m'afficher les erreurs en bas des champs à remplir
```

Correction appliquee :

- Ajout d'un champ cache `_site_modal_id` dans le formulaire de site pour identifier le modal a rouvrir apres validation serveur.
- Reouverture automatique du modal concerne lorsque Laravel retourne des erreurs de validation.
- Affichage des erreurs directement sous les champs obligatoires du modal.
- Suppression du toast global pour les erreurs provenant du modal de site.
- Isolation des anciennes valeurs et des erreurs au modal concerne afin d'eviter de polluer les autres modals d'edition.

Fichiers modifies :

- `resources/views/main/company-sites.blade.php`
- `resources/views/main/partials/site-form-modal.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=site_form_validation` passe.
- `php artisan test --filter=company_sites` passe.

### 2026-04-28 - Validation conditionnelle des comptes entreprise

Prompt utilisateur :

```text
ici si le champ est saisie, le numéro de compte devient obligatoire, si le numéro de compte est saisie la devise devient obligatoire
```

Correction appliquee :

- Ajustement de la validation des comptes bancaires entreprise sur tous les formulaires de creation/modification :
  - si la banque est renseignee, le numero de compte devient obligatoire;
  - si le numero de compte est renseigne, la devise devient obligatoire;
  - les lignes de compte totalement vides restent autorisees et ignorees.
- Application de la regle :
  - cote superadmin dans `AdminController`;
  - cote admin abonnement dans `MainController`.
- Affichage des erreurs serveur au niveau de chaque champ de ligne de compte :
  - banque;
  - numero de compte;
  - devise.
- Le placeholder du numero de compte affiche maintenant `*` pour signaler la condition obligatoire lorsque la banque est saisie.
- Les tests couvrent les deux enchainements de validation cote superadmin et cote `/main`.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/views/main/company-form.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=company_creation_requires_account_number_after_bank_and_currency_after_number` passe.
- `php artisan test` passe avec 35 tests et 172 assertions.

### 2026-04-28 - Traduction propre des erreurs de comptes entreprise

Prompt utilisateur :

```text
n'oublie pas la traduction des ereurs et fais ça proprement
```

Correction appliquee :

- Ajout des fichiers de traduction Laravel pour les erreurs de validation :
  - `lang/fr/validation.php`;
  - `lang/en/validation.php`.
- Ajout de messages specifiques pour les comptes bancaires entreprise :
  - `Le numero de compte est obligatoire lorsque la banque est renseignee.`;
  - `La devise est obligatoire lorsque le numero de compte est renseigne.`
- Ajout des equivalents anglais pour conserver le support bilingue.
- Ajout de noms lisibles pour les champs dynamiques `accounts.*` afin d'eviter les messages techniques du type `accounts.0.account_number`.
- Mise a jour des tests pour verifier le texte exact des erreurs cote superadmin et cote `/main`.

Fichiers modifies :

- `lang/fr/validation.php`
- `lang/en/validation.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=company_creation_requires_account_number_after_bank_and_currency_after_number` passe.
- `php artisan test` passe avec 35 tests et 172 assertions.

### 2026-04-28 - Validation conditionnelle des numeros de telephone entreprise

Prompt utilisateur :

```text
applique la meme regle pour le numero de téléphone.
Pour le numéro de téléphone si le libellé est saisie le télé phone est obligatoire donc vis versa
```

Correction appliquee :

- Ajout d'une validation conditionnelle sur les numeros de telephone entreprise :
  - si le libelle est renseigne, le telephone devient obligatoire;
  - si le telephone est renseigne, le libelle devient obligatoire;
  - les lignes totalement vides restent autorisees.
- Application de la regle :
  - cote superadmin dans `AdminController`;
  - cote admin abonnement dans `MainController`.
- Correction du bug SQL `Column 'phone_number' cannot be null` en bloquant les lignes incompletes avant insertion.
- Ajout de messages traduits FR/EN propres :
  - `Le telephone est obligatoire lorsque le libelle est renseigne.`;
  - `Le libelle est obligatoire lorsque le telephone est renseigne.`
- Affichage des erreurs serveur sous chaque champ de telephone dans les formulaires superadmin et `/main`.
- Les placeholders telephone/libelle affichent maintenant `*` pour signaler le caractere conditionnellement obligatoire.
- Ajout de tests pour verifier les deux sens de la validation cote superadmin et cote `/main`.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/views/main/company-form.blade.php`
- `lang/fr/validation.php`
- `lang/en/validation.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php -l lang/fr/validation.php` passe.
- `php -l lang/en/validation.php` passe.
- `php artisan test --filter=complete_phone_rows` passe.
- `php artisan test` passe avec 37 tests et 188 assertions.

### 2026-04-28 - Pagination du tableau entreprises sur main

Prompt utilisateur :

```text
tu as oublié la pagination sur le tableau comme je faisais avec les autres tableaux
```

Correction appliquee :

- Ajout de la pagination Laravel sur le tableau `Mes entreprises` de la page `/main`.
- La requete des entreprises cote admin abonnement utilise maintenant `paginate(5)` comme les autres tableaux principaux.
- Le tableau affiche le compteur de lignes visibles sur le total global.
- Ajout du bloc de pagination standard :
  - `subscriptions-pagination`;
  - `pagination-shell`;
  - `Precedent`;
  - numeros de page;
  - `Suivant`.
- La numerotation des lignes tient maintenant compte de la page courante via `firstItem()`.
- Ajout d'un test pour verifier que le tableau `/main` expose bien la pagination lorsqu'il y a plus de 5 entreprises.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/main.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan test --filter=main_companies_table_is_paginated` passe.
- `php artisan test` passe avec 38 tests et 195 assertions.

### 2026-04-28 - Gestion des sites d'entreprise cote main

Prompt utilisateur :

```text
fais en sorte que les lignes de la colonne entreprise soit cliquable.
Lorsqu'on clique elles nous ramenes vers la liste des sites de l'entreprise...
```

Correction appliquee :

- Les noms d'entreprise de la colonne `Entreprise` sur `/main` sont maintenant cliquables.
- Ajout des routes cote `/main` pour les sites d'une entreprise :
  - liste des sites;
  - creation;
  - modification;
  - suppression.
- Ajout de la page `main.company-sites` avec :
  - retour aux entreprises;
  - titre `Entreprise : Sites`;
  - tableau datatable avec recherche, tri, compteur et pagination;
  - colonnes : nom, type, ville, responsable, modules, telephone, statut, actions.
- Ajout d'un modal de creation/modification de site avec :
  - nom;
  - type;
  - code;
  - responsable;
  - ville;
  - telephone;
  - adresse;
  - modules;
  - utilisateurs affectes;
  - email;
  - devise de gestion;
  - statut actif/inactif.
- Ajout du champ `status` sur les sites.
- Ajout de la table pivot `company_site_user` pour affecter les utilisateurs aux sites.
- Le responsable du site est automatiquement affecte au site.
- Le responsable et les utilisateurs affectables sont limites aux utilisateurs `admin` et `user` du meme abonnement.
- Ajout de la page `Acces en attente` pour un utilisateur sans site affecte.
- Application des limites par abonnement :
  - `standard` : 1 site, module comptabilite uniquement;
  - `pro` : 2 sites, 2 modules actifs maximum, comptabilite + ressources humaines;
  - `business` : sites et modules illimites.
- Ajustement du plan `pro` : limite entreprises a 2 au lieu de 3 pour les nouveaux abonnements et les abonnements existants.
- La devise de gestion du site est obligatoire et utilise le catalogue mondial des devises.
- Mise a jour de l'export SQL pour inclure `company_site_user`, `company_sites.status`, la migration de sites et la migration de correction Pro.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `app/Http/Controllers/AdminController.php`
- `app/Models/CompanySite.php`
- `app/Models/User.php`
- `database/migrations/2026_04_28_000002_add_status_and_user_assignments_to_company_sites.php`
- `database/migrations/2026_04_28_000003_update_pro_company_limit_to_two.php`
- `database/exports/erp_database.sql`
- `resources/views/main/main.blade.php`
- `resources/views/main/company-sites.blade.php`
- `resources/views/main/partials/site-form-modal.blade.php`
- `resources/views/main/pending-access.blade.php`
- `resources/css/main.css`
- `resources/views/admin/users.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `lang/fr/validation.php`
- `lang/en/validation.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate --force` execute.
- `php scripts/export-database.php` execute.
- `php artisan view:clear` execute.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l resources/views/main/company-sites.blade.php` passe.
- `php -l resources/views/main/partials/site-form-modal.blade.php` passe.
- `php artisan test` passe avec 41 tests et 217 assertions.

### 2026-04-28 - Nettoyage modal site et module comptabilite par defaut

Prompt utilisateur :

```text
le module comptabilité doit etre coché automatiquement.
Les affections des utilisateurs ne sera pas fais ici tu fais supprimer.
Ajoute un pading sur le modal pour une bonne affichage centralisée
```

Correction appliquee :

- Le module `Comptabilite (Facturation)` est coche automatiquement lors de la creation d'un site.
- Le bloc `Utilisateurs affectes` a ete retire du modal de creation/modification de site.
- L'affectation automatique du responsable au site reste conservee.
- Les autres utilisateurs ne sont plus affectes depuis ce formulaire.
- Ajout d'un padding dedie au modal site :
  - contenu plus centre;
  - largeur maximale controlee;
  - header, body et actions mieux espaces.
- Mise a jour des tests pour verifier :
  - le module comptabilite coche par defaut;
  - l'absence du bloc d'affectation utilisateurs;
  - l'affectation uniquement du responsable.
- Regeneration de `database/exports/erp_database.sql`.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/partials/site-form-modal.blade.php`
- `resources/css/main.css`
- `lang/fr/validation.php`
- `lang/en/validation.php`
- `tests/Feature/ExampleTest.php`
- `database/exports/erp_database.sql`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l resources/views/main/partials/site-form-modal.blade.php` passe.
- `php artisan test --filter=company_sites` passe.
- `php artisan test` passe avec 41 tests et 218 assertions.
- `php scripts/export-database.php` execute.

### 2026-04-28 - Menu profil selon role

Prompt utilisateur :

```text
Pourquoi tu as supprimé profile et gestion des utilisateurs ?
Pour l'admin remet Profil et gestion des utilisateurs...
```

Correction appliquee :

- Restauration du lien `Profil` dans les menus utilisateur des pages cote `/main`.
- Restauration du lien `Gestion des utilisateurs` pour les admins sur :
  - `/main`;
  - formulaire entreprise admin;
  - liste des sites entreprise.
- Sur la page `Acces en attente`, un utilisateur simple voit uniquement `Profil` et `Deconnexion`.
- Le superadmin conserve `Profil` et la gestion globale des utilisateurs.
- Correction des liens `Gestion des utilisateurs` sur certaines pages superadmin pour pointer vers `admin.users`.
- Ajout de tests pour verifier :
  - admin : `Profil` + `Gestion des utilisateurs`;
  - utilisateur simple sans site : `Profil` uniquement.

Fichiers modifies :

- `resources/views/main/main.blade.php`
- `resources/views/main/company-form.blade.php`
- `resources/views/main/company-sites.blade.php`
- `resources/views/main/pending-access.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/subscriptions.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/company-sites.blade.php` passe.
- `php -l resources/views/main/company-form.blade.php` passe.
- `php -l resources/views/main/pending-access.blade.php` passe.
- `php artisan test --filter="admin_can_open_main_page|admin_can_open_company_sites|pending_access"` passe.
- `php artisan test` passe avec 41 tests et 224 assertions.

### 2026-04-28 - Sidebar tablette pleine hauteur

Prompt utilisateur :

```text
dans l'affichage tablette je souhaite que la side barre prenne toutes la pagejusqu'en bas
```

Correction appliquee :

- Ajout de `align-items: stretch` sur `.dashboard-shell` pour permettre aux colonnes de s'etirer sur toute la hauteur du contenu.
- En affichage tablette (`max-width: 1180px`), la sidebar compacte n'est plus limitee a `height: 100vh`.
- La sidebar tablette utilise maintenant :
  - `position: static`;
  - `height: auto`;
  - `min-height: 100vh`;
  - `min-height: 100dvh`.
- Le fond bleu de la sidebar descend maintenant jusqu'en bas de la page lorsque le contenu principal est plus long que l'ecran.

Fichiers modifies :

- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=admin` passe.
- `php artisan test` passe avec 31 tests et 126 assertions.

### 2026-04-28 - Nombre d'utilisateurs dans la liste des entreprises

Prompt utilisateur :

```text
dans entreprise rajoute également la rubrique nombre dêutilisateur
```

Correction appliquee :

- Ajout du compteur reel `users_count` sur la requete de la liste des entreprises.
- La liste charge maintenant les compteurs :
  - `sites_count`;
  - `users_count`.
- Ajout d'une colonne triable `Utilisateurs` dans le tableau des entreprises.
- Affichage du nombre d'utilisateurs lies a chaque entreprise via la relation pivot `company_user`.
- Mise a jour du test de la liste des entreprises pour verifier le compteur utilisateurs en plus du compteur sites.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `resources/views/admin/companies.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=companies` passe.
- `php artisan test` passe avec 31 tests et 127 assertions.

### 2026-04-28 - Login sans panneau bleu sur tablette

Prompt utilisateur :

```text
dans login à partir de l'affichage tablette n'affiche pas la zone bleu juste le login et ramene le logo
```

Correction appliquee :

- Ajout d'un logo compact dans la zone formulaire du login avec `.login-logo-card`.
- A partir du breakpoint tablette `max-width: 991.98px` :
  - la zone bleue `.brand-side` est masquee;
  - seule la zone login reste visible;
  - le logo EXAD est affiche au-dessus du formulaire;
  - la zone formulaire conserve une hauteur minimale de page avec `100vh` et `100dvh`.
- Suppression des anciens ajustements mobiles qui concernaient encore le panneau bleu, car il est masque sur tablette et mobile.

Fichiers modifies :

- `resources/views/auth/login.blade.php`
- `resources/css/auth/login.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=login` passe.
- `php artisan test` passe avec 31 tests et 127 assertions.

### 2026-04-28 - Modal utilisateurs affectes depuis la liste des entreprises

Prompt utilisateur :

```text
Dans entreprise je souhaite que les elements de la colonnes soient cliquable et lorsqu'on clique dessus on affiches un tableau sur le modal de tous les utilisateurs affecté à l'entreprise
```

Correction appliquee :

- La requete de la liste des entreprises charge maintenant les utilisateurs affectes via la relation pivot `company_user`.
- Le compteur de la colonne `Utilisateurs` est devenu cliquable avec un bouton discret `.count-link`.
- Au clic sur le compteur, un modal Bootstrap propre a l'entreprise s'ouvre.
- Le modal affiche un tableau des utilisateurs affectes avec :
  - nom;
  - email;
  - role;
  - telephone;
  - grade.
- Ajout d'un etat vide lorsqu'aucun utilisateur n'est affecte a l'entreprise.
- Ajout des traductions FR/EN :
  - `company_users_title`;
  - `no_company_users`.
- Mise a jour du test entreprises pour verifier le modal et les donnees utilisateur.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `resources/views/admin/companies.blade.php`
- `resources/css/admin/dashboard.css`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=companies` passe.
- `php artisan test` passe avec 31 tests et 131 assertions.

### 2026-04-28 - Standard datatable pour le modal utilisateurs entreprise

Prompt utilisateur :

```text
j'avais dis que tout mes tableaux doivent etre des datatable avec recherche/ refère toi du tableau utilisateurs par exemple
```

Correction appliquee :

- Generalisation du JavaScript de datatable dans `resources/js/main.js`.
- Le comportement historique des tableaux principaux est conserve :
  - recherche;
  - compteur de lignes visibles;
  - tri par colonnes;
  - ligne vide lorsque la recherche ne retourne aucun resultat.
- Ajout d'un format reutilisable pour les nouveaux tableaux internes/modaux :
  - wrapper `data-datatable`;
  - input `data-datatable-search`;
  - compteur `data-datatable-visible-count`;
  - table `data-datatable-table`.
- Le tableau du modal `Utilisateurs affectes` respecte maintenant ce standard :
  - barre de recherche;
  - compteur visible/total;
  - colonnes triables;
  - message vide pour les recherches sans resultat.
- Ajout du style `.modal-table-tools` pour garder l'espacement coherent dans les modals.
- Mise a jour du test entreprises pour verifier que le modal expose bien les attributs datatable.

Regle retenue pour la suite :

- Tout nouveau tableau visible dans l'interface, y compris dans les modals, doit etre construit comme une datatable avec recherche, compteur et tri lorsque cela s'applique.
- Pour les tableaux multiples sur une meme page, utiliser le pattern `data-datatable`.

Fichiers modifies :

- `resources/js/main.js`
- `resources/views/admin/companies.blade.php`
- `resources/css/admin/dashboard.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=companies` passe.
- `php artisan test` passe avec 31 tests et 134 assertions.

### 2026-04-28 - Bouton Fermer bas sur modal utilisateurs entreprise

Prompt utilisateur :

```text
ajoute le boutton fermer sur le modal en bas. 
Pour le formatage des tableaux n'oublie pas
```

Correction appliquee :

- Ajout d'un bouton `Fermer` en bas du modal des utilisateurs affectes a une entreprise.
- Le bouton utilise le style standard `.modal-actions` + `.modal-cancel`.
- Le tableau du modal conserve le standard datatable :
  - recherche;
  - compteur visible/total;
  - colonnes triables;
  - ligne vide de recherche.
- Mise a jour du test entreprises pour verifier la presence du bouton `Fermer` et des attributs datatable.

Fichiers modifies :

- `resources/views/admin/companies.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=companies` passe.
- `php artisan test` passe avec 31 tests et 135 assertions.

### 2026-04-28 - Gestion des entreprises cote admin abonnement sur /main

Prompt utilisateur :

```text
sur la page main lorsque un admin se connecte, il peux voir toutes les entreprises affectées à son abonnements.

les chmaps du tableaux entreprise à affiche sont : 
Entreprise, nombre de site, pays et email. 

reprends le formatage du tableau. 

et lorsqu'il clique sur nouvelle entreprise une page similaire s'ouvre comme la création de l'entreprise coté superadmin, la seule différence est qu'il n'y a pas la zonne affectation car l'entreprise qu'il va crée son abonnement est déjà connu et c'est lui meme l'admin, on sait récupérer ces informations. et par rapport à la modification pareil et pour la suppression tu connais le style à appliquer on ne supprime pas les entreprises qui ont des sites n'oublie pas
```

Correction appliquee :

- Ajout des routes CRUD entreprises cote `/main` :
  - `main.companies.create`;
  - `main.companies.store`;
  - `main.companies.edit`;
  - `main.companies.update`;
  - `main.companies.destroy`.
- La page `/main` affiche maintenant les entreprises de l'abonnement de l'admin connecte.
- Le tableau `/main` suit le format datatable existant :
  - recherche;
  - compteur visible/total;
  - tri sur colonnes;
  - ligne vide de recherche.
- Colonnes affichees :
  - entreprise;
  - nombre de sites;
  - pays;
  - email;
  - actions.
- Ajout d'une page `resources/views/main/company-form.blade.php` similaire au formulaire superadmin.
- Le formulaire cote admin ne contient pas la zone `Affectation` :
  - l'abonnement est recupere depuis l'utilisateur connecte;
  - l'admin createur est l'utilisateur connecte.
- Creation et modification gerent :
  - identification;
  - contact;
  - telephones;
  - comptes avec devise;
  - logo.
- La suppression utilise le style de confirmation existant.
- Les entreprises ayant des sites ne sont pas supprimables.
- Ajout des tests pour :
  - affichage main;
  - creation entreprise sans zone affectation;
  - modification entreprise;
  - suppression bloquee si sites presents;
  - suppression autorisee sans sites.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/main.blade.php`
- `resources/views/main/company-form.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan route:list --path=main` verifie les routes.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan test --filter=main` passe.
- `php artisan test` passe avec 33 tests et 156 assertions.

### 2026-04-28 - Devise obligatoire sur les comptes entreprise

Prompt utilisateur :

```text
le devise doit etre obligatoire lors de la création d'une entreprise partout
```

Correction appliquee :

- La devise d'un compte bancaire entreprise est maintenant obligatoire des qu'une ligne de compte est renseignee.
- La regle s'applique partout ou l'entreprise est creee ou modifiee :
  - cote superadmin (`AdminController`);
  - cote admin abonnement sur `/main` (`MainController`).
- Les lignes de compte totalement vides restent ignorees pour ne pas bloquer le formulaire initial.
- Les lignes contenant une banque ou un numero de compte sans devise sont refusees avec une erreur sur `accounts.*.currency`.
- Les selects de devise affichent maintenant `Devise *` / `Currency *`.
- Les erreurs serveur de devise sont affichees au niveau de chaque ligne de compte.
- Ajout de tests pour verifier le blocage cote superadmin et cote admin `/main`.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/views/main/company-form.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=currency` passe.
- `php artisan test` passe avec 35 tests et 164 assertions.

### 2026-04-28 - Icône bouton nouvelle entreprise sur /main

Prompt utilisateur :

```text
le button nouvelle entreprise dois avoir le meme icone que l'autre
```

Correction appliquee :

- Remplacement de l'icone `bi-plus-lg` du bouton `Nouvelle entreprise` sur `/main`.
- Utilisation de la meme icone que cote superadmin : `bi-building-add`.

Fichiers modifies :

- `resources/views/main/main.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=main` passe.

### 2026-04-28 - Mise a jour export SQL de la base

Prompt utilisateur :

```text
met à jour également le fichier erp_database.sql
```

Correction appliquee :

- Ajout d'un script reutilisable `scripts/export-database.php`.
- Le script lit la configuration MySQL depuis `.env`.
- Le script exporte :
  - les tables de la base;
  - les instructions `DROP TABLE IF EXISTS`;
  - les `CREATE TABLE`;
  - les donnees avec des `INSERT INTO`;
  - les contraintes avec `FOREIGN_KEY_CHECKS`.
- Regeneration du fichier `database/exports/erp_database.sql` depuis la base locale `erp_database`.

Fichiers modifies :

- `scripts/export-database.php`
- `database/exports/erp_database.sql`
- `docs/prompts/project-history.md`

Procedure a reutiliser :

```bash
php scripts/export-database.php
```

Verification :

- `php scripts/export-database.php` execute.
- `php -l scripts/export-database.php` passe.

### 2026-04-28 - Sidebar admin reductible

Prompt utilisateur :

```text
Sur le side barre enleve les lignes verticales en arrière plan en suite rajoute un icone qui me permet de reduire la side barre quand je clique sur l'icone la side barre se reduit, juste les icones qui s'affiches pour tableau de bord, abonnement, etc... et coté EXAD ERP juste le logo qui s'affiche
```

Correction appliquee :

- Suppression du motif de lignes verticales en arriere-plan de la sidebar admin.
- Ajout d'un bouton de reduction de sidebar dans les vues admin existantes :
  - tableau de bord;
  - abonnements;
  - utilisateurs;
  - entreprises;
  - creation/modification entreprise.
- Ajout du mode compact `.sidebar-collapsed` :
  - largeur reduite a 86 px;
  - seul le logo EXAD reste visible;
  - seuls les icones des menus restent visibles;
  - le footer garde uniquement son icone.
- Ajout d'une persistance via `localStorage` avec la cle `exad-sidebar-collapsed`.
- Ajout des libelles FR/EN pour l'accessibilite du bouton :
  - `collapse_sidebar`;
  - `expand_sidebar`.

Fichiers modifies :

- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/subscriptions.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/views/admin/companies.blade.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/css/admin/dashboard.css`
- `resources/js/main.js`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=admin` passe.
- `php artisan test` passe avec 31 tests et 126 assertions.

### 2026-04-28 - Sidebar admin compacte sur tablette

Prompt utilisateur :

```text
avec des écran un peu plus petite je souhaite que tu affiche la side barre toujours à coté et non en haut à partir de l'affichage tablette tu reduit la side barre et tu laisse seulement les icones
```

Correction appliquee :

- Modification du breakpoint responsive admin a `max-width: 1180px`.
- La sidebar ne passe plus au-dessus du contenu sur les ecrans intermediaires.
- A partir du format tablette, la mise en page reste en deux colonnes :
  - sidebar compacte a gauche avec `86px`;
  - contenu principal a droite.
- En format tablette, la sidebar est automatiquement reduite :
  - seul le logo EXAD reste visible;
  - seuls les icones des menus restent visibles;
  - le bouton manuel de reduction est masque car le mode compact est force.
- Le JavaScript detecte le breakpoint avec `matchMedia('(max-width: 1180px)')` et force l'etat compact sur tablette.
- Sur desktop, le comportement manuel precedent reste conserve avec persistance `localStorage`.

Fichiers modifies :

- `resources/css/admin/dashboard.css`
- `resources/js/main.js`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=admin` passe.
- `php artisan test` passe avec 31 tests et 126 assertions.

### 2026-04-28 - Icônes activité récente conformes à la référence

Prompt utilisateur :

```text
J'ai besoin de meme icones comme dans la deuxième image stp
```

Correction appliquee :

- Remplacement des pictogrammes CSS simplifiés par les icônes Bootstrap Icons locales.
- Utilisation des icônes suivantes dans la timeline `Activité récente` :
  - `bi-person-plus` pour les nouveaux utilisateurs;
  - `bi-geo-alt` pour les nouveaux sites;
  - `bi-stack` pour les nouveaux abonnements.
- Conservation du cercle coloré et de l'alignement centré.
- Le CSS cible uniquement `.activity-icon > i` pour eviter que les styles du texte de la timeline ne perturbent les icones.
- Vidage du cache des vues Laravel avec `php artisan view:clear`.

Fichiers modifies :

- `resources/views/admin/dashboard.blade.php`
- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.

### 2026-04-28 - Taille titre modal nouveau site

Prompt utilisateur :

```text
reduit légerement la taille du titre du modal nouveau site
```

Correction appliquee :

- Reduction ciblee du titre des modals de site via `.site-modal .modal-header h2`.
- Ajout d'un espacement controle entre l'icone et le texte du titre.
- La modification ne touche pas les autres titres de modals.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=company_sites` passe.

### 2026-04-28 - Correction robuste des icones activite recente

Prompt utilisateur :

```text
Rien ne fonctionne toujours
```

Diagnostic :

- Les symboles typographiques utilises pour la timeline etaient sensibles a l'encodage du fichier et pouvaient apparaitre sous forme de caracteres casses.
- Le rendu dependait encore du contenu texte de l'icone, ce qui n'etait pas assez fiable pour cette section.

Correction appliquee :

- Suppression de l'attribut `data-activity-symbol` dans la vue du tableau de bord.
- Ajout d'une classe de type d'activite sur chaque icone :
  - `activity-type-user`;
  - `activity-type-site`;
  - `activity-type-subscription`.
- Dessin des pictogrammes uniquement en CSS avec `::before` et `::after`.
- Aucune dependance a Bootstrap Icons, Font Awesome, SVG inline ou caracteres Unicode pour ces icones.
- Conservation du style de la reference : cercle colore, pictogramme blanc centre, texte plus compact.
- Vidage du cache des vues Laravel avec `php artisan view:clear`.

Fichiers modifies :

- `resources/views/admin/dashboard.blade.php`
- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.

### 2026-04-29 - Page detail d'un site et acces aux modules disponibles

Prompt utilisateur :

```text
Je souhaite que les elements de colles site soient cliquable lorsqu'on clique, on il redirigé vers vers le site cliqué où l'on peut accéder au modules qui sont disponibles sur le site en question comme le montre l'image 2
```

Correction appliquee :

- Ajout d'une route detail pour un site d'entreprise :
  - `main.companies.sites.show`;
  - URL `/main/companies/{company}/sites/{site}`.
- Les noms des sites dans la colonne `Site` de la liste sont maintenant cliquables.
- Creation de la vue `main.company-site-show` pour afficher :
  - le retour vers la liste des sites de l'entreprise;
  - le nom du site;
  - son type;
  - son statut;
  - les details du site : responsable, telephone, email, adresse, plan;
  - les modules disponibles sur le site sous forme de cartes cliquables.
- Les admins et superadmins voient tous les modules configures sur le site.
- Un utilisateur simple affecte au site ne voit que les modules presents dans ses permissions de pivot.
- Ajout de styles dedies pour garder un affichage proche de la maquette fournie.
- Ajout des traductions FR/EN necessaires.
- Ajout de tests pour verifier :
  - le lien cliquable depuis la liste;
  - l'ouverture de la page detail par un admin;
  - le filtrage des modules visibles pour un utilisateur affecte.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/company-sites.blade.php`
- `resources/views/main/company-site-show.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l routes\web.php` passe.
- `php -l resources\views\main\company-site-show.blade.php` passe.
- `php -l resources\views\main\company-sites.blade.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=site` passe avec 13 tests et 122 assertions.
- `php artisan test` passe avec 61 tests et 372 assertions.

### 2026-04-29 - Alignement des cartes site et couleurs modules uniformisees

Prompt utilisateur :

```text
les grip du module doivent etre sur une meme ligne et les cards  détails du site et modules doivent avoir la même taille.
Sur le formulaire d'ajout et modification du site réprends les couleus
 des grids des modules  que tu as mis lorsqu'on entre dans un site
```

Correction appliquee :

- La grille des modules sur la page detail d'un site affiche maintenant les quatre modules sur une meme ligne en desktop.
- Les cartes `Details du site` et `Modules` s'etirent sur la meme hauteur.
- Les cartes de modules du formulaire de creation/modification de site utilisent les memes couleurs que les cartes de modules de la page detail :
  - comptabilite : orange clair;
  - ressources humaines : violet;
  - archivage : orange;
  - GED : vert.
- Le style selectionne du formulaire conserve le retour visuel sans remplacer la couleur du module par un bleu generique.
- Ajout d'assertions de test pour verifier les classes de couleurs des modules dans la liste et la page detail.

Fichiers modifies :

- `resources/views/main/partials/site-form-modal.blade.php`
- `resources/css/main.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\partials\site-form-modal.blade.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=company_site` passe avec 2 tests et 33 assertions.
- `php artisan test` passe avec 61 tests et 379 assertions.

### 2026-04-29 - Redirection directe des utilisateurs simples vers leur site

Prompt utilisateur :

```text
Les utilisateurs simple ne peuvent pas se connecter sur les pages main, gestion des utilisateurs et la pages qui affiche la liste des sites. lorsqu'il se connecte, ils sont directement connecté au site affecté en tenant compte aussi des modules activés sur le site
```

Correction appliquee :

- La page `/main` devient une passerelle pour le role `user` :
  - si l'utilisateur simple a un site affecte, il est redirige directement vers la page detail de ce site;
  - s'il n'a aucun site affecte, il voit toujours la page `Acces en attente`.
- Les utilisateurs simples ne peuvent plus rester sur :
  - `/main`;
  - `/main/users`;
  - `/main/companies/{company}/sites`.
- Lorsqu'un utilisateur simple tente d'ouvrir une page de gestion non autorisee, il est renvoye vers son site affecte.
- La reponse de connexion Fortify redirige maintenant directement un utilisateur simple vers son site affecte.
- La reponse apres verification 2FA applique le meme comportement.
- La page detail du site continue de filtrer les modules visibles selon les permissions attribuees a l'utilisateur sur le pivot `company_site_user`.
- Ajout d'un test qui verifie :
  - la redirection apres login vers le site;
  - le blocage/redirection depuis `/main`, `main.users` et la liste des sites;
  - l'affichage uniquement des modules autorises.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Http/Responses/LoginResponse.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l app\Http\Responses\LoginResponse.php` passe.
- `php -l app\Http\Responses\TwoFactorLoginResponse.php` passe.
- `php artisan test --filter=simple_user` passe avec 1 test et 16 assertions.
- `php artisan test` passe avec 62 tests et 395 assertions.

### 2026-04-29 - Premiers ecrans de modules de site

Prompt utilisateur :

```text
nous allons commencé par developper le modules comptabilité (facturation)
lorsqu'on clique sur ce module on est redirigé vers le tableau de bord dedié je te dirais les elements à mettre mais garde le style du tableau de bord du superadmin.
pour les autres modules lorsqu'on clique, voici ce qu'il doit etre affiche joint
```

Correction appliquee :

- Ajout d'une route dediee pour ouvrir un module d'un site :
  - `main.companies.sites.modules.show`;
  - URL `/main/companies/{company}/sites/{site}/modules/{module}`.
- Les cartes de modules de la page detail du site pointent maintenant vers cette route.
- Ajout d'une verification d'acces :
  - le site doit appartenir a l'entreprise;
  - l'utilisateur doit pouvoir acceder au site;
  - le module demande doit etre disponible pour cet utilisateur.
- Creation d'un premier tableau de bord du module `Comptabilite (Facturation)` :
  - style proche du tableau de bord superadmin;
  - cartes KPI prêtes pour factures, clients, paiements et devis;
  - panneaux vides propres en attendant les vrais indicateurs.
- Creation d'une page generique `Module en cours de developpement` pour :
  - Ressources Humaines;
  - Archivage;
  - GED.
- La page de developpement affiche :
  - retour vers le site;
  - titre du module;
  - description;
  - message indiquant que le module est en cours de developpement;
  - utilisateur connecte.
- Ajout des styles communs des pages modules.
- Ajout des traductions FR/EN.
- Ajout de tests pour verifier :
  - les liens de modules;
  - l'ouverture du tableau de bord comptabilite;
  - l'ouverture de la page de developpement pour les autres modules;
  - le blocage d'un module non attribue a un utilisateur simple.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/company-site-show.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/views/main/modules/under-development.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l resources\views\main\modules\under-development.blade.php` passe.
- `php -l resources\views\main\company-site-show.blade.php` passe.
- `php -l routes\web.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=module` passe avec 5 tests et 44 assertions.
- `php artisan test` passe avec 64 tests et 411 assertions.

### 2026-04-29 - Sidebar du module comptabilite

Prompt utilisateur :

```text
nous sommes dans le modules comptabilités.
mets la sidebarre avec le style comme on l'a fait avec superadmin. reduction etc...
Les elements du menu sidebarre : tableau de bord, contacts, stock, services, devises, modes de paiement, facturation, vente, depenses, autres, dettes, creances, rapport.
```

Correction appliquee :

- Le tableau de bord `Comptabilite (Facturation)` utilise maintenant le shell avec sidebar comme la console superadmin.
- Ajout du bouton de reduction de sidebar avec le comportement existant `sidebarToggle`.
- Ajout d'une navigation comptable structuree :
  - Tableau de bord;
  - Contacts : clients, fournisseurs, prospects, creanciers, debiteurs, partenaires, commerciaux;
  - Stock : articles, sous-categories, categories;
  - Services : grille tarifaire, sous-categories, categories;
  - Devises;
  - Modes de paiement;
  - Facturation : vente et depenses;
  - Autres : dettes, creances, taxes, tresorerie, rapprochement bancaire, relances de paiement;
  - Rapport.
- Les sous-menus restent visibles quand la sidebar est ouverte et se replient proprement quand la sidebar est reduite ou en affichage tablette.
- Ajout des traductions FR/EN pour toutes les nouvelles entrees.
- Le test du tableau de bord comptabilite verifie maintenant la presence de la sidebar et des rubriques principales.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 32 assertions.
- `php artisan test` passe avec 64 tests et 433 assertions.

### 2026-04-29 - Sous-menus comptabilite replies par defaut

Prompt utilisateur :

```text
les sous menus doivent automatiquement etre caché ou reduit.
les elements à coté du side barre ne doivent pas etre positionné à gauche ils doivent prendre le 100% du coté
```

Correction appliquee :

- Les groupes de sous-menu de la sidebar comptabilite sont maintenant fermes par defaut.
- Les titres de groupes sont devenus des boutons accessibles avec `aria-expanded`.
- Un clic sur un groupe ouvre ou referme son sous-menu.
- Quand la sidebar est reduite ou en affichage tablette, les sous-menus restent caches et seuls les pictogrammes principaux sont affiches.
- Le contenu du tableau de bord comptabilite prend maintenant toute la largeur disponible a droite de la sidebar.
- Ajout d'assertions de test pour verifier que les sous-menus sont rendus replies par defaut.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `resources/js/main.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 34 assertions.

### 2026-05-05 - Ajustement entete PDF proforma

Prompt utilisateur :

```text
agrandi legerement le logo et EXAD et EXAD KINSHASA enleve EXAD ERP
```

Correction appliquee :

- Le logo et le bloc identite entreprise/site sont legerement agrandis dans le PDF proforma.
- La mention de secours `EXAD ERP` sous le titre du document a ete retiree.
- Le site web de l'entreprise reste affiche uniquement lorsqu'il est renseigne.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-05 - Adresse visible pour tous les types tiers

Prompt utilisateur :

```text
pourquoi lors de l'ajout du clent; prospect et fournisseur quand on selectionne type entreprise tu supprime le champ adresse on a besoin des adresses des tous type de client
```

Correction appliquee :

- Le champ adresse n'est plus limite au type particulier.
- Le champ adresse reste visible et saisissable pour les clients, prospects et fournisseurs, que le type soit particulier ou entreprise.
- Les erreurs de validation restent affichees sous le champ et les placeholders sont conserves.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/accounting-prospects.blade.php`
- `resources/views/main/modules/accounting-suppliers.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-05 - Adresse alignee avec site web

Prompt utilisateur :

```text
adresse doit etre à coté de site web
```

Correction appliquee :

- Le champ adresse se positionne maintenant a cote du champ site web lorsque le type entreprise est selectionne.
- Pour le type particulier, le meme champ adresse reste dans la grille des informations personnelles.
- La logique utilise un seul champ adresse par formulaire afin d'eviter les doublons soumis.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/accounting-prospects.blade.php`
- `resources/views/main/modules/accounting-suppliers.blade.php`
- `resources/js/main/accounting-clients.js`
- `resources/js/main/accounting-prospects.js`
- `resources/js/main/accounting-suppliers.js`
- `docs/prompts/project-history.md`

### 2026-05-05 - Espacement sections formulaires tiers

Prompt utilisateur :

```text
espace entre email et Numéros de compte etc
```

Correction appliquee :

- Ajout d'un espacement plus confortable avant les sections `Numeros de compte` et `Suivi commercial`.
- L'ajustement concerne les formulaires client, prospect et fournisseur sans modifier le reste des formulaires.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-05-05 - Suppression redondance modalite PDF

Prompt utilisateur :

```text
enleve ça c'est une redondance
```

Correction appliquee :

- La modalite de paiement a ete retiree du bloc de metadonnees a droite du PDF proforma.
- La section dediee `Modalite de paiement` reste affichee plus bas dans le document.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-05 - Agrandissement general des textes PDF

Prompt utilisateur :

```text
AGRANDI LEGEREMENT les restes du texte aussi
continue la dernière requette
```

Correction appliquee :

- La taille generale du texte du PDF proforma a ete legerement augmentee.
- Les blocs client, metadonnees, lignes de facture, totaux, modalites de paiement, conditions, signature et footer ont ete ajustes.
- Le rendu conserve la police Courier/Courier New sur tous les textes.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-proforma-invoice-print.blade.php` passe.

### 2026-05-05 - Agrandissement complementaire entete PDF

Prompt utilisateur :

```text
LEGEREMENT ENCORE
```

Correction appliquee :

- Le logo PDF proforma est passe de 60px a 66px.
- Le nom de l'entreprise et le nom du site ont ete legerement agrandis.
- L'espacement entre le logo et l'identite a ete ajuste.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-05 - Nettoyage des titres redondants sur la creation proforma

Prompt utilisateur :

```text
dans facture proforma, enleve les titres encercles en rouge c'est redondant
```

Correction appliquee :

- Suppression du titre de section duplique sous le lien de retour sur la page de creation d'une facture proforma.
- Suppression du titre duplique dans la carte du formulaire proforma.
- Conservation du titre principal de la barre haute et de la phrase descriptive de la page.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-05-05 - Centrage du bouton Annuler

Prompt utilisateur :

```text
centre bien le boutton annuler
```

Correction appliquee :

- Uniformisation du centrage interne des boutons d'action de modal/page.
- Les liens utilises comme bouton Annuler sont maintenant centres comme les boutons de soumission.

Fichiers modifies :

- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

### 2026-05-01 - Alignement global des montants dans les tableaux

Prompt utilisateur :

```text
dans tous les tableaux pas seulement celui-ci
```

Correction appliquee :

- Generalisation de la classe `amount-cell` aux colonnes de montants des tableaux principaux.
- Alignement a droite des montants dans les ressources stock et services via les colonnes `money`.
- Alignement a droite des montants dans les tableaux creanciers, debiteurs, proformas, commerciaux et devises.
- Ajout d'une regle CSS commune pour garder les montants en chiffres tabulaires et sans retour a la ligne.
- Alignement a droite des boutons de tri dans les en-tetes de colonnes de montants.

Fichiers modifies :

- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/views/main/modules/accounting-creditors.blade.php`
- `resources/views/main/modules/accounting-debtors.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-sales-representatives.blade.php`
- `resources/views/main/modules/accounting-currencies.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test` passe avec 76 tests et 756 assertions.

### 2026-05-01 - Colonnes simplifiees du modal des services rattaches

Prompt utilisateur :

```text
pareill pour servuce, enleve type de facturation et status
```

Correction appliquee :

- Retrait des colonnes `Type de facturation` et `Statut` dans le modal des services rattaches a une sous-categorie.
- Le tableau du modal affiche maintenant uniquement : reference, service, unite de service et prix de vente.
- Le payload envoye au modal et le rendu JS ont ete nettoyes pour ne plus transporter ces champs inutiles.
- Le test du module services a ete ajuste sur le nouveau format du modal.

Fichiers modifies :

- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/js/main/accounting-service-resource.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources/js/main/accounting-service-resource.js` passe.
- `php -l resources/views/main/modules/accounting-service-resource.blade.php` passe.
- `php artisan test --filter=accounting_service_pages` passe avec 1 test et 31 assertions.

### 2026-05-01 - Centrage des messages vides dans les tableaux de modal

Prompt utilisateur :

```text
dans tous les tableaux du modal le message aucun....rattaché dois toujours etre au milieu
```

Correction appliquee :

- Ajout d'une regle globale sur `.modal-table-empty`.
- Les messages d'etat vide des tableaux en modal sont maintenant centres horizontalement et verticalement dans leur zone.
- Le comportement s'applique aux modals actuels et aux futurs tableaux de modal qui utilisent ce standard.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-05-01 - Modes de paiement du module comptabilité

Prompt utilisateur :

```text
la devise est obligatoire par défaut séléctionner la devise d base.
si le mode de paiement est une banque ajouter des champs pas obligatoire :
- IBAN, Numéro de compte, BIC/Swift code etc...

applique maintenant
```

Travail réalisé :

- Ajout de la page `Modes de paiement` dans le module Comptabilité avec tableau standard, recherche, tri, pagination et actions.
- Ajout des routes CRUD `main.accounting.payment-methods`.
- Ajout d'un formulaire modal create/edit/view pour les modes de paiement.
- La devise est obligatoire et présélectionnée sur la devise de base du site.
- Ajout d'un mode système `Espèces` créé automatiquement pour chaque site comptable.
- Le mode système est affiché en premier et protégé contre la modification/suppression.
- Possibilité de définir un seul mode de paiement par défaut.
- Si le type est `Banque`, affichage des champs facultatifs : banque, titulaire, numéro de compte, IBAN, BIC/Swift et adresse bancaire.
- Si le type n'est pas `Banque`, les champs bancaires sont nettoyés automatiquement.
- Ajout du lien `Modes de paiement` dans la sidebar comptabilité et le tableau de bord comptabilité.
- Ajout du JS isolé `resources/js/main/accounting-payment-methods.js`.
- Mise à jour de l'export SQL avec la table `accounting_payment_methods`.
- Ajout des traductions FR/EN liées aux modes de paiement.

Fichiers ajoutés ou modifiés :

- `database/migrations/2026_05_01_000001_create_accounting_payment_methods_table.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingPaymentMethod.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-payment-methods.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l` passe sur le modèle, la migration, le contrôleur et les routes.
- `node --check resources/js/main/accounting-payment-methods.js` passe.
- `php artisan migrate --force` applique la migration des modes de paiement.
- `php artisan test --filter=payment_methods` passe avec 1 test et 20 assertions.
- `php artisan test` passe avec 75 tests et 702 assertions.

### 2026-05-01 - Réorganisation du sous-menu Vente

Prompt utilisateur :

```text
change juste l'ordre de sous-menu et ajoute ce que tu vient de dire mais ne rajoute pas encore les pages
```

Correction appliquée :

- Réorganisation du sous-menu `Vente` dans le cycle logique : proforma, commande client, livraison, facture, encaissement, caisse, avoirs et autres entrées.
- Ajout des entrées de navigation sans création de pages ni routes pour le moment :
  - Commandes clients
  - Encaissements
  - Avoirs / Notes de crédit
- Mise à jour de la sidebar comptabilité et de la sidebar intégrée du tableau de bord comptabilité.
- Ajout des traductions FR/EN des nouveaux libellés.

Fichiers modifiés :

- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan test --filter=accounting_module` passe avec 2 tests et 63 assertions.

### 2026-05-01 - Factures proforma

Prompt utilisateur :

```text
applique maintenant
```

Contexte retenu :

- Les factures proforma sont des documents commerciaux non comptables.
- Les lignes de proforma ne portent pas de TVA ligne par ligne.
- La TVA s'applique globalement au document.

Travail réalisé :

- Ajout des tables `accounting_proforma_invoices` et `accounting_proforma_invoice_lines`.
- Ajout des modèles `AccountingProformaInvoice` et `AccountingProformaInvoiceLine`.
- Ajout des relations :
  - `CompanySite::accountingProformaInvoices()`
  - `AccountingClient::proformaInvoices()`
- Ajout des routes CRUD `main.accounting.proforma-invoices`.
- Ajout de la page `Factures proforma` avec le tableau standard, recherche, tri, pagination et actions.
- Ajout du formulaire modal de création/modification.
- Ajout des champs principaux : client, objet, date, expiration, devise, statut, notes et conditions.
- Ajout des lignes dynamiques : article, service ou ligne libre.
- Les lignes contiennent : désignation, description, quantité, prix unitaire, remise et total ligne.
- Ajout du calcul serveur des totaux :
  - sous-total
  - remise totale
  - total HT
  - taux TVA global
  - montant TVA
  - total TTC
- Ajout du calcul instantané côté interface via `resources/js/main/accounting-proforma-invoices.js`.
- Ajout du lien `Factures proforma` dans le sous-menu Vente.
- Mise à jour de l'export SQL.
- Ajout des traductions FR/EN.

Fichiers ajoutés ou modifiés :

- `database/migrations/2026_05_01_000002_create_accounting_proforma_invoices_tables.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingProformaInvoice.php`
- `app/Models/AccountingProformaInvoiceLine.php`
- `app/Models/CompanySite.php`
- `app/Models/AccountingClient.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/partials/proforma-line-row.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-proforma-invoices.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l` passe sur les nouveaux modèles, la migration et le contrôleur.
- `node --check resources/js/main/accounting-proforma-invoices.js` passe.
- `php artisan migrate --force` applique la migration proforma.
- `php artisan test --filter=proforma` passe avec 1 test et 16 assertions.
- `php artisan test` passe avec 76 tests et 718 assertions.

### 2026-04-30 - Selection de l'entrepot dans le modal categorie stock

Prompt utilisateur :

```text
^pourquoi lorsque je modifie la categorie par défaut je vois qui ne selectionne pas l'entrepro par defaut
```

Correction appliquee :

- La base contenait bien la relation entre la categorie par defaut et l'entrepot par defaut.
- Le probleme venait du transfert des valeurs de la ligne du tableau vers le modal d'edition.
- Les valeurs du bouton modifier sont maintenant encodees en base64 pour eviter les problemes d'attribut HTML.
- Le JS decode ces valeurs avant de remplir le formulaire.
- Le remplissage des champs `select` force maintenant la selection de la bonne option et declenche un evenement `change`.
- Ajout d'une assertion pour verifier que `warehouse_id` est bien present dans les donnees d'edition de la categorie.

Fichiers modifies :

- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources/js/main/accounting-stock-resource.js` passe.
- `php -l resources/views/main/modules/accounting-stock-resource.blade.php` passe.
- `php artisan test --filter=accounting_stock` passe avec 1 test et 27 assertions.
- `php artisan test` passe avec 72 tests et 625 assertions.

### 2026-04-30 - Elements stock par defaut en lecture seule

Prompt utilisateur :

```text
les default tooivent toujours s'afficher en premier sur le tableau et ne doivent pas etre modifiable, mais on peut seulement voir les informations enleve le bouton modifier mets voir on affiche le modal sans possibilité de modifier
```

Correction appliquee :

- Les categories, sous-categories et entrepots marques `is_default` s'affichent maintenant en premier dans leurs tableaux.
- Les elements par defaut n'affichent plus le bouton modifier.
- Le bouton d'action des elements par defaut devient un bouton `Voir`.
- Le modal s'ouvre en mode lecture seule pour les elements par defaut.
- Les champs du modal sont desactives en mode lecture seule.
- Le bouton de soumission du modal est masque en mode lecture seule.
- La modification backend des elements par defaut est bloquee, meme en cas de requete manuelle.
- La suppression restait deja bloquee et le bouton supprimer reste masque.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources/js/main/accounting-stock-resource.js` passe.
- `php -l resources/views/main/modules/accounting-stock-resource.blade.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan test --filter="company_sites|accounting_stock"` passe avec 2 tests et 60 assertions.
- `php artisan test` passe avec 72 tests et 632 assertions.
- Scan rapide des traductions et fichiers d'interface : aucun texte casse de type `Ã`, `Â` ou `�`.

### 2026-04-30 - Rubrique commerciaux du module comptabilite

Prompt utilisateur :

```text
applique ton idée et travail sur la pages commerciaux
```

Implementation appliquee :

- Ajout de la table `accounting_sales_representatives`.
- Ajout du modele `AccountingSalesRepresentative` avec generation automatique des references `COM-000001`.
- Ajout de la relation `CompanySite::accountingSalesRepresentatives()`.
- Ajout des routes CRUD commerciaux dans le module comptabilite.
- Ajout de la page `main.modules.accounting-sales-representatives` avec le tableau standard : recherche, tri, pagination, actions et etat vide propre.
- Ajout du formulaire modal create/edit avec placeholders, erreurs sous les champs et maintien du modal en cas d'erreur.
- Ajout des champs metier : type, nom, telephone, email, adresse, zone commerciale, devise, objectif mensuel, objectif annuel, taux de commission, statut et notes.
- Ajout des types de commerciaux : interne, externe, agent independant, revendeur et apporteur d'affaires.
- Raccordement du menu `Commerciaux` dans la sidebar comptabilite.
- Mise a jour du tableau de bord comptabilite pour inclure les commerciaux dans la repartition des contacts.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000009_create_accounting_sales_representatives_table.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingSalesRepresentative.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-sales-representatives.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-sales-representatives.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` applique la migration commerciaux.
- `php -l` passe sur le modele, la migration, le controleur, la vue commerciaux et les routes.
- `node --check resources/js/main/accounting-sales-representatives.js` passe.
- Le controle UTF-8 des fichiers `lang/fr/*.php` ne retourne aucune anomalie.
- `php artisan test --filter=accounting` passe avec 10 tests et 197 assertions.
- `php artisan view:clear` execute.
- `php artisan test` passe avec 71 tests et 592 assertions.

### 2026-04-30 - Precision du taux de commission commercial

Prompt utilisateur :

```text
il faut préciser le champs dans le formullaire %
```

Correction appliquee :

- Le champ du formulaire commercial affiche maintenant `Taux de commission (%)`.
- Ajout d'un suffixe visuel `%` directement dans l'input du taux de commission.
- Le placeholder du champ indique un exemple simple : `Ex. 5`.

Fichiers modifies :

- `resources/views/main/modules/accounting-sales-representatives.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-sales-representatives.blade.php` passe.
- Le controle UTF-8 des fichiers `lang/fr/*.php` ne retourne aucune anomalie.
- `php artisan test --filter=accounting_sales_representatives` passe avec 1 test et 16 assertions.

### 2026-04-30 - Rubrique partenaires du module comptabilite

Prompt utilisateur :

```text
applique ton idée
```

Implementation appliquee :

- Ajout de la table `accounting_partners` pour gerer les partenaires rattaches a un site de facturation.
- Ajout du modele `AccountingPartner` avec generation automatique des references `PAR-000001`.
- Ajout de la relation `CompanySite::accountingPartners()`.
- Ajout des routes CRUD partenaires dans le module comptabilite.
- Ajout de la page `main.modules.accounting-partners` avec le tableau standard : recherche, tri, pagination, actions et ligne vide propre.
- Ajout du formulaire modal create/edit avec placeholders, erreurs sous les champs et conservation du modal en cas d'erreur.
- Ajout des types de partenaires : apporteur d'affaires, distributeur, sous-traitant, cabinet conseil, institution, banque, agence et autre.
- Ajout des statuts : actif, en discussion, suspendu et termine.
- Raccordement du menu `Partenaires` dans la sidebar comptabilite.
- Mise a jour du tableau de bord comptabilite pour inclure les partenaires dans la repartition des contacts.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000008_create_accounting_partners_table.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingPartner.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-partners.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-partners.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` applique la migration partenaires.
- `php -l` passe sur le modele, la migration, le controleur, la vue partenaires et les routes.
- `node --check resources/js/main/accounting-partners.js` passe.
- Le controle UTF-8 des fichiers `lang/fr/*.php` ne retourne aucune anomalie.
- `php artisan test --filter=accounting` passe avec 9 tests et 181 assertions.
- `php artisan view:clear` execute.
- `php artisan test` passe avec 70 tests et 576 assertions.

### 2026-04-30 - Correction encodage UTF-8 des traductions francaises

Prompt utilisateur :

```text
j'ai un probleme de formatage de texte ne reprends plus cette erreur
```

Correction appliquee :

- Correction des chaines francaises mal encodees dans `lang/fr/*.php`.
- Suppression des sequences mojibake visibles comme `Ã©`, `Ã `, `Â°` et `â€™`.
- Verification explicite des traductions concernees :
  - `Comptabilité (Facturation)` ;
  - `Liste des clients rattachés à ce site de facturation.` ;
  - `Chiffre d’affaires` ;
  - `Détails du site`.
- Nettoyage du cache des vues Blade pour forcer le nouvel affichage.

Regle a respecter :

- Ne plus reecrire les fichiers de traduction FR avec un encodage qui casse l'UTF-8.
- Si une correction massive de texte est necessaire, verifier ensuite l'absence de sequences `Ã`, `Â` ou `�`.

Fichiers modifies :

- `lang/fr/main.php`
- `lang/fr/auth.php`
- `lang/fr/validation.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l lang\fr\main.php` passe.
- `php -l lang\fr\auth.php` passe.
- `php -l lang\fr\validation.php` passe.
- Recherche PHP sur `lang/fr/*.php` : aucune sequence `Ã`, `Â` ou `�` restante.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting` passe avec 5 tests et 108 assertions.
- `php artisan test` passe avec 66 tests et 503 assertions.

### 2026-04-30 - Ordre du menu contacts comptabilite

Prompt utilisateur :

```text
change la disposition commence par Prospects, Clients, Fournisseurs; etc...
```

Changement applique :

- Le sous-menu `Contacts` de la sidebar comptabilite commence maintenant par :
  - Prospects ;
  - Clients ;
  - Fournisseurs ;
  - Creanciers ;
  - Debiteurs ;
  - Partenaires ;
  - Commerciaux.
- Le meme ordre est applique dans la sidebar incluse du dashboard comptabilite.

Fichiers modifies :

- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=accounting` passe avec 5 tests et 108 assertions.

### 2026-04-30 - Ajustements visuels du modal client

Prompt utilisateur :

```text
c'est bien mais espace un peu l'icone de suppression et l'input.
deuxième image espace l'icon et nouveau client
```

Correction appliquee :

- Ajout d'un espace reserve a droite dans les cartes de contact du modal client pour separer le bouton de suppression des champs.
- Alignement du titre du modal client en `inline-flex` avec un espacement propre entre l'icone et le texte.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-04-29 - Fixation de la zone basse comptabilite et nouvelles entrees sidebar

Prompt utilisateur :

```text
la nav barre ça va mais la zone en bas non. regles ça.

dans la side barre, avant rapport ajoute Tâches et  après rapport ajoute parametres du modules
```

Correction appliquee :

- La topbar du module comptabilite reste fixe.
- La zone basse du panneau droit est maintenant elle aussi encadree dans un conteneur fixe.
- Le scroll est limite au contenu interne du tableau de bord, ce qui evite le glissement de toute la zone basse.
- Ajout de l'entree `Taches` avant `Rapport`.
- Ajout de l'entree `Parametres du module` apres `Rapport`.
- Ajout des traductions FR/EN et des assertions de test associees.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 36 assertions.

### 2026-04-29 - Dashboard professionnel du module comptabilite avec ApexCharts

Prompt utilisateur :

```text
reprends le style semane, mois , année comme tu l'avais fais dans le tableau de bord superadmin .

Sur base des elements que je t'ai donné à mettre dans la sidebarre.

génère moi un tableau de bord très professionnel avec charts utilisant toujours la meme lib comme fais dans superadmin. les charts que tu vas générer pour l'instant doivent juste avoir des elements aléatoires en attendants
```

Correction appliquee :

- Ajout des onglets de periode `Semaine`, `Mois`, `Annee` avec le meme style que le tableau de bord superadmin.
- Remplacement des panneaux vides par un tableau de bord comptabilite plus complet.
- Ajout de cartes KPI temporaires :
  - chiffre d'affaires;
  - factures de vente;
  - paiements;
  - creances;
  - depenses.
- Ajout de graphiques ApexCharts avec donnees temporaires :
  - evolution ventes / depenses;
  - repartition des contacts;
  - stock et services;
  - flux de documents;
  - tresorerie, dettes et creances.
- Creation d'un fichier JS isole pour le dashboard comptabilite :
  - `resources/js/main/accounting-dashboard.js`.
- Les onglets de periode mettent a jour le graphique principal avec des donnees mockees semaine/mois/annee.
- Ajout des traductions FR/EN necessaires.
- Ajout de tests pour verifier les onglets, les panneaux de graphiques et le JS dedie.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-dashboard.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 49 assertions.

### 2026-04-29 - Periodes du graphique comptabilite alignees sur le superadmin

Prompt utilisateur :

```text
quand le boutton est sur semaines fais comme tu avais fais coté superadmin, pareil pour mois et année
```

Correction appliquee :

- Les donnees mockees du graphique principal comptabilite utilisent maintenant la meme logique de periodes que le dashboard superadmin.
- `Semaine` affiche des semaines glissantes avec labels de date.
- `Mois` affiche des mois glissants avec labels mois/annee.
- `Annee` affiche des annees glissantes.
- Les series temporaires ventes, chiffre d'affaires et depenses ont ete alignees sur le nombre de points de chaque periode.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 49 assertions.

### 2026-04-29 - Vue tresorerie periodique et echeancier comptabilite

Prompt utilisateur :

```text
pareil pour la vue tresorerie.

et rajoute l'echeancier ausssi dans la zone vide
```

Correction appliquee :

- Le graphique `Vue tresorerie, dettes et creances` utilise maintenant les memes periodes que le graphique principal.
- Les boutons `Semaine`, `Mois` et `Annee` mettent a jour en meme temps :
  - l'evolution ventes / depenses;
  - la vue tresorerie / dettes / creances.
- Ajout de donnees mockees periodiques pour :
  - creances;
  - dettes;
  - taches.
- Ajout d'un panneau `Echeancier` dans la zone vide de la grille du dashboard comptabilite.
- L'echeancier affiche temporairement des echeances facture, commande, creance et dette avec montant et date.
- Ajout des styles du panneau echeancier.
- Ajout des traductions FR/EN et des assertions de test.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-dashboard.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 51 assertions.
- `php artisan test` passe avec 64 tests et 452 assertions.

### 2026-04-29 - Echeancier comptabilite oriente dettes et creances

Prompt utilisateur :

```text
non pour l'écheancier je souhaite savoir combien mes clients me doivent et combien je dois à mes fournisseurs, les dettes et les créances que j'ai
```

Correction appliquee :

- Refonte du panneau `Echeancier` pour afficher les dettes et creances plutot que de simples evenements.
- Ajout de deux totaux temporaires :
  - montant que les clients doivent a l'entreprise;
  - montant que l'entreprise doit aux fournisseurs.
- Ajout d'une liste d'echeances temporaires detaillees :
  - creances clients;
  - dettes fournisseurs;
  - tiers concerne;
  - montant;
  - date d'echeance.
- Ajout des styles de synthese et des pastilles de statut.
- Ajout des traductions FR/EN et des assertions de test.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 56 assertions.
- `php artisan test` passe avec 64 tests et 457 assertions.

### 2026-04-29 - Responsivite des charts et sidebar mobile comptabilite

Prompt utilisateur :

```text
il y a un soucis cotés flux de documents la hauteur dois prendre toutes le parent.
et je souhaite que mes tous soit responsives memes les charts.

ajuste aussi la sidebare de sorte que lorsque je suis en affichage mobile jdonne la possibilité que je puisse agrandir et reduire car j'ai des sous-menus
```

Correction appliquee :

- Le panneau `Flux de documents` devient un panneau large avec une hauteur plus coherente.
- Les panneaux du dashboard comptabilite utilisent un layout flex pour que les charts puissent remplir leur parent.
- Les charts ApexCharts sont configures pour se redessiner au resize du parent et de la fenetre.
- La grille du dashboard comptabilite passe en une colonne sur les largeurs tablette/mobile.
- Les KPI et les cartes d'echeancier deviennent responsives.
- La sidebar comptabilite garde le bouton de reduction/agrandissement en mobile.
- En mobile/tablette, la sidebar comptabilite peut maintenant etre agrandie pour afficher les sous-menus, puis reduite a nouveau.
- L'ordre CSS de la page comptabilite charge les styles admin avant les styles main afin que les overrides du module comptabilite gagnent correctement.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main.js`
- `resources/js/main/accounting-dashboard.js`
- `resources/css/main.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 57 assertions.
- `php artisan test` passe avec 64 tests et 458 assertions.

### 2026-04-29 - Flux documents pleine hauteur et devise du site

Prompt utilisateur :

```text
le flux de documents est toujours suspendus en haut, il doit pas laisser d'espace en bas.
Et pour les momants toujours mettre la devise du site
```

Correction appliquee :

- Le graphique `Flux de documents` est configure en hauteur `100%` cote ApexCharts.
- Le panneau `Flux de documents` utilise un layout flex pour que le graphique occupe toute la hauteur utile du parent.
- Les canvas/svg ApexCharts du panneau sont forces a prendre toute la hauteur disponible.
- Les montants temporaires du dashboard comptabilite utilisent maintenant la devise du site via `$site->currency`.
- Les KPI et l'echeancier ne sont plus figes en `CDF`; ils affichent la devise du site.
- Ajout d'assertions de test pour verifier les montants affiches avec la devise.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-dashboard.js`
- `resources/css/main.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 59 assertions.
- `php artisan test` passe avec 64 tests et 460 assertions.

### 2026-04-29 - Retrait des taches du graphique tresorerie

Prompt utilisateur :

```text
qu'es ce que la tâche fou ici
```

Correction appliquee :

- Retrait de la serie `Taches` du graphique `Vue tresorerie, dettes et creances`.
- Le graphique n'affiche plus que :
  - Creances;
  - Dettes.
- Suppression des donnees mockees `tasks` du dataset periodique du graphique tresorerie.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-dashboard.js`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=accounting_module` passe avec 2 tests et 59 assertions.

### 2026-04-29 - Suppression de l'effet blanc au-dessus du dashboard comptabilite

Prompt utilisateur :

```text
mais cet effet blanc aud dessus me derange il est toujours présent, scrollable mais enleve cet effet blanc
```

Correction appliquee :

- Le module comptabilite garde son scroll naturel comme le dashboard superadmin.
- Ajout d'une classe racine `accounting-module-root` sur le document HTML du module.
- Application du fond de page au `html` et au `body` du module comptabilite.
- Desactivation de l'overscroll vertical pour eviter l'effet de rebond qui expose un espace blanc au-dessus.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 49 assertions.

### 2026-04-29 - Scroll du module comptabilite aligne sur le dashboard superadmin

Prompt utilisateur :

```text
scrollable comme dans le tashboard superadmin stp
```

Correction appliquee :

- Le panneau droit du module comptabilite reprend le comportement de scroll naturel du dashboard superadmin.
- Le shell n'est plus fixe sur le viewport.
- Le contenu droit defile avec la page quand la hauteur depasse l'ecran.
- La sidebar reste sticky et garde son scroll interne invisible pour les menus longs.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 49 assertions.
- `php artisan test` passe avec 64 tests et 450 assertions.

### 2026-04-29 - Scroll interne du contenu droit comptabilite

Prompt utilisateur :

```text
stp la zone à coté du side barre si la hauteur depasse la taille de l'écran, il doit etre scrollable
```

Correction appliquee :

- La zone de contenu du dashboard comptabilite redevient scrollable uniquement si son contenu depasse la hauteur de l'ecran.
- Le scroll reste contenu dans le panneau droit, sans faire glisser toute la page ni la sidebar.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 49 assertions.
- `php artisan test` passe avec 64 tests et 437 assertions.

### 2026-04-29 - Blocage complet de la zone basse comptabilite

Prompt utilisateur :

```text
tout ce qui suit en bas dois etre fixe please
```

Correction appliquee :

- Suppression du dernier scroll interne de la zone basse du dashboard comptabilite.
- Toute la partie sous la topbar reste maintenant fixe, sans glissement a la roulette.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 36 assertions.

### 2026-04-29 - Blocage du glissement vertical du module comptabilite

Prompt utilisateur :

```text
quand j'eessaie de faire la roulette souris ça glisse et laisse un peu d'espace blan au dessus je souhaite que ça soit fixe comme coté superadmin plaese
```

Correction appliquee :

- Ajout d'une classe de page `accounting-module-body` sur le module comptabilite.
- Le scroll du document est verrouille sur cette page pour eviter le glissement et l'espace blanc en haut.
- Les scrolls internes de la sidebar et de la zone de contenu restent fonctionnels.
- Ajout de `overscroll-behavior` pour contenir le scroll dans les zones prevues.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 34 assertions.

### 2026-04-29 - Fixation complete du panneau droit comptabilite

Prompt utilisateur :

```text
la side barre ça va mais la zone à coté non
```

Correction appliquee :

- Le shell du module comptabilite est maintenant fixe sur tout le viewport.
- Le panneau droit ne scrolle plus comme un bloc.
- Le `main` de droite est fixe en hauteur et masque son overflow.
- Seule la zone de contenu interne du tableau de bord comptabilite peut defiler si son contenu depasse.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 34 assertions.

### 2026-05-06 - Ligne du footer PDF identique au bas de page

Prompt utilisateur :

```text
la ligne du pied de page doit etre similaire à la ligne du bas de page
```

Correction appliquee :

- La ligne du footer fixe utilise maintenant la meme structure bleu + gris que la ligne de bas de page.
- La ligne conserve une hauteur coherente dans le PDF.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - QR proforma sans libelle

Prompt utilisateur :

```text
enleve le texte scanner pour ouvrir ....
agrandit legerement le qr code
```

Correction appliquee :

- Suppression du texte sous le QR code dans le PDF proforma.
- Agrandissement leger du QR code affiche dans le PDF.
- Agrandissement de la taille SVG generee pour garder une meilleure nettete.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - QR code dans le PDF proforma

Prompt utilisateur :

```text
j'ai besoin que dans chaque facture tu me genere un code QR qui s'affiche en bas de termes et condions.
Ce code qui doit renvoyer vers le lien de cette facture pour ouvrir la proforma
```

Correction appliquee :

- Generation d'un QR code SVG avec la librairie deja disponible `bacon/bacon-qr-code`.
- Le QR code pointe vers le lien PDF de la proforma.
- Le QR code est affiche sous les termes et conditions dans le PDF.
- Ajout de libelles traduisibles pour l'alt et le texte sous le QR code.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 45 assertions.

### 2026-05-06 - Signature PDF alignee a droite

Prompt utilisateur :

```text
le texte doit etre ajusté à droite
```

Correction appliquee :

- Le bloc signature du PDF proforma est maintenant aligne a droite.
- La ligne de signature est egalement positionnee a droite pour rester coherente avec le texte.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Signature utilisateur sur PDF proforma

Prompt utilisateur :

```text
n'oublie pas l'utf-8 pour l'accentuation;
Enleve signature de l'entreprise
mets le Nom de l'utilisateur, et si le grade de l'utilisateur est rempli affiche le
```

Correction appliquee :

- Le bloc signature du PDF proforma affiche maintenant le nom de l'utilisateur createur de la proforma.
- Le grade de l'utilisateur s'affiche uniquement lorsqu'il est renseigne.
- Le libelle `Signature de l'entreprise` a ete retire du PDF.
- La traduction francaise `Facture générée par EXAD ERP` utilise maintenant les accents en UTF-8.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `lang/fr/main.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Traduction du libelle de generation PDF

Prompt utilisateur :

```text
Invoice generated by EXAD ERP doit etre traductible
```

Correction appliquee :

- Ajout de la cle `invoice_generated_by` dans les traductions FR et EN.
- Le footer PDF proforma utilise maintenant `__('main.invoice_generated_by')`.

Fichiers modifies :

- `lang/fr/main.php`
- `lang/en/main.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Footer fixe en bas du PDF proforma

Prompt utilisateur :

```text
fixe le pied de page en bs, voici un exemple
```

Correction appliquee :

- Le pied de page du PDF proforma est maintenant fixe en bas de page.
- Ajout d'une ligne bleue et d'un bloc textuel avec les informations de l'entreprise.
- Les anciennes icones du footer ont ete masquees pour eviter les soucis d'encodage dans DomPDF.
- La marge basse du PDF a ete augmentee pour eviter que le contenu principal chevauche le footer.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Objet place avant les lignes dans le PDF proforma

Prompt utilisateur :

```text
l'objet doit s'afficher juste avant les items comme dans une lettre
```

Correction appliquee :

- L'objet de la proforma a ete retire du bloc d'informations a droite.
- L'objet s'affiche maintenant juste avant le tableau des lignes, avec un format de type lettre.
- L'espace avant le tableau des lignes a ete ajuste.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Objet affiche dans le PDF proforma

Prompt utilisateur :

```text
tu dois également afficher l'objet de la facture
```

Correction appliquee :

- Ajout de l'objet de la proforma dans le bloc d'informations du PDF.
- La ligne `Objet` s'affiche uniquement lorsqu'un objet est renseigne sur la proforma.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Taille uniforme du bloc client PDF proforma

Prompt utilisateur :

```text
Facturé à et l'adresse du client doit avoir la meme taille que facture nà et le reste;
```

Correction appliquee :

- Dans le PDF proforma, le libelle `Facture a` et l'adresse du client utilisent maintenant la meme taille que les informations de facture a droite.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Date explicite et retrait du statut sur PDF proforma

Prompt utilisateur :

```text
toujours dans le fichier pdf affiche :
Date : la date
enleve status
```

Correction appliquee :

- Dans le PDF de facture proforma, la date est maintenant affichee avec le libelle `Date :`.
- La ligne `Statut` a ete retiree du bloc d'informations de la facture.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Adresse seule sous le client dans la proforma PDF

Prompt utilisateur :

```text
dans la facture proforma Sous le nom du client, affiche seulement l'adresse et s'il n'y a pas d'adresse n'affiche pas
```

Correction appliquee :

- Dans le PDF de facture proforma, le bloc client affiche maintenant le nom du client puis uniquement son adresse.
- Le telephone, l'email et les tirets par defaut ont ete retires du bloc client.
- Si le client n'a pas d'adresse, aucune ligne supplementaire n'est affichee sous son nom.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-06 - Fond grise des lignes proforma

Prompt utilisateur :

```text
cette ligne de proforma la card doit etre un peu grisatre
```

Correction appliquee :

- Ajout d'un fond gris tres leger sur `.proforma-line-card`.
- Le changement est limite aux lignes de proforma et ne modifie pas les cartes de contacts.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-05-06 - Modification proforma sur page dediee

Prompt utilisateur :

```text
la modification d'une proforma ne dois pas etre dans un modal, il doit etre dans une page à part comme c'est lors de la création
```

Correction appliquee :

- Ajout de la route `main.accounting.proforma-invoices.edit`.
- Ajout de la methode `editAccountingProformaInvoice` dans `MainController`.
- La page `accounting-proforma-invoice-create.blade.php` sert maintenant a la creation et a la modification.
- Le formulaire d'edition pre-remplit le client, les dates, la devise, le statut, les modalites de paiement, les notes, les conditions, la TVA et les lignes de proforma.
- Dans la liste des proformas, le bouton modifier redirige maintenant vers la page dediee au lieu d'ouvrir un modal.
- Suppression du modal de creation/modification proforma dans la liste, afin d'eviter deux comportements differents.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l routes/web.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan route:list --name=main.accounting.proforma-invoices` affiche la route `edit`.
- `php artisan test --filter=proforma` passe avec 1 test et 45 assertions.
- `php artisan view:clear` execute.
- `php artisan test` passe avec 76 tests et 800 assertions.

### 2026-05-05 - Impression PDF des factures proforma

Prompt utilisateur :

```text
ajouter un bouton qui permet de d'imprimer la facture en pdf
```

Correction appliquee :

- Ajout d'un bouton d'impression dans la colonne actions du tableau des factures proforma.
- Ajout d'une route d'impression dediee pour chaque facture proforma.
- Ajout d'une page imprimable A4 propre qui ouvre automatiquement la fenetre d'impression du navigateur.
- La page d'impression affiche les informations de l'entreprise, du site, du client, les lignes, les remises, la TVA, les totaux et les zones de signature.
- Ajout des traductions FR/EN pour les libelles d'impression.
- Ajout d'un test pour verifier la presence du bouton et l'acces a la page imprimable.

Fichiers ajoutes ou modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `resources/css/main.css`
- `resources/css/admin/dashboard.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l routes/web.php` passe.
- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 33 assertions.

### 2026-05-05 - Generation PDF serveur avec DomPDF

Prompt utilisateur :

```text
il y pas moyen que tu utilises une vraie librairie pdf ?
```

Correction appliquee :

- Installation de `barryvdh/laravel-dompdf`.
- La route d'impression des factures proforma genere maintenant un vrai document PDF cote serveur.
- La reponse HTTP de la route d'impression est maintenant en `application/pdf`.
- La vue imprimable reste reutilisee comme gabarit PDF, avec les styles mobiles isoles pour ne pas casser le rendu DomPDF.
- Le test proforma verifie maintenant que la route retourne bien un PDF.

Fichiers ajoutes ou modifies :

- `composer.json`
- `composer.lock`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `composer require barryvdh/laravel-dompdf` execute avec succes.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 31 assertions.

### 2026-05-05 - Gabarit PDF professionnel pour proforma

Prompt utilisateur :

```text
la facture est desordonnée fais moi une facture propre professionnel
```

Correction appliquee :

- Remplacement du gabarit PDF par une mise en page plus stable pour DomPDF.
- Suppression des styles modernes mal rendus en PDF (`grid`, `flex`, variables CSS, toolbar navigateur).
- Mise en page professionnelle avec bandeau, entete entreprise/proforma, blocs client/details, tableau de lignes, totaux et signatures.
- Alignement propre des montants et conservation de la devise sur chaque montant.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 31 assertions.

### 2026-05-05 - Gabarit PDF inspire du template fourni

Prompt utilisateur :

```text
voici un template
```

Correction appliquee :

- Refonte du gabarit PDF proforma pour reprendre la structure du template fourni.
- Ajout d'un grand titre bleu a droite, identité de l'entreprise a gauche, séparateur bleu/gris, bloc client, bloc reference/date/statut.
- Tableau de lignes avec entete bleu et lignes alternees.
- Ajout d'une zone mode de paiement, totaux, grand total bleu, conditions et signature.
- Ajout d'un pied de page avec telephone, email et adresse de l'entreprise.
- Ajout des libelles FR/EN necessaires au nouveau template.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 31 assertions.

### 2026-05-05 - Modalite de paiement sur les proformas

Prompt utilisateur :

```text
ajoute lors dans l'ajout de la proforma une option modalité de paiement, on a plusieurs possibilités : 100% à la commande; 50% à la commande, etc... et à discuter avec le client
```

Correction appliquee :

- Ajout du champ `payment_terms` sur les factures proforma.
- Ajout d'une migration pour stocker la modalite de paiement.
- Ajout des options : 100% a la commande, 50% a la commande, 30% a la commande, paiement a la livraison, paiement apres livraison et a discuter avec le client.
- Ajout du select de modalite de paiement dans la page d'ajout et le formulaire de modification proforma.
- Affichage de la modalite de paiement dans le PDF proforma.
- Ajout des traductions FR/EN.
- Mise a jour de l'export SQL.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_05_05_000002_add_payment_terms_to_accounting_proforma_invoices_table.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingProformaInvoice.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` execute et la migration est appliquee.
- `php -l` passe sur le modele, le controleur, la migration et les vues proforma.
- `php artisan test --filter=proforma` passe avec 1 test et 33 assertions.

### 2026-05-05 - Libelle modalite de paiement dans le PDF

Prompt utilisateur :

```text
dans le fichier pdf change mode de paiement en modalité de paiement
continue
```

Correction appliquee :

- Le bloc PDF affiche maintenant `Modalité de paiement` au lieu de `Mode de paiement`.
- Le contenu du bloc affiche la modalite choisie sur la proforma.
- Les coordonnees bancaires restent affichees uniquement si un compte d'entreprise est configure.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 33 assertions.

### 2026-05-05 - Police Courier New pour les factures PDF

Prompt utilisateur :

```text
utilise courrier new comme police pour les factures
```

Correction appliquee :

- Le gabarit PDF des factures proforma utilise maintenant `Courier New`.
- Ajout de polices de secours compatibles DomPDF : `DejaVu Sans Mono`, puis `monospace`.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 33 assertions.

### 2026-05-05 - Courier New force sur tout le PDF

Prompt utilisateur :

```text
tous les textes sauf rien
```

Correction appliquee :

- La police `Courier New` est maintenant forcee sur tous les elements du gabarit PDF proforma.
- Aucune zone du PDF n'a une police differente, sauf fallback technique DomPDF si Courier New n'est pas disponible.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 33 assertions.

### 2026-05-05 - Correction police des titres PDF

Prompt utilisateur :

```text
certains text nêest pas en courrier new comme le titre par exemple
```

Correction appliquee :

- Forcage explicite de la famille `Courier` / `Courier New` sur les titres, tableaux et textes du PDF.
- Remplacement des poids trop forts (`800`, `900`) par `bold` pour eviter que DomPDF bascule certains titres vers une police serif.
- Conservation de `DejaVu Sans Mono` comme fallback technique.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-proforma-invoice-print.blade.php` passe.
- `php artisan test --filter=proforma` passe avec 1 test et 33 assertions.

### 2026-05-01 - Page dediee pour l'ajout d'une facture proforma

Prompt utilisateur :

```text
pour l'ajout d'une proforma je souhaite que tu cee une page a part
```

Correction appliquee :

- Ajout d'une route GET dediee pour l'ouverture de la page de creation d'une facture proforma.
- Ajout de la methode `createAccountingProformaInvoice` dans `MainController`.
- Le bouton `Nouvelle proforma` redirige maintenant vers une page complete de creation au lieu d'ouvrir un modal.
- Creation de la vue `main.modules.accounting-proforma-invoice-create`.
- Conservation du modal pour la modification des proformas existantes.
- Adaptation du script proforma pour fonctionner a la fois dans un modal et sur une page autonome.
- Ajout d'un style dedie pour la carte de creation proforma.
- Mise a jour du test proforma pour verifier la nouvelle route et la page de creation.

Fichiers modifies ou ajoutes :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/js/main/accounting-proforma-invoices.js`
- `resources/css/main.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l routes\web.php` passe.
- `node --check resources\js\main\accounting-proforma-invoices.js` passe.
- `php artisan route:list --name=main.accounting-proforma-invoices` affiche bien la route `create`.
- `php artisan test --filter=proforma` passe avec 1 test et 21 assertions.
- `php artisan test` passe avec 76 tests et 723 assertions.

### 2026-05-01 - Ajout du type Quantite pour les unites de stock

Prompt utilisateur :

```text
revenons d'abord sur Unités coté stock nous allons revenir vers proforma plutad.
Parmi les types des unités ajoute :
- Quantité
```

Correction appliquee :

- Ajout du type `quantity` dans le modele `AccountingStockUnit`.
- Le type est maintenant accepte par la validation des unites de stock.
- Ajout du libelle `Quantite` en francais et `Quantity` en anglais.
- Ajout de l'affichage du type dans le tableau standard des unites.
- Mise a jour du test stock pour creer une unite avec ce nouveau type.

Fichiers modifies :

- `app/Models/AccountingStockUnit.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingStockUnit.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 27 assertions.
- `php artisan test` passe avec 76 tests et 723 assertions.

### 2026-05-01 - Modification autorisee des elements stock par defaut

Prompt utilisateur :

```text
bon pour catégorie, sous catégorie et entreprot donne la possibilité de modifier et non de supprimer enleve voir
```

Correction appliquee :

- Les entrepots, categories et sous-categories stock crees par defaut affichent maintenant le bouton `Modifier`.
- Le modal s'ouvre en mode edition au lieu du mode consultation `Voir`.
- Le controleur accepte maintenant la mise a jour de ces elements par defaut.
- La suppression reste interdite pour ces elements par defaut.
- Le test existant a ete ajuste pour verifier ce nouveau comportement.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-stock-resource.blade.php` passe.
- `php artisan test --filter=admin_can_open_company_sites_and_create_site_with_assignments` passe avec 1 test et 38 assertions.
- `php artisan test` passe avec 76 tests et 723 assertions.

### 2026-05-01 - Unite de stock par defaut

Prompt utilisateur :

```text
le système dois également avoir une unité par défaut :
- Nom : Pièce
- Symbole : pc
- Type : Quantité
- Status : Actif
```

Correction appliquee :

- Ajout du champ `is_default` sur les unites de stock.
- Ajout d'une migration pour creer automatiquement l'unite par defaut des sites comptabilite existants.
- Ajout de la creation automatique de l'unite `Pièce` pour les nouveaux sites comptabilite.
- L'unite par defaut utilise le symbole `pc`, le type `quantity` et le statut `active`.
- Les unites par defaut s'affichent en premier dans le tableau des unites.
- Les unites par defaut restent modifiables mais ne peuvent pas etre supprimees.
- Mise a jour de l'export SQL.
- Mise a jour du test de creation de site pour verifier l'unite par defaut et sa protection contre la suppression.

Fichiers modifies ou ajoutes :

- `app/Models/AccountingStockUnit.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `database/migrations/2026_05_01_000003_add_default_stock_unit.php`
- `database/exports/erp_database.sql`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingStockUnit.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l database\migrations\2026_05_01_000003_add_default_stock_unit.php` passe.
- `php -l resources\views\main\modules\accounting-stock-resource.blade.php` passe.
- `php artisan test --filter=admin_can_open_company_sites_and_create_site_with_assignments` passe avec 1 test et 42 assertions.
- `php artisan test` passe avec 76 tests et 727 assertions.
- `php artisan migrate` execute et cree l'unite par defaut dans la base locale.
- Verification en base : `Pièce`, symbole `pc`, type `quantity`, statut `active`, `is_default = true`.

### 2026-05-01 - Synchronisation automatique categorie, sous-categorie et entrepot des articles

Prompt utilisateur :

```text
lors de l'ajout de l'article, lorsque je séléctionne catégorie, automatiquement l'entrepot au quel cette catégorie est dedant se selectionne, pareil pour sous categorie lorsque je le selectionne automatique la categorie correspondate se selectionne
```

Correction appliquee :

- Les options de categorie portent maintenant l'identifiant de leur entrepot via `data-warehouse-id`.
- Les options de sous-categorie portent maintenant l'identifiant de leur categorie via `data-category-id`.
- Dans le formulaire Article, le choix d'une categorie selectionne automatiquement l'entrepot correspondant.
- Dans le formulaire Article, le choix d'une sous-categorie selectionne automatiquement la categorie correspondante.
- La selection automatique declenche aussi la synchronisation categorie vers entrepot.
- Le test stock verifie que ces relations sont presentes dans le formulaire Article.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-stock-resource.blade.php` passe.
- `node --check resources\js\main\accounting-stock-resource.js` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 32 assertions.
- `php artisan test` passe avec 76 tests et 732 assertions.

### 2026-05-01 - Synchronisation automatique categorie et sous-categorie des services

Prompt utilisateur :

```text
applique ça coté service aussi
```

Correction appliquee :

- Les options de sous-categorie de service portent maintenant l'identifiant de leur categorie via `data-category-id`.
- Dans le formulaire de grille tarifaire des services, le choix d'une sous-categorie selectionne automatiquement la categorie correspondante.
- Le test services verifie que cette relation est bien presente dans le formulaire.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/js/main/accounting-service-resource.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-service-resource.blade.php` passe.
- `node --check resources\js\main\accounting-service-resource.js` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=accounting_service_pages` passe avec 1 test et 28 assertions.
- `php artisan test` passe avec 76 tests et 734 assertions.

### 2026-05-01 - Modification autorisee des unites de service par defaut

Prompt utilisateur :

```text
unité de service possibilité de modifier enleve voir seulement
```

Correction appliquee :

- Les unites de service par defaut affichent maintenant le bouton `Modifier` au lieu du bouton `Voir`.
- Le controleur autorise la mise a jour des unites de service par defaut.
- Les categories et sous-categories de service par defaut restent en consultation seule.
- La suppression des unites de service par defaut reste interdite.
- Le test de creation de site verifie que l'unite de service par defaut est modifiable.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-service-resource.blade.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=admin_can_open_company_sites_and_create_site_with_assignments` passe avec 1 test et 48 assertions.
- `php artisan test` passe avec 76 tests et 740 assertions.

### 2026-05-01 - Modification autorisee des categories et sous-categories de service par defaut

Prompt utilisateur :

```text
pareil pour catégorie et sous-catégorie
```

Correction appliquee :

- Les categories de service par defaut affichent maintenant le bouton `Modifier`.
- Les sous-categories de service par defaut affichent maintenant le bouton `Modifier`.
- Le controleur autorise la mise a jour des categories et sous-categories de service par defaut.
- La suppression des categories, sous-categories et unites de service par defaut reste interdite.
- Le test de creation de site verifie que les categories et sous-categories de service par defaut sont modifiables.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-service-resource.blade.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=admin_can_open_company_sites_and_create_site_with_assignments` passe avec 1 test et 58 assertions.
- `php artisan test` passe avec 76 tests et 750 assertions.

### 2026-05-01 - Liste des articles et services par sous-categorie

Prompt utilisateur :

```text
dans sous-catégorie d'article ou service, ajoute un boutton dans action qui affiche la liste des articles/services appartenant à cette dernière
```

Correction appliquee :

- Ajout d'un bouton d'action dans le tableau des sous-categories d'articles.
- Le bouton ouvre un modal listant les articles rattaches a la sous-categorie.
- Ajout d'un bouton d'action dans le tableau des sous-categories de services.
- Le bouton ouvre un modal listant les services rattaches a la sous-categorie.
- Les modals utilisent un tableau avec recherche, tri, pagination client et etat vide propre.
- Les relations `items.unit` et `services.unit` sont chargees avec les sous-categories.
- Ajout des traductions FR/EN des titres, boutons et etats vides.
- Les tests Stock et Services verifient la presence des boutons et des donnees rattachees.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `resources/js/main/accounting-service-resource.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-stock-resource.blade.php` passe.
- `php -l resources\views\main\modules\accounting-service-resource.blade.php` passe.
- `node --check resources\js\main\accounting-stock-resource.js` passe.
- `node --check resources\js\main\accounting-service-resource.js` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 35 assertions.
- `php artisan test --filter=accounting_service_pages` passe avec 1 test et 31 assertions.
- `php artisan test` passe avec 76 tests et 756 assertions.

### 2026-05-01 - Ajustement affichage des tableaux dans les modals de sous-categories

Prompt utilisateur :

```text
arrange l'affichage du tableau
```

Correction appliquee :

- Les modals listant les articles/services rattaches aux sous-categories utilisent maintenant une largeur dediee.
- Le tableau reste contenu dans le modal et ne deborde plus hors du cadre.
- Ajout d'un cadre propre autour du tableau avec scroll horizontal si necessaire.
- Le titre du modal garde un espacement correct entre l'icone et le texte.
- Le rendu reste responsive sur petits ecrans.

Fichiers modifies :

- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-stock-resource.blade.php` passe.
- `php -l resources\views\main\modules\accounting-service-resource.blade.php` passe.
- `php artisan test --filter=accounting_service_pages` passe avec 1 test et 31 assertions.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 35 assertions.
- `php artisan test` passe avec 76 tests et 756 assertions.

### 2026-05-01 - Colonnes simplifiees dans le modal des articles rattaches

Prompt utilisateur :

```text
enleve stock actuel dans le modal et status
```

Correction appliquee :

- Retrait de la colonne `Stock actuel` dans le modal des articles rattaches a une sous-categorie.
- Retrait de la colonne `Statut` dans ce meme modal.
- Les donnees envoyees au modal ne contiennent plus ces champs.
- Le test stock a ete ajuste pour verifier le nouveau format de donnees.

Fichiers modifies :

- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-stock-resource.blade.php` passe.
- `node --check resources\js\main\accounting-stock-resource.js` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 35 assertions.
- `php artisan test` passe avec 76 tests et 756 assertions.

### 2026-05-01 - Alignement a droite des montants

Prompt utilisateur :

```text
les monants doivent toujours etres positions à droite
```

Correction appliquee :

- Alignement a droite des colonnes de montants dans le modal des articles rattaches.
- Alignement a droite des colonnes de montants dans le modal des services rattaches.
- Ajout d'une classe `amount-cell` pour standardiser le rendu des montants.

Fichiers modifies :

- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `resources/js/main/accounting-service-resource.js`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-stock-resource.blade.php` passe.
- `php -l resources\views\main\modules\accounting-service-resource.blade.php` passe.
- `node --check resources\js\main\accounting-stock-resource.js` passe.
- `node --check resources\js\main\accounting-service-resource.js` passe.
- `php artisan test --filter=accounting_stock_pages` passe avec 1 test et 35 assertions.
- `php artisan test --filter=accounting_service_pages` passe avec 1 test et 31 assertions.
- `php artisan test` passe avec 76 tests et 756 assertions.

### 2026-04-30 - Module comptabilite : services

Prompt utilisateur :

```text
fais moi une proprosition pour les servives et ne fais rien
appliques ton idee
```

Implementation appliquee :

- Ajout des tables du module services : unites, categories, sous-categories, grille tarifaire et services recurrents.
- Ajout des modeles `AccountingServiceUnit`, `AccountingServiceCategory`, `AccountingServiceSubcategory`, `AccountingService` et `AccountingRecurringService`.
- Ajout des relations services sur `CompanySite`.
- Ajout de la creation automatique des valeurs par defaut pour chaque site comptabilite :
  - unite par defaut `Forfait`
  - categorie par defaut `Services generaux`
  - sous-categorie par defaut `Prestations generales`
- Protection des elements par defaut : affichage en premier, consultation possible, modification et suppression bloquees.
- Ajout des routes CRUD generiques pour les ressources services.
- Ajout de la page generique `accounting-service-resource` avec le style de tableau standard, recherche, tri, pagination, modal create/edit/view et erreurs sous les champs.
- Ajout du JS isole `resources/js/main/accounting-service-resource.js`.
- Raccordement de la sidebar et du tableau de bord comptabilite :
  - Grille tarifaire
  - Categories de services
  - Sous-categories de services
  - Unites de services
  - Services recurrents
- Ajout des traductions FR/EN liees au module services.
- Mise a jour de `database/exports/erp_database.sql` avec les nouvelles tables et l'entree de migration.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000013_create_accounting_services_tables.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingServiceUnit.php`
- `app/Models/AccountingServiceCategory.php`
- `app/Models/AccountingServiceSubcategory.php`
- `app/Models/AccountingService.php`
- `app/Models/AccountingRecurringService.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-service-resource.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur les routes, le controleur, les modeles, la migration et les tests.
- `node --check resources/js/main/accounting-service-resource.js` passe.
- `php artisan route:list --path=accounting/services` affiche les 4 routes services.
- `php artisan migrate --force` applique la migration services.
- `php artisan test --filter=accounting_service` passe avec 1 test et 26 assertions.
- `php artisan test --filter=accounting_stock` passe avec 1 test et 27 assertions.
- `php artisan test` passe avec 73 tests et 661 assertions.
- Scan anti-mauvais caracteres (`Ã`, `Â`, `�`) execute sur les traductions, vues et JS : aucun fichier signale.

### 2026-04-30 - Module comptabilite : devises

Prompt utilisateur :

```text
appliques ton idée concernant les devises
```

Implementation appliquee :

- Ajout d'une table `accounting_currencies` pour gerer les devises utilisees par chaque site de facturation.
- Ajout du modele `AccountingCurrency` avec reference automatique `CUR-000001`, etc.
- Ajout de la relation `CompanySite::accountingCurrencies()`.
- Creation automatique de la devise de base du site lors de la creation/modification d'un site comptabilite.
- La devise du site reste la devise de base, avec taux `1`, statut actif, et protection contre modification/suppression dans cette page.
- Ajout d'une page `Devises` dans le module comptabilite avec :
  - tableau standard
  - recherche
  - tri
  - pagination
  - modal creation/modification/consultation
  - erreurs sous les champs
- Ajout du JS isole `resources/js/main/accounting-currencies.js`.
- Raccordement de la sidebar et du dashboard comptabilite vers la page Devises.
- Ajout des traductions FR/EN liees aux devises.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000014_create_accounting_currencies_table.php`
- `app/Models/AccountingCurrency.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-currencies.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-currencies.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `database/exports/erp_database.sql`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le modele, la migration, le controleur, les routes et les tests.
- `node --check resources/js/main/accounting-currencies.js` passe.
- `php artisan route:list --path=accounting/currencies` affiche les 4 routes Devises.
- `php artisan migrate --force` applique la migration Devises.
- `php artisan test --filter=accounting_currencies` passe avec 1 test et 19 assertions.
- `php artisan test` passe avec 74 tests et 681 assertions.
- Scan anti-mauvais caracteres (`Ã`, `Â`, `�`) execute sur les traductions, vues et JS : aucun fichier signale.

### 2026-05-01 - Format du taux de change

Prompt utilisateur :

```text
toujours afficher deux zero après la virgule
```

Correction appliquee :

- Le taux de change de la page Devises s'affiche maintenant avec deux decimales dans le tableau.
- Le champ du modal utilise aussi une valeur par defaut `1.00` avec un pas de saisie `0.01`.
- La validation minimale du taux passe a `0.01`.
- Les placeholders FR/EN du taux de change ont ete ajustes.

Fichiers modifies :

- `resources/views/main/modules/accounting-currencies.blade.php`
- `app/Http/Controllers/MainController.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l tests/Feature/ExampleTest.php` passe.
- `php artisan test --filter=accounting_currencies` passe avec 1 test et 19 assertions.
- `php artisan test` passe avec 74 tests et 681 assertions.

### 2026-04-30 - Module stock complet pour la comptabilite

Prompt utilisateur :

```text
fais ça, crée les pages et les tables avec toute la logique et les rélations entre tables
```

Travail realise :

- Ajout des tables stock du module comptabilite :
  - categories
  - sous-categories
  - unites
  - entrepots
  - articles
  - lots / series
  - mouvements de stock
  - transferts
  - inventaires
  - lignes d'inventaire
  - alertes de stock
- Ajout des relations entre site, categories, sous-categories, unites, entrepots, articles, lots, mouvements, transferts, inventaires et alertes.
- Ajout des modeles Eloquent dedies au stock.
- Ajout d'une generation automatique de reference par ressource avec le trait `HasAccountingReference`.
- Ajout des pages CRUD generiques du stock dans le module comptabilite.
- Ajout du tableau standard avec recherche, tri, pagination et actions.
- Ajout du modal standard create/edit avec erreurs sous les champs.
- Ajout de la logique de mouvement :
  - entree : augmente le stock courant
  - sortie : diminue le stock courant sans passer sous zero
  - ajustement : fixe le stock courant a la quantite indiquee
- Ajout des rubriques stock dans la sidebar comptabilite.
- Mise a jour de l'export `database/exports/erp_database.sql`.
- Ajout des traductions FR/EN.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000010_create_accounting_stock_tables.php`
- `database/exports/erp_database.sql`
- `app/Models/Concerns/HasAccountingReference.php`
- `app/Models/AccountingStockCategory.php`
- `app/Models/AccountingStockSubcategory.php`
- `app/Models/AccountingStockUnit.php`
- `app/Models/AccountingStockWarehouse.php`
- `app/Models/AccountingStockItem.php`
- `app/Models/AccountingStockBatch.php`
- `app/Models/AccountingStockMovement.php`
- `app/Models/AccountingStockTransfer.php`
- `app/Models/AccountingStockInventory.php`
- `app/Models/AccountingStockInventoryLine.php`
- `app/Models/AccountingStockAlert.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/js/main/accounting-stock-resource.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` passe et applique les tables stock.
- `php artisan test --filter=accounting_stock` passe avec 1 test et 25 assertions.
- `php artisan test --filter=accounting` passe avec 11 tests et 222 assertions.
- `php artisan test` passe avec 72 tests et 617 assertions.
- Scan rapide des fichiers `resources`, `lang`, `app`, `routes` et migrations : aucun texte casse de type `Ã`, `Â` ou `�`.

### 2026-04-30 - Enregistrements stock par defaut non supprimables

Prompt utilisateur :

```text
je souhaite que dans le systeme qu'on ai à la base une entrepretot déjà précréé par defaut, une categorie par defaut et sous-catégorie par defaut qu'on ne peut pas supprimer
```

Travail realise :

- Ajout d'un champ `is_default` sur :
  - `accounting_stock_categories`
  - `accounting_stock_subcategories`
  - `accounting_stock_warehouses`
- Creation automatique, pour chaque site qui contient le module comptabilite, de :
  - `Categorie generale`
  - `Sous-categorie generale`
  - `Entrepot principal`
- Creation automatique de ces valeurs lors de la creation ou modification d'un site contenant le module comptabilite.
- Blocage de la suppression des categories, sous-categories et entrepots marques comme valeurs par defaut.
- Masquage du bouton de suppression dans le tableau pour ces valeurs par defaut.
- Mise a jour de l'export SQL.
- Ajout de la traduction FR/EN du message de blocage.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000011_add_default_stock_records.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingStockCategory.php`
- `app/Models/AccountingStockSubcategory.php`
- `app/Models/AccountingStockWarehouse.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` passe et applique la migration.
- `php artisan test --filter="company_sites|accounting_stock"` passe avec 2 tests et 51 assertions.
- `php artisan test` passe avec 72 tests et 623 assertions.
- Scan rapide des traductions et fichiers d'interface : aucun texte casse de type `Ã`, `Â` ou `�`.

### 2026-04-30 - Relation entrepot par defaut, categorie par defaut et sous-categorie par defaut

Prompt utilisateur :

```text
une sous catégorie par défaut appartient à une catégorie par défault et une catégorie par défaut appartient à un entreprot par défaut
```

Travail realise :

- Ajout de la relation `warehouse_id` sur `accounting_stock_categories`.
- Une categorie de stock peut maintenant appartenir a un entrepot.
- La categorie par defaut est rattachee automatiquement a l'entrepot par defaut.
- La sous-categorie par defaut reste rattachee a la categorie par defaut.
- La creation automatique des valeurs stock suit maintenant l'ordre :
  - entrepot par defaut
  - categorie par defaut liee a cet entrepot
  - sous-categorie par defaut liee a cette categorie
- La page des categories affiche et exige maintenant l'entrepot rattache.
- Mise a jour de l'export SQL.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000012_add_warehouse_to_stock_categories.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingStockCategory.php`
- `app/Models/AccountingStockWarehouse.php`
- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` passe et applique la relation.
- `php artisan test --filter="company_sites|accounting_stock"` passe avec 2 tests et 51 assertions.
- `php artisan test` passe avec 72 tests et 623 assertions.
- Scan rapide des traductions et fichiers d'interface : aucun texte casse de type `Ã`, `Â` ou `�`.

### 2026-04-30 - Gestion des prospects dans le module comptabilite

Prompt utilisateur :

```text
applique ton idée pour les prospects
```

Implementation appliquee :

- Ajout des tables `accounting_prospects` et `accounting_prospect_contacts`.
- Ajout des modeles `AccountingProspect` et `AccountingProspectContact`.
- Ajout de la relation `CompanySite::accountingProspects()`.
- Ajout des routes CRUD prospects dans le module comptabilite.
- Ajout d'une page `main.modules.accounting-prospects` avec tableau standard, recherche, tri et pagination.
- Ajout du formulaire modal create/edit pour prospects particuliers et entreprises.
- Ajout des champs CRM : source, statut, niveau d'interet et notes.
- Ajout des contacts multiples pour les prospects de type entreprise.
- Ajout d'une action permettant de convertir un prospect en client.
- Mise a jour du tableau de bord comptabilite pour integrer les prospects dans la repartition des contacts.
- Mise a jour de l'export `database/exports/erp_database.sql`.
- Ajout des traductions FR/EN propres aux prospects.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000005_create_accounting_prospects_tables.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingProspect.php`
- `app/Models/AccountingProspectContact.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-prospects.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-prospects.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le controleur, les modeles et la migration prospects.
- `node --check resources/js/main/accounting-prospects.js` passe.
- `php artisan migrate` applique la migration prospects.
- `php artisan test --filter=accounting` passe avec 6 tests et 131 assertions.
- `php artisan test` passe avec 67 tests et 526 assertions.
- Controle UTF-8 des fichiers `lang/fr/*.php` sans mojibake detecte.
- `php artisan view:clear` execute.

### 2026-04-30 - Retrait de la colonne contacts des tableaux comptabilite

Prompt utilisateur :

```text
n'affiche pas la colone contacs dans les tableaux de prospects, client, fournisseurs et autres
```

Correction appliquee :

- Retrait de la colonne visible `Contacts` sur les tableaux Clients, Fournisseurs et Prospects.
- Conservation des contacts dans les donnees du formulaire modal pour l'edition et la conversion.
- Ajustement des index de tri et des colonnes `colspan` des lignes vides/recherche.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/accounting-suppliers.blade.php`
- `resources/views/main/modules/accounting-prospects.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur les trois vues Blade concernees.
- `php artisan test --filter=accounting` passe avec 6 tests et 131 assertions.

### 2026-04-30 - Gestion des creanciers dans le module comptabilite

Prompt utilisateur :

```text
applique ton idée
```

Implementation appliquee :

- Ajout de la table `accounting_creditors`.
- Ajout du modele `AccountingCreditor` avec reference automatique `CRE-000001`.
- Ajout de la relation `CompanySite::accountingCreditors()`.
- Ajout des routes CRUD creanciers dans le module comptabilite.
- Ajout de la page `main.modules.accounting-creditors` avec tableau standard, recherche, tri et pagination.
- Ajout d'un formulaire modal create/edit pour les informations du creancier.
- Ajout des informations de dette : devise, montant initial, montant deja paye, solde du, echeance, priorite, statut et description.
- Branchement du lien `Creanciers` dans la sidebar comptabilite.
- Mise a jour du tableau de bord comptabilite pour utiliser le total reel des dettes creanciers dans l'echeancier.
- Mise a jour de l'export `database/exports/erp_database.sql`.
- Ajout des traductions FR/EN liees aux creanciers.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000006_create_accounting_creditors_table.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingCreditor.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-creditors.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-creditors.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le controleur, les routes, le modele, la migration et la vue Blade creanciers.
- `node --check resources/js/main/accounting-creditors.js` passe.
- `php artisan migrate` applique la migration creanciers.
- `php artisan test --filter=accounting` passe avec 7 tests et 148 assertions.
- Controle UTF-8 des fichiers `lang/fr/*.php` sans mojibake detecte.
- `php artisan view:clear` execute.
- `php artisan test` passe avec 68 tests et 543 assertions.

### 2026-04-30 - Retrait de la colonne priorite du tableau creanciers

Prompt utilisateur :

```text
n'affiche pas priorité dans le tableau
```

Correction appliquee :

- Retrait de la colonne visible `Priorite` dans le tableau Creanciers.
- Conservation du champ `Priorite` dans le formulaire create/edit.
- Ajustement des index de tri et des colonnes `colspan` des lignes vides/recherche.

Fichiers modifies :

- `resources/views/main/modules/accounting-creditors.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-creditors.blade.php` passe.
- `php artisan test --filter=accounting_creditors` passe avec 1 test et 17 assertions.

### 2026-04-30 - Gestion des debiteurs dans le module comptabilite

Prompt utilisateur :

```text
applique ton idée
```

Implementation appliquee :

- Ajout de la table `accounting_debtors`.
- Ajout du modele `AccountingDebtor` avec reference automatique `DEB-000001`.
- Ajout de la relation `CompanySite::accountingDebtors()`.
- Ajout des routes CRUD debiteurs dans le module comptabilite.
- Ajout de la page `main.modules.accounting-debtors` avec tableau standard, recherche, tri et pagination.
- Ajout d'un formulaire modal create/edit pour les informations du debiteur.
- Ajout des informations de creance : devise, montant initial, montant deja encaisse, solde a encaisser, echeance, statut et description.
- Branchement du lien `Debiteurs` dans la sidebar comptabilite.
- Mise a jour du tableau de bord comptabilite pour utiliser le total reel des creances debiteurs dans l'echeancier.
- Mise a jour de l'export `database/exports/erp_database.sql`.
- Ajout des traductions FR/EN liees aux debiteurs.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000007_create_accounting_debtors_table.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingDebtor.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-debtors.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-debtors.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le controleur, les routes, le modele, la migration et la vue Blade debiteurs.
- `node --check resources/js/main/accounting-debtors.js` passe.
- `php artisan migrate` applique la migration debiteurs.
- `php artisan test --filter=accounting` passe avec 8 tests et 165 assertions.
- Controle UTF-8 des fichiers `lang/fr/*.php` sans mojibake detecte.
- `php artisan view:clear` execute.
- `php artisan test` passe avec 69 tests et 560 assertions.

### 2026-04-30 - Tableau de bord comptabilite avec donnees clients reelles

Prompt utilisateur :

```text
comme on a déjà une table client mets à jour le tableau de bord avec des vraies informations clients
```

Changements appliques :

- Ajout d'un agregat serveur pour compter les clients du site comptable.
- Le KPI `Clients` du tableau de bord comptabilite utilise maintenant le nombre reel de clients du site.
- Le graphique `Repartition des contacts` utilise maintenant les donnees reelles :
  - clients particuliers ;
  - clients entreprises ;
  - contacts rattaches aux clients entreprises.
- Ajout d'un etat vide propre lorsque le site ne contient encore aucun client.
- Mise a jour du test du tableau de bord comptabilite avec des clients reels.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-dashboard.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `node --check resources\js\main\accounting-dashboard.js` passe.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 64 assertions.
- `php artisan test` passe avec 65 tests et 484 assertions.

### 2026-04-30 - Fournisseurs comptabilite et coordonnees bancaires clients

Prompt utilisateur :

```text
applique ton idée par rapport à la view fournisseurs mais pour le client ajoute et ajuste ça aussi :
Numéro de compte
Banque
Devise du compte
```

Changements appliques :

- Ajout d'un module fournisseurs dans la comptabilite.
- Creation des tables `accounting_suppliers` et `accounting_supplier_contacts`.
- Ajout des modeles `AccountingSupplier` et `AccountingSupplierContact`.
- Ajout d'une reference fournisseur automatique au format `FRS-000001`.
- Ajout du CRUD fournisseurs avec tableau standard, recherche, tri, pagination, modal create/edit et suppression confirmee.
- Ajout du statut fournisseur actif/inactif.
- Ajout du lien `Fournisseurs` dans la sidebar comptabilite.
- Utilisation d'un nom d'index court pour rester compatible MySQL.
- Ajout des champs bancaires sur les clients :
  - Banque ;
  - Numero de compte ;
  - Devise du compte.
- Les champs bancaires clients sont disponibles pour les particuliers et les entreprises.
- Mise a jour du tableau de bord comptabilite pour inclure les fournisseurs reels dans la repartition des contacts.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000003_add_bank_and_currency_to_accounting_clients_table.php`
- `database/migrations/2026_04_30_000004_create_accounting_suppliers_tables.php`
- `app/Models/AccountingSupplier.php`
- `app/Models/AccountingSupplierContact.php`
- `app/Models/AccountingClient.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/accounting-suppliers.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/js/main/accounting-clients.js`
- `resources/js/main/accounting-suppliers.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `database/exports/erp_database.sql`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le controleur, les nouveaux modeles, les nouvelles migrations, les vues modifiees, les routes et les traductions.
- `node --check` passe sur les JS clients et fournisseurs.
- `php artisan migrate` applique les colonnes bancaires clients et les tables fournisseurs.
- `php artisan test --filter=accounting` passe avec 5 tests et 108 assertions.
- `php artisan test` passe avec 66 tests et 503 assertions.

### 2026-04-30 - Page clients du module comptabilite

Prompt utilisateur :

```text
Nous allons maintenant travailler la page client :
lorsqu'on on accède sur la page client, on un tableau qui affiche la liste des clients (applique le style qu'on utilise pour tous les tableaux).

Nous avons deux type de client (entreprise et particulier)
Pour les clients particuliers nous avons les informations suivantes :
- Nom complet (obligatoire)
- Profession (Non obligatoire)
- Téléphone (Non obligatoire)
- adresse email (Non obligatoire)
- adresse (Non Non obligatoire)

Pour les clients entreprise :
- nom de l'entreprise (obligatoire)
- RCCM (Non obligatoire)
- ID NAT (Non obligatoire)
- NIF (Non obligatoire)
- numéro de compte (Non obligatoire)
- site web (Non obligatoire)

ensuite les informations du ou des contacts, un client de type entreprise peut avoir un ou plusieurs contact. Voici les informations du contact :
- Nom complet (obligatoire)
- fonction ou grade (Non obligatoire)
- departement (Non obligatoire)
- adresse émail (Non obligatoire)
- numero de téléphone (Non obligatoire)
```

Travail applique :

- Ajout des tables `accounting_clients` et `accounting_client_contacts`.
- Ajout des modeles `AccountingClient` et `AccountingClientContact`.
- Ajout de la relation `CompanySite::accountingClients()`.
- Ajout des routes CRUD clients du module comptabilite.
- Ajout de la page `main.modules.accounting-clients` avec le tableau standard, recherche, tri, pagination et actions.
- Ajout d'un formulaire modal create/edit pour les clients particuliers et entreprises.
- Ajout de contacts multiples pour les clients de type entreprise.
- Ajout du lien `Clients` depuis la sidebar du module comptabilite.
- Ajout d'un JS isole `resources/js/main/accounting-clients.js` pour le comportement du formulaire client.
- Ajout des traductions FR/EN liees aux clients.
- Respect des permissions du site pour les actions client : creation, modification et suppression.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_04_30_000001_create_accounting_clients_tables.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingClient.php`
- `app/Models/AccountingClientContact.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-clients.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le controleur, les modeles, la migration et les routes.
- `php artisan test --filter=accounting_clients` passe.
- `php artisan test` passe avec 65 tests et 476 assertions.
- `php artisan migrate` execute et la migration clients est appliquee.
- `database/exports/erp_database.sql` mis a jour avec les tables clients comptabilite et l'entree de migration.
- `php artisan test` passe avec 64 tests et 435 assertions.

### 2026-04-29 - Scroll invisible de la sidebar comptabilite

Prompt utilisateur :

```text
le side barre est scrollable mais le scroll ne dois pas etre visible
```

Correction appliquee :

- La sidebar comptabilite reste scrollable pour les menus longs.
- La barre de scroll est maintenant masquee visuellement sur Firefox, Edge, Chrome et navigateurs WebKit.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-04-29 - Layout fixe du module comptabilite

Prompt utilisateur :

```text
la side barre et la zone à coté doivnt etre fixe
```

Correction appliquee :

- La sidebar du module comptabilite reste fixee sur toute la hauteur du viewport.
- La zone de contenu a droite garde aussi une hauteur fixe de viewport.
- Le contenu a droite dispose de son propre scroll interne si la page depasse la hauteur disponible.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=accounting_module` passe avec 2 tests et 34 assertions.
