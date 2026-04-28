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

Verification :

- `php artisan view:clear` execute.
- `php artisan test --filter=superadmin_can_open_admin_dashboard` passe.

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
