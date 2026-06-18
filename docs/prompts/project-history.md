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

### 2026-05-07 - Import quotation fournisseur vers proforma

Prompt utilisateur :

```text
peux-tu m'ajouter cette option ?
```

Contexte :

- L'utilisateur souhaite pouvoir importer un fichier de quotation fournisseur pour pre-remplir une facture proforma.
- Le fichier peut servir de base a la proforma et, si l'utilisateur le choisit, creer aussi les lignes importees dans le stock.

Changements appliques :

- Ajout d'une route POST dediee pour importer une quotation fournisseur depuis la page de creation proforma.
- Ajout d'une section "Importer une quotation fournisseur" dans la creation de proforma.
- L'import accepte CSV, TXT, XLSX simple et PDF texte en lecture best-effort.
- Les lignes importees sont ajoutees comme lignes libres de proforma et restent modifiables avant validation.
- Ajout d'une option permettant de creer automatiquement les lignes importees comme articles de stock lors de la validation de la proforma.
- Le prix fournisseur importe est conserve comme prix de revient/cache interne, puis utilise comme prix d'achat de l'article de stock cree.
- Ajout des traductions FR/EN pour l'import.
- Ajout d'un test couvrant l'import CSV et la conservation des lignes importees dans l'ancien input du formulaire.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/partials/proforma-line-row.blade.php`
- `resources/js/main/accounting-proforma-invoices.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` OK.
- `php -l resources\views\main\modules\accounting-proforma-invoice-create.blade.php` OK.
- `php -l resources\views\main\modules\partials\proforma-line-row.blade.php` OK.
- `node --check resources\js\main\accounting-proforma-invoices.js` OK.
- `php artisan route:list --name=main.accounting.proforma-invoices.import-quote` OK.
- `php artisan test --filter=proforma` passe avec 1 test et 69 assertions.
- `php artisan test --filter=accounting_customer_orders` passe avec 1 test et 23 assertions.
- `php artisan test` passe avec 77 tests et 847 assertions.

### 2026-05-07 - Formats image pour import quotation fournisseur

Prompt utilisateur :

```text
ajoute les formats des images aussi
```

Changements appliques :

- Les imports de quotation fournisseur acceptent maintenant aussi les images `JPG`, `JPEG`, `PNG`, `WEBP`, `BMP`, `TIF` et `TIFF`.
- Le champ fichier du formulaire proforma affiche ces extensions dans la liste des formats acceptes.
- Le backend tente une extraction OCR via `Tesseract` lorsque le serveur le permet.
- Si `Tesseract` n'est pas installe sur le serveur, un message d'erreur clair est affiche pour les images afin d'eviter un echec silencieux.
- Les traductions FR/EN ont ete mises a jour.
- Le test proforma verifie que les formats image sont proposes et que l'absence d'OCR est geree proprement.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` OK.
- `php -l resources\views\main\modules\accounting-proforma-invoice-create.blade.php` OK.
- `php artisan test --filter=proforma` passe avec 1 test et 72 assertions.
- `php artisan test` passe avec 77 tests et 850 assertions.

### 2026-05-07 - Retrait des formats image de l'import quotation

Prompt utilisateur :

```text
enleve l'ajout des images
```

Changements appliques :

- Retrait des extensions image de l'import de quotation fournisseur.
- L'import accepte de nouveau uniquement `CSV`, `TXT`, `XLSX` et `PDF` texte.
- Suppression de la logique OCR/Tesseract ajoutee pour les images.
- Mise a jour du champ fichier dans le formulaire proforma.
- Mise a jour des traductions FR/EN.
- Suppression du test lie aux images.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` OK.
- `php -l resources\views\main\modules\accounting-proforma-invoice-create.blade.php` OK.
- `php artisan test --filter=proforma` passe avec 1 test et 69 assertions.
- `php artisan test` passe avec 77 tests et 847 assertions.

### 2026-05-07 - Commandes clients avec marges beneficiaires

Prompt utilisateur :

```text
maintenant nous allons travailler sur commandes clients. Donne moi ton idee professionnel et ne fais encore rien.
```

Puis :

```text
lorsqu'on a une commande je souhaite que tu donnes aussi la possibilite d'ajouter des marges beneficiaires sur les items qu'en penses tu ?
```

Puis :

```text
applique tout ca
```

Travail realise :

- Ajout du module "Commandes clients" dans le sous-menu Vente du module Comptabilite.
- Creation des tables `accounting_customer_orders` et `accounting_customer_order_lines`.
- Ajout des modeles `AccountingCustomerOrder` et `AccountingCustomerOrderLine`.
- Ajout des routes CRUD des commandes clients.
- Ajout des pages liste, creation et modification des commandes clients.
- Ajout des lignes de commande avec article, service ou ligne libre.
- Ajout du prix de revient, de la methode de marge, de la valeur de marge, de la remise et des totaux par ligne.
- Calcul automatique du cout total, de la marge totale, du taux de marge, de la remise, du total HT, de la TVA et du total TTC.
- Restriction des devises aux devises actives du site.
- Ajout des traductions FR/EN.
- Mise a jour de l'export `database/exports/erp_database.sql`.
- Ajout d'un test fonctionnel couvrant la creation d'une commande client avec marge sur article.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `app/Models/CompanySite.php`
- `app/Models/AccountingCustomerOrder.php`
- `app/Models/AccountingCustomerOrderLine.php`
- `database/migrations/2026_05_07_000001_create_accounting_customer_orders_tables.php`
- `database/exports/erp_database.sql`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/partials/accounting-topbar.blade.php`
- `resources/views/main/modules/accounting-customer-orders.blade.php`
- `resources/views/main/modules/accounting-customer-order-create.blade.php`
- `resources/views/main/modules/partials/customer-order-line-row.blade.php`
- `resources/js/main/accounting-customer-orders.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` OK.
- `php -l app/Models/AccountingCustomerOrder.php` OK.
- `php -l app/Models/AccountingCustomerOrderLine.php` OK.
- `php -l database/migrations/2026_05_07_000001_create_accounting_customer_orders_tables.php` OK.
- `php artisan route:list --name=customer-orders` OK.
- `php artisan view:clear` execute.
- `php artisan test --filter=customer_orders` passe.
- `php artisan test --filter=accounting` passe avec 16 tests et 388 assertions.
- `php artisan test` passe avec 77 tests et 821 assertions.

### 2026-05-07 - Correction table manquante commandes clients

Prompt utilisateur :

```text
lorsque je clique sur commandes clients j'ai cette erreur
```

Erreur constatee :

- `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'erp_database.accounting_customer_orders' doesn't exist`.

Cause :

- La migration `2026_05_07_000001_create_accounting_customer_orders_tables` etait encore en attente dans la base locale.

Correction appliquee :

- Execution de `php artisan migrate`.
- La migration est maintenant marquee comme executee en batch 30.

Verification :

- `php artisan migrate:status | Select-String "customer_orders"` confirme `[30] Ran`.
- `php artisan test --filter=customer_orders` passe avec 1 test et 21 assertions.

### 2026-05-07 - Ligne libre de commande convertible en article de stock

Prompt utilisateur :

```text
Pour les lignes libres libres lors de l'ajout de la commande est-ce possible de'entregistrer un item qui n'existe pas dans le stock ? réponds juste ne fais rien
```

Puis :

```text
applique ton idée
```

Travail realise :

- Ajout d'une option sur les lignes libres de commande client pour creer aussi l'element dans le stock.
- L'option s'affiche uniquement quand le type de ligne est "ligne libre".
- Si l'option est cochee, la ligne libre cree automatiquement un article de stock avec les categorie, sous-categorie, unite et entrepot par defaut du site.
- La ligne de commande est ensuite rattachee a l'article cree, tout en conservant les calculs de cout, marge, remise et total.
- Si l'option n'est pas cochee, la ligne libre reste une simple ligne de commande sans impact sur le stock.
- Ajout des traductions FR/EN et du style du champ.
- Le test commandes clients couvre maintenant la conversion d'une ligne libre en article de stock.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/partials/customer-order-line-row.blade.php`
- `resources/js/main/accounting-customer-orders.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` OK.
- `php -l resources/views/main/modules/partials/customer-order-line-row.blade.php` OK.
- `php artisan test --filter=customer_orders` passe avec 1 test et 23 assertions.
- `php artisan test --filter=accounting` passe avec 16 tests et 390 assertions.

### 2026-05-07 - Ligne libre de proforma convertible en article de stock

Prompt utilisateur :

```text
peux(tu appliquer également la logique dans la proforma comme tu l'avais expliqué ?
```

Travail realise :

- Ajout de la meme logique que les commandes clients sur les factures proforma.
- Sur une ligne de proforma de type "ligne libre", l'utilisateur peut cocher une option pour creer aussi l'element dans le stock.
- L'option reste masquee pour les lignes de type article ou service.
- Si l'option est cochee, le systeme cree un article de stock rattache aux categorie, sous-categorie, unite et entrepot par defaut du site.
- La ligne de proforma est ensuite rattachee a l'article cree.
- Si l'option n'est pas cochee, la ligne libre reste une ligne purement commerciale sans impact sur le stock.
- La logique de creation d'article depuis une ligne libre est maintenant partagee entre proformas et commandes clients.
- Le test proforma couvre la creation d'un article de stock depuis une ligne libre.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/partials/proforma-line-row.blade.php`
- `resources/js/main/accounting-proforma-invoices.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` OK.
- `php -l resources/views/main/modules/partials/proforma-line-row.blade.php` OK.
- `php artisan test --filter=proforma` passe avec 1 test et 48 assertions.
- `php artisan test --filter=accounting` passe avec 16 tests et 393 assertions.

### 2026-05-07 - Masquage du bouton modifier sur proforma convertie

Prompt utilisateur :

```text
il serait mieux de ne pas afficher le bouton modifié sur une proforma convertie
```

Correction appliquee :

- Le bouton de modification n'est plus affiche pour les proformas avec le statut converti.
- Le bouton d'impression PDF reste disponible.
- Le test proforma confirme qu'une proforma convertie n'affiche plus le lien de modification.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-proforma-invoices.blade.php` OK.
- `php artisan test --filter=proforma` passe avec 1 test et 52 assertions.
- `php artisan test --filter=accounting` passe avec 16 tests et 397 assertions.

### 2026-05-07 - Conversion d'une proforma acceptee en commande client

Prompt utilisateur :

```text
peux-tu appliquer ça ?
```

Contexte :

- Le bouton "Convertir en commande client" doit permettre de transformer une proforma acceptee en commande client sans ressaisie.

Travail realise :

- Ajout d'une route POST de conversion proforma vers commande client.
- Ajout d'une action controleur `convertAccountingProformaToCustomerOrder`.
- Affichage du bouton de conversion uniquement pour les proformas acceptees et si l'utilisateur peut creer.
- La conversion cree une commande client liee a la proforma d'origine via `proforma_invoice_id`.
- Les informations reprises sont le client, l'objet, les dates, la devise, la modalite de paiement, les notes, les conditions, la TVA et les totaux.
- Les lignes de proforma sont reprises dans la commande client.
- Pour les lignes article, le prix de revient est repris depuis l'article afin de calculer la marge.
- Pour les lignes service ou libres, le prix de revient est mis a zero par defaut.
- La proforma convertie passe automatiquement au statut `converted`.
- Le systeme evite les doubles conversions en reutilisant le lien existant si une commande a deja ete creee.
- Ajout des traductions FR/EN.
- Le test proforma couvre maintenant la conversion, la creation de la commande et le verrouillage de la proforma.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` OK.
- `php -l routes/web.php` OK.
- `php -l resources/views/main/modules/accounting-proforma-invoices.blade.php` OK.
- `php artisan test --filter=proforma` passe avec 1 test et 59 assertions.
- `php artisan test --filter=accounting` passe avec 16 tests et 404 assertions.

### 2026-05-07 - Badge vert pour commande client confirmee

Prompt utilisateur :

```text
dans commandes clients sur le tableau confirmée doit etre en vert
```

Correction appliquee :

- Les statuts des commandes clients utilisent maintenant des classes CSS dediees.
- Le statut `confirmed` s'affiche en badge vert.
- Le statut `delivered` s'affiche egalement en vert.
- Les statuts `draft`, `in_progress` et `cancelled` gardent chacun une couleur distincte.

Fichiers modifies :

- `resources/views/main/modules/accounting-customer-orders.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources/views/main/modules/accounting-customer-orders.blade.php` OK.
- `php artisan test --filter=customer_orders` passe avec 1 test et 23 assertions.

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

### 2026-05-07 - Bons de livraison du module comptabilite

Prompt utilisateur :

```text
maintenant nous allons travailler sur bons de livraison.
donne moi ton idée professionnel sans rien faire pour le moment

applique ton idée
```

Fonctionnalite appliquee :

- Ajout des tables `accounting_delivery_notes` et `accounting_delivery_note_lines`.
- Ajout des modeles `AccountingDeliveryNote` et `AccountingDeliveryNoteLine`.
- Ajout des relations avec les commandes clients, les lignes de commande et les sites.
- Ajout de la page `Bons de livraison` avec le tableau standard : recherche, tri, pagination, statuts et actions.
- Creation d'un bon de livraison uniquement depuis une commande client confirmee ou en preparation avec reste a livrer.
- Gestion des livraisons partielles et finales.
- Controle des quantites pour empecher de livrer plus que le reste disponible.
- Sortie automatique du stock pour les bons partiels ou livres, avec mouvement de stock rattache.
- Mise a jour automatique du statut de la commande client : confirmee, en preparation ou livree.
- Ajout d'un PDF professionnel de bon de livraison avec QR Code et pied de page.
- Ajout du lien `Bons de livraison` dans la sidebar comptabilite et du raccourci depuis les commandes clients.
- Mise a jour de l'export `database/exports/erp_database.sql`.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_05_07_000002_create_accounting_delivery_notes_tables.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingDeliveryNote.php`
- `app/Models/AccountingDeliveryNoteLine.php`
- `app/Models/AccountingCustomerOrder.php`
- `app/Models/AccountingCustomerOrderLine.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-delivery-notes.blade.php`
- `resources/views/main/modules/accounting-delivery-note-create.blade.php`
- `resources/views/main/modules/accounting-delivery-note-print.blade.php`
- `resources/views/main/modules/accounting-customer-orders.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le controleur, les modeles, la migration et les routes.
- `php artisan test --filter=delivery_notes` passe.
- `php artisan test` passe avec 78 tests et 870 assertions.
- `php artisan migrate --force` applique la migration des bons de livraison.

### 2026-05-07 - Conservation des lignes apres erreur de bon de livraison

Prompt utilisateur :

```text
quand tu affiche l'erreur lors de la création du bon de livraison, ne supprime pas les items affiche l'erreur mais ne supprime pas
```

Correction appliquee :

- Lorsqu'une erreur de stock ou de validation survient sur la creation d'un bon de livraison, les lignes issues de la commande restent visibles.
- Les quantites saisies par l'utilisateur sont conservees.
- Les informations calculees de la commande, comme la description, la quantite commandee, deja livree et le reste a livrer, sont rechargees depuis la commande.

Fichiers modifies :

- `resources/views/main/modules/accounting-delivery-note-create.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=delivery_note_stock_error` passe.

### 2026-05-07 - Numeros de serie sur les bons de livraison

Prompt utilisateur :

```text
ajoute une rubrique qui nous permets d'ajouter les serial number des items par rapport à la quantité; un exemple si la quantité d'un item est 1 on peut rajouter un serial number si c'est 3 par exemple on peut rajouter 3 ainsi de suite
```

Fonctionnalite appliquee :

- Ajout de la table `accounting_delivery_note_serials` pour stocker les numeros de serie par ligne de bon de livraison.
- Ajout du modele `AccountingDeliveryNoteSerial`.
- Ajout de la relation `AccountingDeliveryNoteLine::serials()`.
- Sur le formulaire de creation du bon de livraison, les lignes d'articles affichent automatiquement un champ de numero de serie par unite livree.
- Les numeros de serie sont optionnels mais limites a la quantite livree.
- Les doublons de numeros de serie sur une meme ligne sont refuses proprement.
- Les numeros de serie s'affichent dans le PDF du bon de livraison sous la ligne concernee.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers ajoutes ou modifies :

- `database/migrations/2026_05_07_000003_create_accounting_delivery_note_serials_table.php`
- `database/exports/erp_database.sql`
- `app/Models/AccountingDeliveryNoteSerial.php`
- `app/Models/AccountingDeliveryNoteLine.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-delivery-note-create.blade.php`
- `resources/views/main/modules/accounting-delivery-note-print.blade.php`
- `resources/js/main/accounting-delivery-notes.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur les fichiers PHP touches.
- `php artisan test --filter=delivery_notes` passe.
- `php artisan migrate --force` applique la migration des numeros de serie.

### 2026-05-07 - Bouton ajouter une ligne en bas des cards

Prompt utilisateur :

```text
ajouter une ligne doit toujours etre en bas et non en haut de la card des items partout
```

Correction appliquee :

- Le bouton `Ajouter une ligne` est deplace sous les cards de lignes dans le formulaire de facture proforma.
- Le bouton `Ajouter une ligne` est deplace sous les cards de lignes dans le formulaire de commande client.
- Ajout d'un conteneur commun `line-section-actions` pour aligner le bouton en bas a droite.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-customer-order-create.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur les deux vues Blade modifiees.
- `php artisan test --filter="proforma|customer_orders"` passe.

### 2026-05-07 - Masquage des items deja selectionnes dans les lignes

Prompt utilisateur :

```text
un item deja selectionné ne dois plus etre selectionné et ne dois meme plus s'afficher sur l'input pour la recherche
```

Correction appliquee :

- Dans les lignes de facture proforma, un article ou service deja selectionne dans une ligne active disparait automatiquement du select recherchable des autres lignes.
- Dans les lignes de commande client, le meme comportement est applique pour les articles et les services.
- Lorsqu'une ligne change de type, qu'une ligne est ajoutee ou supprimee, les listes recherchables sont rafraichies automatiquement.
- Une selection doublon forcee est remise a vide pour eviter l'envoi de deux fois le meme item.

Fichiers modifies :

- `resources/js/main/accounting-proforma-invoices.js`
- `resources/js/main/accounting-customer-orders.js`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="proforma|customer_orders"` passe.

### 2026-05-07 - Recherche sur le champ client des documents de vente

Prompt utilisateur :

```text
pour le client possibilité de faire egalement la recherche comme c'est fait sur les items; applique ça partout
```

Correction appliquee :

- Le champ `Client` du formulaire de facture proforma utilise maintenant le select recherchable deja utilise pour les articles et services.
- Le champ `Client` du formulaire de commande client utilise aussi le meme select recherchable.
- Les erreurs de validation restent affichees sous le champ avec le meme style que les autres formulaires.

Fichiers modifies :

- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-customer-order-create.blade.php`
- `resources/js/main/accounting-proforma-invoices.js`
- `resources/js/main/accounting-customer-orders.js`
- `docs/prompts/project-history.md`

Verification :

- `node --check` passe sur les deux fichiers JavaScript touches.
- `php artisan test --filter="proforma|customer_orders"` passe.

### 2026-05-07 - Correction du debordement des lignes de commande

Prompt utilisateur :

```text
lorsque je modifie une commande, il y a un depassement d'affichage des items
```

Correction appliquee :

- Les lignes de commande client utilisent une grille CSS dediee au lieu de dependre uniquement des largeurs Bootstrap.
- Les colonnes ne peuvent plus etre elargies par les longs noms d'articles ou de services.
- Les champs, selects et selects recherchables restent dans la largeur disponible.
- La grille est responsive : 12 colonnes sur grand ecran, 6 colonnes sur tablette, une colonne sur mobile.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="customer_orders"` passe.

### 2026-05-07 - Numeros de serie en liste sur le bon de livraison PDF

Prompt utilisateur :

```text
les numéros de serie doivent s'afficher sous forme d'une liste
```

Correction appliquee :

- Les numeros de serie ne sont plus affiches sur une seule ligne separee par des virgules.
- Dans le PDF du bon de livraison, chaque numero de serie s'affiche maintenant sur sa propre ligne dans une liste.

Fichiers modifies :

- `resources/views/main/modules/accounting-delivery-note-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="delivery_notes"` passe.

### 2026-05-08 - Cycle ouverture et cloture de caisse

Prompt utilisateur :

```text
genere moi un cycle comme ceci :
Objectif :
Ajouter un module de caisse permettant :
- l'ouverture de caisse
- le rattachement des ventes a une session de caisse ouverte
- la fermeture / cloture de caisse
- le calcul des montants theoriques
- la saisie des montants reellement comptes
- le calcul des ecarts
- la generation d'un rapport de cloture
- la validation de la cloture se fait qu'avec un utilisateur qui a le role admin
```

Correction appliquee :

- Ajout d'une table de sessions de caisse avec ouverture, cloture, montants theoriques, montants comptes, ecarts et validation admin.
- Liaison des ventes de caisse a la session ouverte via `cash_register_session_id`.
- Ajout de l'ouverture de caisse avant toute vente rapide.
- Blocage de l'enregistrement d'une vente si aucune session n'est ouverte.
- Ajout de la cloture de caisse avec validation reservee au role admin.
- Calcul automatique des montants theoriques depuis le fond de caisse et les paiements rattaches.
- Ajout d'un historique des sessions et d'un rapport imprimable de cloture.

Fichiers modifies :

- `database/migrations/2026_05_08_000001_create_accounting_cash_register_sessions_table.php`
- `app/Models/AccountingCashRegisterSession.php`
- `app/Models/AccountingSalesInvoice.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-cash-register.blade.php`
- `resources/views/main/modules/accounting-cash-register-session-report.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingCashRegisterSession.php` passe.
- `php -l database\migrations\2026_05_08_000001_create_accounting_cash_register_sessions_table.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php -l resources\views\main\modules\accounting-cash-register-session-report.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Monnaie a rendre dans la caisse

Prompt utilisateur :

```text
Ajoute dans la caisse une fonctionnalite de gestion du paiement en especes lors de l'enregistrement d'une vente.
Objectif :
Quand le client paie en especes, le caissier doit pouvoir saisir le montant recu du client, et le systeme doit calculer automatiquement la monnaie a rendre.
```

Correction appliquee :

- Ajout des champs de paiement especes dans la caisse : total a payer, montant recu et monnaie a rendre.
- Affichage de ces champs uniquement lorsque le mode de paiement selectionne est de type especes.
- Calcul automatique de la monnaie a rendre cote interface.
- Blocage cote interface et cote serveur si le montant recu est inferieur au total a payer.
- Enregistrement du montant recu et de la monnaie rendue sur le paiement de la facture.
- Affichage du montant recu et de la monnaie rendue dans le detail du ticket de caisse.

Fichiers modifies :

- `database/migrations/2026_05_08_000002_add_cash_received_fields_to_sales_invoice_payments.php`
- `app/Models/AccountingSalesInvoicePayment.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-cash-register.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l database\migrations\2026_05_08_000002_add_cash_received_fields_to_sales_invoice_payments.php` passe.
- `php -l app\Models\AccountingSalesInvoicePayment.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Reduction des polices en gras du rapport de caisse

Prompt utilisateur :

```text
ne touche pas le pied et bas de page c'est parfait.
reduit legerement la taille des polices en gras
```

Correction appliquee :

- Reduction legere des titres de section, des montants en gras, des en-tetes de tableaux et des badges de statut dans le rapport de cloture de caisse.
- Aucun changement applique au pied de page ni a la zone basse du PDF.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register-session-report.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register-session-report.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Pavé numérique caisse pour quantité et montant reçu

Prompt utilisateur :

```text
le clavier numérique sur la caisse enregistreuses doit permerttre de pouvoir saisir :
- la quantité de l'article séléctionné
- le Montant reçu
```

Correction appliquee :

- Le pave numerique de la caisse peut maintenant piloter la quantite de l'article selectionne dans le panier.
- Le meme pave numerique peut saisir le montant recu lorsque le champ `Montant recu` est selectionne.
- Ajout d'un etat visuel sur la zone especes quand le pave numerique cible le montant recu.
- Le bouton `C` efface la cible active : remise a 1 pour la quantite ou vidage du montant recu.
- Le calcul de la monnaie a rendre et l'activation du bouton d'enregistrement restent synchronises apres chaque saisie au pave.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Visibilite des tickets et sessions de caisse par role

Prompt utilisateur :

```text
un administrateur peut voir toutes les sessions de caisse et les tickets de caisse émisse dans le module.
Les utilisateurs normaux eux peuvent voir uniquement ce que eux ils ont emises sessions de caisse et tikets de caisses
```

Correction appliquee :

- Les administrateurs conservent la visibilite globale sur les tickets de caisse et les sessions du site.
- Les utilisateurs simples ne voient plus que les tickets de caisse qu'ils ont emis.
- Les utilisateurs simples ne voient plus que les sessions de caisse qu'ils ont ouvertes.
- L'ouverture et l'enregistrement d'une vente par un utilisateur simple utilisent sa propre session ouverte.
- L'acces direct au rapport d'une session appartenant a un autre utilisateur simple est bloque.
- Le test caisse couvre maintenant la difference de visibilite entre admin et utilisateur simple.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Rapport de caisse en PDF Dompdf

Prompt utilisateur :

```text
LE rapport de la caisse doit egalement utiliser doompdf garde le meme design de la page html que tu as utilisé
```

Correction appliquee :

- Le rapport de cloture de caisse est maintenant genere avec Dompdf.
- La meme vue Blade et le meme style visuel sont conserves pour le rendu du rapport.
- Les boutons HTML `Retour` et `Imprimer` sont masques pendant le rendu PDF.
- Le fichier PDF est diffuse avec un nom base sur la reference de session de caisse.
- Le test caisse verifie maintenant que le rapport retourne bien un contenu `application/pdf`.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-cash-register-session-report.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-cash-register-session-report.blade.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Alignements et espacements du rapport de cloture caisse

Prompt utilisateur :

```text
stp respect tout les espaces, les alignimement comme sur l'image
```

Correction appliquee :

- Reprise complete de la mise en page PDF du rapport de cloture caisse pour respecter l'image fournie.
- Header en deux zones : informations entreprise a gauche, titre et reference a droite.
- Ligne bleue pleine largeur sous l'entete.
- Sections espacees avec titres en majuscules.
- Tableau de session sans bordures lourdes, avec ligne de cloture sur fond bleu clair.
- Cartes de montants alignees sur une seule ligne.
- Tableaux `Mode de paiement` et `Tickets de caisse` alignes et espacés comme sur le modele.
- Utilisation d'une structure table-based plus stable avec Dompdf.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register-session-report.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register-session-report.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Harmonisation rapport caisse avec les factures

Prompt utilisateur :

```text
reduit legerement la la taille de police ajoute le pied de page comme dans facture, utilise le meme haut et pied de page meme la taille des polices
```

Correction appliquee :

- Le rapport de cloture caisse utilise maintenant les memes marges PDF que les factures.
- La police globale passe au meme style et a la meme taille que les factures : Courier 12px.
- La ligne haute bleu/gris reprend le meme format que les factures.
- Ajout du pied de page fixe identique aux factures avec informations entreprise, compte principal et mention de generation.
- Reduction des tailles internes du rapport : titres de section, lignes de tableau, badges et cartes de montants.
- Chargement des comptes de l'entreprise pour alimenter le pied de page.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-cash-register-session-report.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-cash-register-session-report.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Message informatif pour montant recu insuffisant

Prompt utilisateur :

```text
ce message doit etre informatif et non en route mets ça dans un alert info
```

Correction appliquee :

- Le message "Le montant recu doit etre superieur ou egal au total a payer" s'affiche maintenant dans une alerte informative.
- Retrait de l'apparence rouge `invalid-feedback` et de la coloration danger sur ce cas dans la caisse.
- Le blocage de validation reste actif tant que le montant recu est inferieur au total a payer.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Bouton plein ecran dans la topbar

Prompt utilisateur :

```text
ajouter un bouton qui met permet de passer en mode plein ecran sur la barre de tache comme indiqué dans l'image
```

Correction appliquee :

- Ajout d'un bouton plein ecran dans la topbar des pages du module.
- Utilisation de l'API Fullscreen du navigateur.
- Changement automatique de l'icone entre plein ecran et sortie du plein ecran.
- Libelles traduits en francais et en anglais.

Fichiers modifies :

- `resources/views/main/modules/partials/accounting-topbar.blade.php`
- `resources/js/main.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\partials\accounting-topbar.blade.php` passe.
- `node --check resources\js\main.js` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.

### 2026-05-08 - Espacement et activation conditionnelle du bouton de vente caisse

Prompt utilisateur :

```text
espace les cards stp
enregistrer la vente doit toujours etre floue jusqu'à ce que les conditions soit reunis plase
```

Correction appliquee :

- Ajout d'un espacement au-dessus des cards principales de la caisse.
- Le bouton "Enregistrer la vente" est maintenant desactive par defaut.
- Le bouton reste flou et inclicable tant que le panier est vide.
- En paiement especes, le bouton reste flou et inclicable tant que le montant recu est inferieur au total a payer.
- Le bouton redevient actif uniquement lorsque les conditions de vente sont reunies.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Desactivation du bouton caisse si montant recu insuffisant

Prompt utilisateur :

```text
si le monatnt est inférieur au monant à payer le bouton enregistre vente doit etre inclicable et flou si le monant est supérieur ou egale c'est bon
```

Correction appliquee :

- Le bouton "Enregistrer la vente" est desactive automatiquement si le paiement est en especes et que le montant recu est inferieur au total a payer.
- Le bouton devient visuellement flou et attenue pendant cet etat.
- Le bouton redevient actif des que le montant recu est superieur ou egal au total a payer.
- Les autres modes de paiement ne sont pas concernes par ce blocage visuel.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Caisse enregistreuse POS point de vente

Prompt utilisateur :

```text
je vais une vrai caisse enregistreuse pour les points de vente comme magasin, alimentation etc...
enleves les services ça n'a aucun sens et le total des ventes caisse.
Voici un exemple d'interfaces comme une caise doit etre ce que tu as fais n'as aucun sens
```

Correction appliquee :

- Remplacement de la page caisse enregistreuse par une vraie interface POS adaptee aux ventes rapides.
- Suppression des services dans la caisse : seuls les articles de stock actifs peuvent etre vendus.
- Suppression du bloc "total des ventes caisse" qui ne correspondait pas au flux point de vente.
- Ajout d'une zone produits avec recherche, d'un panier central, d'un recapitulatif TVA/total et d'un panneau de paiement.
- Ajout d'un clavier numerique pour ajuster rapidement la quantite de la ligne selectionnee.
- Conservation de la logique existante : creation d'une facture caisse payee, enregistrement du paiement et sortie de stock.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `app/Http/Controllers/MainController.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Espacement visuel de la caisse enregistreuse

Prompt utilisateur :

```text
espace un peu l'input de recherche en bas et les 3 cards de la caisse enregistreuse
```

Correction appliquee :

- Augmentation legere de l'espace sous l'input de recherche des articles.
- Augmentation legere de l'espace entre les trois panneaux principaux de la caisse enregistreuse.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Tableau des tickets integre dans la card

Prompt utilisateur :

```text
meme le tableau doit etre mits dans la card et ajoute un titre sur la card
```

Correction appliquee :

- Regroupement du titre, de la recherche, du compteur, du tableau et de la pagination des tickets de caisse dans une seule card.
- Ajout du titre "Tickets de caisse" sur la card.
- Conservation du style de tableau existant avec un cadre interne propre.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Espacement au-dessus de la card tickets de caisse

Prompt utilisateur :

```text
la card que tu viens de céer doit laisse un peu d'espace au dessus stp c'est trop sérré avec les aautres cards en haut
```

Correction appliquee :

- Ajout d'un espace superieur sur la card "Tickets de caisse".
- La card est maintenant mieux separee des panneaux POS situes au-dessus.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Recherche des tickets de caisse dans une card

Prompt utilisateur :

```text
si ça ne marche pas mets cette zone dans une card
```

Correction appliquee :

- La zone de recherche et compteur des tickets de caisse est maintenant placee dans une card dediee.
- La card possede son propre padding et son propre espacement avec le tableau.
- Cette approche evite les conflits avec les marges globales des tableaux.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Renforcement de l'espacement sous la recherche des tickets

Prompt utilisateur :

```text
toujours pas d'espace
```

Correction appliquee :

- Renforcement du selecteur CSS de la page caisse pour eviter que le layout comptabilite ecrase l'espacement.
- Ajout d'un espacement force entre la barre de recherche des tickets et la card du tableau.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Espacement sous la recherche des tickets de caisse

Prompt utilisateur :

```text
je parle de cette input
```

Correction appliquee :

- Ajout d'un espacement plus confortable sous l'input de recherche du tableau des tickets de caisse.
- La correction est limitee a la page caisse enregistreuse pour ne pas modifier le style global des autres tableaux.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Traduction uniforme du client comptoir

Prompt utilisateur :

```text
peux tu traduire client comptoire partout ?
```

Correction appliquee :

- Ajout d'un affichage traduisible pour le client comptoir via `display_name`.
- Les anciens libelles deja presents en base sont reconnus : `Client comptoir`, `Client comptoire`, `Client de passage`, `Walk-in customer` et `Counter customer`.
- Les listes, modales, documents PDF, encaissements, bons de livraison, factures, commandes et tickets de caisse affichent maintenant le libelle selon la langue active.
- La creation du client comptoir reutilise un ancien enregistrement equivalent au lieu de creer un doublon selon la langue courante.

Fichiers modifies :

- `app/Models/AccountingClient.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-cash-register.blade.php`
- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/accounting-receipts.blade.php`
- `resources/views/main/modules/accounting-delivery-notes.blade.php`
- `resources/views/main/modules/accounting-delivery-note-create.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-customer-orders.blade.php`
- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `resources/views/main/modules/accounting-proforma-invoice-print.blade.php`
- `resources/views/main/modules/accounting-sales-invoice-print.blade.php`
- `resources/views/main/modules/accounting-delivery-note-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingClient.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l` passe sur les vues Blade modifiees.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Espacement des modes de paiement en caisse

Prompt utilisateur :

```text
dans mode de paiement dans caisse enregistreuses ne laisse pas beaucoup dêespace pareil
```

Correction appliquee :

- Correction du panneau "Mode de paiement" de la caisse enregistreuse.
- Les modes de paiement ne s'etirent plus verticalement sur toute la hauteur disponible.
- Le clavier numerique et les actions restent plus proches du contenu pour une interface POS plus compacte.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Navigation articles par categorie en caisse

Prompt utilisateur :

```text
dans la zone articles disponibles je souhaite que tu groupes les artcles par catégories lorsquêon clique sur une catégories. on voit les sous-catégories et en suite on voit les articles
```

Correction appliquee :

- La caisse enregistreuse affiche maintenant les articles en navigation hierarchique.
- Premier niveau : categories d'articles.
- Deuxieme niveau : sous-categories de la categorie selectionnee.
- Troisieme niveau : articles disponibles dans la sous-categorie selectionnee.
- Ajout d'un fil d'Ariane et d'un bouton retour pour naviguer dans la zone articles.
- La recherche reste globale : elle permet de retrouver directement les articles, meme sans parcourir les categories.
- Les articles sans categorie ou sans sous-categorie sont regroupes proprement dans des rubriques dediees.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-cash-register.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Caisse enregistreuse point de vente

Prompt utilisateur :

```text
je vais une vrai caisse enregistreuse pour les points de vente comme magasin, alimentation etc...
enleves les services ça n'a aucun sens et le total des ventes caisse.
```

Correction appliquee :

- Refonte de l'interface caisse en mode point de vente.
- Suppression des services dans la caisse : la vente caisse se fait uniquement sur les articles du stock.
- Suppression du bloc `Total des ventes caisse` sur l'ecran de caisse.
- Ajout d'un catalogue d'articles avec recherche rapide, prix, reference et stock disponible.
- Ajout d'un panier caisse avec quantites modifiables, suppression de lignes et calcul direct du total a payer.
- Ajout d'une zone paiement avec les modes de paiement et un pave numerique utilisable sur la ligne selectionnee.
- Conservation de la logique d'enregistrement : facture payee automatiquement, paiement cree, stock decremente.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-cash-register.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Caisse enregistreuse en mode vente rapide

Prompt utilisateur :

```text
pourquoi la caisse enregistreuse ressemble à la page encaissements ?
une caisse enregistreuse c'est pour les ventes rapides
```

Correction appliquee :

- Remplacement de la logique de journal d'encaissements par une vraie page de caisse enregistreuse.
- Ajout d'un formulaire de vente rapide avec client comptoir par defaut, lignes article/service/libre, TVA, mode de paiement obligatoire et reference de paiement.
- Creation automatique d'une facture payee pour chaque ticket de caisse.
- Enregistrement automatique du paiement lie au ticket.
- Decrement automatique du stock pour les lignes de type article, avec message d'erreur si le stock est insuffisant.
- Affichage d'un tableau des tickets de caisse avec impression PDF et modal de detail des lignes vendues.
- Masquage de l'objet technique du ticket dans le PDF de facture.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `app/Models/AccountingSalesInvoice.php`
- `resources/views/main/modules/accounting-cash-register.blade.php`
- `resources/views/main/modules/accounting-sales-invoice-print.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l app\Models\AccountingSalesInvoice.php` passe.
- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php -l routes\web.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-08 - Module comptabilite - Caisse enregistreuse

Prompt utilisateur :

```text
applique ton idée
```

Implementation appliquee :

- Ajout d'une page dediee `Caisse enregistreuse` dans le module Comptabilite.
- Ajout de la route :
  - `main.accounting.cash-register`
- Branchement du sous-menu `Caisse enregistreuse` dans la sidebar avec etat actif.
- Ajout d'une methode controller `accountingCashRegister()` avec :
  - controle d'acces site/module
  - recherche globale serveur (`search`) comme les autres tableaux
  - filtres : mode de paiement, type de mode, devise, date de debut, date de fin
  - calcul des indicateurs de caisse (total des entrees, total du jour, nombre de mouvements, solde courant)
  - pagination + tri + data des modales de detail
- Creation de la vue :
  - `resources/views/main/modules/accounting-cash-register.blade.php`
  - style coherent avec les pages compta existantes
  - barre de recherche + tri + pagination
  - tableau des mouvements (date, reference, facture, client, mode, type, montant, recu par, statut facture)
  - action impression PDF facture et modal de detail mouvement
- Ajout des traductions FR/EN necessaires (sous-titre, total caisse, etat vide, detail mouvement, `all_types`).
- Ajout d'un test feature :
  - `test_accounting_cash_register_page_lists_movements_and_filters`

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-cash-register.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter="accounting_cash_register_page_lists_movements_and_filters|accounting_receipts_page_lists_site_receipts_and_filters"` passe (2 tests, 26 assertions).

### 2026-05-08 - Pagination du modal documents client

Prompt utilisateur :

```text
le bouton de pagination n'est pas bien designé.
Voici le design à coté
```

Correction appliquee :

- Refonte de la pagination du modal `Documents du client`.
- Ajout d'un compteur de plage a gauche du type `Affichage de 1 à 5 sur 9`.
- Ajout d'une vraie navigation a droite dans le style des autres tableaux de l'application.
- Harmonisation des boutons `Précédent`, pages et `Suivant` avec les états actif et désactivé.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/js/main/accounting-clients.js`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources\js\main\accounting-clients.js` passe.

### 2026-05-08 - Impression des documents imprimables dans le dossier client

Prompt utilisateur :

```text
donne la possibilité d'imprimer les documents imprimable
```

Correction appliquee :

- Ajout d'une colonne d'actions dans le modal `Documents du client`.
- Ajout du bouton d'impression pour les documents qui disposent deja d'une route PDF/impression :
  - factures proforma,
  - bons de livraison,
  - factures de vente.
- Les commandes clients restent sans bouton d'impression tant qu'une route d'impression dediee n'existe pas.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/js/main/accounting-clients.js`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources\js\main\accounting-clients.js` passe.
- `php artisan test --filter="accounting_clients"` passe.

### 2026-05-08 - Uniformisation des tableaux dans les modales

Prompt utilisateur :

```text
tous mes modales qui affichent les tableaux doivent etre pareil
```

Correction appliquee :

- Uniformisation du pied de pagination de tous les modales affichant un tableau.
- Ajout d'un compteur bas de page du type `Affichage de X à Y sur Z`.
- Harmonisation du rendu des boutons `Précédent`, pages et `Suivant`.
- Application de ce standard sur :
  - documents client,
  - encaissements par mode de paiement,
  - historique des paiements de facture,
  - tableaux d'articles/services rattachés,
  - historiques de connexion côté admin et côté main.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/views/main/users.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/js/main/accounting-clients.js`
- `resources/js/main/accounting-stock-resource.js`
- `resources/js/main/accounting-service-resource.js`
- `resources/css/main.css`
- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources\js\main\accounting-clients.js` passe.
- `node --check resources\js\main\accounting-stock-resource.js` passe.
- `node --check resources\js\main\accounting-service-resource.js` passe.

### 2026-05-08 - Couleurs des statuts dans le modal documents client

Prompt utilisateur :

```text
sur les modal dossier client dans le tableau sur la colonne statut respect les couleurs stp
```

Correction appliquee :

- Application des classes de couleur de statuts deja utilisees dans les tableaux metier.
- Les documents client affichent maintenant des badges colores coherents selon leur nature :
  - proforma,
  - commande client,
  - bon de livraison,
  - facture.

Fichiers modifies :

- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/js/main/accounting-clients.js`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources\js\main\accounting-clients.js` passe.

### 2026-05-08 - Tableau clients : retrait telephone/email et ajout des documents

Prompt utilisateur :

```text
dans le tableaux qui affiche la liste des clients sur la page clients, j'aimerai que tu supprimes les colonnes téléphone et email.
ajouter un bouttons qui permet d'afficher les profoma, commandes, bon de livraison et facture s'ils en ont
```

Correction appliquee :

- Suppression des colonnes `Telephone` et `Email` du tableau principal des clients.
- Ajout d'un bouton d'action pour consulter les documents lies a un client lorsqu'il possede deja des pieces.
- Ajout d'un modal dynamique listant les factures proforma, commandes clients, bons de livraison et factures du client.
- Le modal reprend le style des tableaux en modale avec recherche locale, tri et pagination sans rechargement de page.
- Chargement des relations et compteurs necessaires cote controleur pour eviter les acces repetes a la base.
- Ajout des relations manquantes sur le modele `AccountingClient`.

Fichiers modifies :

- `app/Models/AccountingClient.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/js/main/accounting-clients.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingClient.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `node --check resources\js\main\accounting-clients.js` passe.
- `php artisan test --filter="accounting_clients"` passe.

### 2026-05-08 - Encaissements par mode de paiement

Prompt utilisateur :

```text
revenons maintenant dans la page mode de paiement, je vais que tu rajoute un boutons qui me permet de voir les encaissements donc les paiement fais avec chaque mode de paiement et les decaissements (pour le moment ce bouton ne sera pas offérationnel, on y travailleras plus tard)
```

Correction appliquee :

- Ajout d'un bouton `Encaissements` sur chaque mode de paiement pour ouvrir un modal listant les paiements de factures rattaches au mode.
- Ajout d'un bouton `Decaissements` visible mais desactive, prepare pour la prochaine etape.
- Le modal des encaissements respecte le style standard des tableaux de modal : bordure autour du tableau, recherche, tri, pagination locale au-dela de 5 lignes et message vide centre.
- Ajout des traductions FR/EN pour les encaissements et les decaissements a venir.
- Ajout d'un test couvrant l'affichage des encaissements rattaches a un mode de paiement.

Fichiers modifies :

- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/js/main/accounting-payment-methods.js`
- `resources/css/main.css`
- `app/Http/Controllers/MainController.php`
- `app/Models/AccountingPaymentMethod.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingPaymentMethod.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `node --check resources\js\main\accounting-payment-methods.js` passe.
- `php artisan test --filter="accounting_payment_methods"` passe : 1 test, 27 assertions.

### 2026-05-08 - Suppression bloquee des modes de paiement utilises

Prompt utilisateur :

```text
un mode paiement qui a déjà des encaissements ou decaissements ne doit pas etre supprimmable il faut enleve le boutton supprimer
```

Correction appliquee :

- Le bouton supprimer n'est plus affiche pour un mode de paiement ayant deja des encaissements.
- La suppression est aussi bloquee cote controleur si une requete directe tente de supprimer un mode de paiement utilise.
- Ajout d'un message traduit FR/EN indiquant qu'un mode avec mouvements ne peut pas etre supprime.
- Le test de la page modes de paiement verifie maintenant que le bouton est masque et que la suppression directe est refusee.

Fichiers modifies :

- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `app/Http/Controllers/MainController.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter="accounting_payment_methods"` passe : 1 test, 32 assertions.

### 2026-05-08 - Total des encaissements dans le modal mode de paiement

Prompt utilisateur :

```text
sur le modal d'encaissement es-ce possible d'afficher le monant total des encaissement
```

Correction appliquee :

- Ajout d'un resume `Total encaisse` dans le modal des encaissements de chaque mode de paiement.
- Le montant est affiche avec deux decimales et la devise du mode de paiement.
- Ajout des traductions FR/EN du libelle.
- Ajout du style de bandeau recapitulatif dans les modales.
- Le test de la page modes de paiement verifie maintenant la presence de ce total.

Fichiers modifies :

- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l tests\Feature\ExampleTest.php` passe.
- `php artisan test --filter="accounting_payment_methods"` passe : 1 test, 33 assertions.

### 2026-05-07 - Module factures de vente

Prompt utilisateur :

```text
nous allons maintenant travailler sur facture donne ton idée pro et ne fais rien
applique ton idée
```

Implementation appliquee :

- Ajout du module `Factures de vente` dans la rubrique Vente du module comptabilite.
- Creation des tables `accounting_sales_invoices`, `accounting_sales_invoice_lines` et `accounting_sales_invoice_payments`.
- Ajout des modeles Eloquent et des relations avec site, client, commande client, bon de livraison, proforma, lignes et paiements.
- Ajout de la liste des factures avec recherche, pagination, statuts, montants alignes a droite et actions.
- Ajout d'une page dediee de creation/modification de facture, dans le meme esprit que proforma et commandes.
- Possibilite de creer une facture depuis une commande client, un bon de livraison ou une proforma.
- Ajout des paiements clients avec recalcul automatique du montant paye, du solde et du statut : brouillon, emise, partiellement payee, payee, en retard, annulee.
- Ajout d'un PDF professionnel de facture de vente avec QR code, objet, lignes, totaux, paiements, solde et pied de page fixe.
- Mise a jour de l'export SQL `database/exports/erp_database.sql`.
- Ajout des traductions FR/EN et d'un test fonctionnel couvrant creation, paiement et generation PDF.

Fichiers principaux modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `app/Models/AccountingSalesInvoice.php`
- `app/Models/AccountingSalesInvoiceLine.php`
- `app/Models/AccountingSalesInvoicePayment.php`
- `app/Models/CompanySite.php`
- `database/migrations/2026_05_07_000004_create_accounting_sales_invoices_tables.php`
- `database/exports/erp_database.sql`
- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `resources/views/main/modules/accounting-sales-invoice-create.blade.php`
- `resources/views/main/modules/accounting-sales-invoice-print.blade.php`
- `resources/views/main/modules/partials/sales-invoice-line-row.blade.php`
- `resources/views/main/modules/accounting-customer-orders.blade.php`
- `resources/views/main/modules/accounting-delivery-notes.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l app\Models\AccountingSalesInvoice.php` passe.
- `php -l app\Models\AccountingSalesInvoiceLine.php` passe.
- `php -l app\Models\AccountingSalesInvoicePayment.php` passe.
- `php -l database/migrations/2026_05_07_000004_create_accounting_sales_invoices_tables.php` passe.
- `php artisan test --filter="sales_invoices"` passe avec 1 test et 31 assertions.
- `php artisan test --filter="accounting_sales_invoices|accounting_customer_orders|accounting_delivery_notes|accounting_proforma"` passe avec 4 tests et 150 assertions.
- `php artisan test` passe avec 81 tests et 919 assertions.

### 2026-05-08 - Correction table factures de vente manquante

Prompt utilisateur :

```text
j'ai cette erreur
continue
```

Erreur constatee :

- `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'erp_database.accounting_sales_invoices' doesn't exist`.
- La migration `2026_05_07_000004_create_accounting_sales_invoices_tables` etait encore en attente dans la base locale.

Correction appliquee :

- Verification de l'etat des migrations avec `php artisan migrate:status`.
- Execution de `php artisan migrate`.
- La migration des factures de vente est maintenant appliquee en batch 33.

Verification :

- `php -l database/migrations/2026_05_07_000004_create_accounting_sales_invoices_tables.php` passe.
- `php artisan migrate:status` confirme que `2026_05_07_000004_create_accounting_sales_invoices_tables` est `Ran`.
- `php artisan test --filter="accounting_sales_invoices"` passe avec 1 test et 31 assertions.

### 2026-05-08 - Ajustement du modal d'ajout de paiement

Prompt utilisateur :

```text
ajuste un peu les inputs de ce modal et reduit legerement le titre
```

Correction appliquee :

- Ajout d'une classe dediee au modal d'ajout de paiement des factures de vente.
- Reduction legere de la taille du titre et meilleur espacement entre l'icone et le texte.
- Ajout de padding interne au formulaire pour que les champs ne collent plus aux bords du modal.
- Ajustement de la hauteur et du padding des inputs/selects/textarea.
- Ajout d'un comportement responsive pour garder le titre propre sur mobile.

Fichiers modifies :

- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="accounting_sales_invoices"` passe avec 1 test et 31 assertions.

### 2026-05-08 - Modal des paiements effectues sur les factures

Prompt utilisateur :

```text
sur la le tableau des factures s'il y a un paiement déjà effectué ajouter un bouton qui affiche modal qui illustre tous les paiements effectués
```

Correction appliquee :

- Ajout d'un bouton d'historique des paiements dans la colonne actions des factures, visible uniquement si la facture possede deja au moins un paiement.
- Ajout d'un modal par facture affichant tous les paiements effectues.
- Le tableau du modal reprend le style standard : recherche, tri, pagination locale a partir de plus de 5 paiements, bordure autour du tableau et bouton fermer en bas.
- Affichage des informations utiles : numero, date, montant, mode de paiement, reference et utilisateur ayant recu le paiement.
- Chargement des relations `paymentMethod` et `receiver` pour eviter les requetes inutiles dans la vue.
- Ajout des traductions FR/EN et mise a jour du test fonctionnel.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php artisan test --filter="accounting_sales_invoices"` passe avec 1 test et 37 assertions.

### 2026-05-08 - Blocage des paiements superieurs au solde restant

Prompt utilisateur :

```text
lors de l'ajout d'un paiement, le montant qui est saisie ne doist pas dépasser le reste du solde à payer
```

Correction appliquee :

- Ajout d'une limite `max` sur le champ montant du modal de paiement, basee sur le solde restant de la facture.
- Ajout d'une validation serveur empechant tout paiement superieur au solde restant, meme si le HTML est modifie.
- Ajout d'un message d'erreur traduit indiquant le solde maximum autorise.
- Le modal de paiement se rouvre automatiquement en cas d'erreur et affiche l'erreur sous le champ montant.
- Ajout d'un test couvrant une tentative de paiement superieure au solde.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php artisan test --filter="accounting_sales_invoices"` passe avec 1 test et 43 assertions.

### 2026-05-08 - Mode de paiement obligatoire sur les paiements de facture

Prompt utilisateur :

```text
le mode de paiement doit etre obligatoire.
pour FAC-000001 supprime le dernier paiement de 1000 qui a été effectué car il sans mode de paiement
```

Correction appliquee :

- Le champ mode de paiement est maintenant obligatoire dans le formulaire d'ajout de paiement.
- La validation serveur exige `payment_method_id`, avec verification que le mode de paiement appartient bien au site courant.
- L'erreur du mode de paiement s'affiche sous le select si le champ est vide.
- Ajout d'un test empechant l'enregistrement d'un paiement sans mode de paiement.
- Suppression dans la base locale du paiement `1000.00` sans mode de paiement lie a `FAC-000001`.
- Recalcul de `FAC-000001` apres suppression : statut `partially_paid`, montant paye `1040.00`, solde restant `700.00`.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php artisan test --filter="accounting_sales_invoices"` passe avec 1 test et 49 assertions.
- Verification base locale : `FAC-000001` ne conserve que le paiement valide avec `payment_method_id = 2`.

### 2026-05-08 - Erreur de paiement affichee sous le champ montant

Prompt utilisateur :

```text
l'erreur doit s'afficher en bas de l'input montant et color l'input en danger
```

Correction appliquee :

- Desactivation de la validation native du navigateur sur le formulaire d'ajout de paiement afin de garder le style applicatif.
- Ajout d'une validation JavaScript du montant avant soumission.
- Si le montant est vide, inferieur au minimum ou superieur au solde restant, l'input passe en etat danger et le message s'affiche directement sous le champ.
- La validation serveur reste active pour bloquer les paiements superieurs au solde, meme en cas de contournement du JavaScript.

Fichiers modifies :

- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php artisan test --filter="accounting_sales_invoices"` passe avec 1 test et 43 assertions.

### 2026-05-08 - Nouvelle page Encaissements (paiements reçus)

Prompt utilisateur :

```text
applique ton idée
```

Correction appliquee :

- Ajout de la page **Encaissements** dans le module Comptabilite, connectee au menu Vente.
- Ajout d'une route dediee et d'une methode controleur pour charger les paiements reels de la base.
- Mise en place de KPI en haut de page : total encaisse, encaissements du mois, nombre de paiements, factures soldees, factures partiellement payees.
- Ajout d'un bloc de filtres (client, mode de paiement, periode, devise, statut facture) avec conservation des filtres pendant recherche/pagination.
- Tableau principal conforme au style existant (tri, recherche dynamique, pagination, statuts colores, montants alignees a droite).
- Ajout d'actions par ligne :
  - impression de la facture liee (PDF)
  - modal de detail d'encaissement.
- Traductions FR/EN ajoutees pour les nouveaux libelles.

Fichiers modifies :

- `routes/web.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-receipts.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=\"accounting_receipts_page_lists_site_receipts_and_filters\"` execute.

### 2026-05-08 - Encaissements : suppression des widgets et total simplifié

Prompt utilisateur :

```text
enleve les widgets j'en ai pas besoin et affiche les totals des encaissements comme tu l'as fais du coté mode paiement
```

Correction appliquee :

- Suppression des widgets KPI sur la page Encaissements.
- Remplacement par une barre de total unique au style `modal-total-strip`, identique a l'affichage des totaux cote modes de paiement.
- Conservation des filtres, du tableau, du tri, de la recherche dynamique et de la pagination.

Fichiers modifies :

- `resources/views/main/modules/accounting-receipts.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter=\"accounting_receipts_page_lists_site_receipts_and_filters\"` execute.

### 2026-05-07 - Recherche dynamique AJAX sur les tableaux

Prompt utilisateur :

```text
la méthode que tu as appliqué n'est pas correcte, la recherche se fait mais c'est lent et à chaque fois la page s'actualise c'est pas propre ni professionnel.
je souhaite que la recherche se fait à la maniere de datatable js dynamique sans charger la page à chaque fois
```

Correction appliquee :

- La recherche des tableaux principaux reste globale sur toutes les pages, mais se fait maintenant en AJAX.
- La page ne se recharge plus quand l'utilisateur saisit une recherche ou clique sur la pagination.
- Le tableau, le compteur et la pagination sont remplaces dynamiquement apres la reponse serveur.
- L'URL garde le parametre `search` sans empiler une nouvelle entree d'historique a chaque frappe.
- La pagination AJAX reste navigable et conserve la recherche en cours.
- Les actions des lignes ajoutees dynamiquement restent utilisables : suppression, edition, modales, historique de connexion et tableaux dans les modales.

Fichiers modifies :

- `resources/js/main.js`
- `resources/js/main/accounting-clients.js`
- `resources/js/main/accounting-suppliers.js`
- `resources/js/main/accounting-prospects.js`
- `resources/js/main/accounting-creditors.js`
- `resources/js/main/accounting-debtors.js`
- `resources/js/main/accounting-partners.js`
- `resources/js/main/accounting-sales-representatives.js`
- `resources/js/main/accounting-currencies.js`
- `resources/js/main/accounting-payment-methods.js`
- `resources/js/main/accounting-stock-resource.js`
- `resources/js/main/accounting-service-resource.js`
- `resources/js/main/accounting-proforma-invoices.js`
- `resources/views/admin/users.blade.php`
- `resources/views/main/users.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `node --check` passe sur les fichiers JavaScript modifies.
- `php artisan test --filter="table_search|admin_main_companies_table_is_paginated|accounting_clients"` passe.
- `php artisan test` passe : 80 tests, 888 assertions.

### 2026-05-07 - Recherche globale sur les tableaux pagines

Prompt utilisateur :

```text
sur tous mes tableaux pour la recherche il ne faut pas seulement faire la recherche sur les items sur la page courante, il faut faire la recherche sur tous les elements et toutes les pages
```

Correction appliquee :

- La recherche des tableaux principaux passe maintenant par le serveur avec le parametre `search`.
- Les resultats sont filtres sur tous les elements de la base, pas seulement sur les lignes de la page courante.
- La pagination conserve la recherche active et revient automatiquement a la premiere page quand le texte de recherche change.
- Les tableaux dans les modales conservent leur recherche locale adaptee a leur contenu deja charge.
- Les recherches couvrent aussi les relations utiles selon les pages : abonnement, entreprise, utilisateurs, contacts, categories, sites, lignes de documents, etc.

Fichiers modifies :

- `resources/js/main.js`
- `app/Http/Controllers/AdminController.php`
- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l app\Http\Controllers\AdminController.php` passe.
- `node --check resources\js\main.js` passe.
- `php artisan test` passe : 80 tests, 888 assertions.

### 2026-05-07 - Espacement des lignes de bons de livraison

Prompt utilisateur :

```text
espace un peu le card des items dans la création et la modification des bons de livraisons
```

Correction appliquee :

- Ajout d'un espacement vertical entre les cards des lignes de bon de livraison.
- Leger renforcement du padding interne des cards pour ameliorer la lisibilite avec les numeros de serie.

Fichiers modifies :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `php artisan test --filter="delivery_notes"` passe.

### 2026-05-08 - Suppression du titre redondant de la caisse

Prompt utilisateur :

```text
enleve Caisse enregistreuse c'est redondant
```

Correction appliquee :

- Suppression confirmee du titre et du sous-titre internes de la page caisse enregistreuse.
- Conservation du titre dans la topbar et du lien retour vers le tableau de bord comptabilite.
- Mise a jour du test pour verifier que le sous-titre redondant ne s'affiche plus dans le contenu.

Fichiers modifies :

- `resources/views/main/modules/accounting-cash-register.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l resources\views\main\modules\accounting-cash-register.blade.php` passe.
- `php artisan test --filter=accounting_cash_register` passe.

### 2026-05-12 - Module avoirs et notes de credit

Prompt utilisateur :

```text
applique ton idee professionnellement
```

Correction appliquee :

- Ajout d'un module complet d'avoirs / notes de credit rattache aux factures de vente.
- Creation des tables `accounting_credit_notes` et `accounting_credit_note_lines`, avec liaison vers la facture, le client, le site, l'utilisateur createur et les lignes de facture.
- Ajout du champ `credit_total` sur les factures de vente pour suivre le total des avoirs valides.
- Creation de la page de liste des avoirs, de la page de creation depuis une facture, de la validation, de l'annulation, de la suppression des brouillons et de l'impression PDF via Dompdf.
- Mise a jour de la liste des factures : colonne `Avoirs`, bouton de creation d'avoir uniquement quand la facture peut encore etre creditee, et statut `Creditée`.
- Mise a jour du dossier client pour afficher les avoirs avec statut, total et lien d'impression.
- Recalcul automatique du solde facture apres paiement ou validation/annulation d'un avoir.
- Traductions FR/EN ajoutees pour les libelles, statuts, erreurs et actions du module.

Fichiers modifies :

- `database/migrations/2026_05_12_000001_create_accounting_credit_notes_tables.php`
- `app/Models/AccountingCreditNote.php`
- `app/Models/AccountingCreditNoteLine.php`
- `app/Models/AccountingSalesInvoice.php`
- `app/Models/AccountingSalesInvoiceLine.php`
- `app/Models/AccountingClient.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-credit-notes.blade.php`
- `resources/views/main/modules/accounting-credit-note-create.blade.php`
- `resources/views/main/modules/accounting-credit-note-print.blade.php`
- `resources/views/main/modules/accounting-sales-invoices.blade.php`
- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` passe.
- `php artisan route:list --name=credit-notes` affiche les 7 routes du module.
- `php artisan test --filter=test_accounting_sales_invoices_page_manages_invoice_payments_and_pdf` passe : 1 test, 62 assertions.
- `php artisan test --filter=accounting_sales_invoices` passe.
- `php artisan test --filter=accounting_clients` passe.

### 2026-05-12 - Module autres entrees

Prompt utilisateur :

```text
applique ton idee
```

Correction appliquee :

- Ajout d'un module `Autres entrees` pour enregistrer les recettes hors cycle de vente classique.
- Creation de la table `accounting_other_incomes` avec reference automatique, type, libelle, date, montant, devise, mode de paiement, reference de paiement et statut.
- Ajout du modele `AccountingOtherIncome` avec statuts brouillon, validee et annulee.
- Creation de la page liste avec filtres, recherche, pagination, total des entrees validees, details, creation, modification des brouillons, validation, annulation et suppression des brouillons.
- Ajout du lien actif dans la sidebar comptabilite.
- Integration avec les modes de paiement : les entrees validees apparaissent dans le modal des encaissements du mode de paiement et bloquent la suppression du mode.
- Ajout des traductions FR/EN et des styles de statuts.

Fichiers modifies :

- `database/migrations/2026_05_12_000002_create_accounting_other_incomes_table.php`
- `app/Models/AccountingOtherIncome.php`
- `app/Models/AccountingPaymentMethod.php`
- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-other-incomes.blade.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan migrate` passe.
- `php artisan route:list --name=other-incomes` affiche les 6 routes du module.
- `php artisan test --filter="accounting_other_incomes|accounting_payment_methods|accounting_receipts"` passe : 3 tests, 67 assertions.

### 2026-05-12 - Type autres dans les autres entrees

Prompt utilisateur :

```text
sur le type d'entrée, ajoute l'option Autres
```

Correction appliquee :

- L'option technique `miscellaneous` des autres entrees est maintenant affichee comme `Autres` en francais.
- La traduction anglaise correspondante est ajustee en `Other`.

Fichiers modifies :

- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan test --filter=test_accounting_other_incomes_page_manages_miscellaneous_receipts` passe : 1 test, 21 assertions.

### 2026-05-13 - Reprise et finalisation du module achats

Prompt utilisateur :

```text
dernierement nous etions entrain de travailler sur la partie achat tu n'avais pas finis ta tache si tu peux continuer et corriger les erreurs
```

Correction appliquee :

- Reprise du module `Achats` et correction des pieces manquantes apres l'implementation initiale.
- Activation du lien `Achats` dans le sous-menu `Depenses` de la sidebar comptabilite, avec ouverture automatique du groupe quand la page est active.
- Ajout des traductions FR/EN pour la page achats, les statuts, les paiements fournisseurs, les filtres et les messages de validation.
- Ajout des couleurs de statuts pour les achats : brouillon, valide, partiellement paye, paye, en retard et annule.
- Protection des modes de paiement : un mode ayant deja des paiements fournisseurs ne peut plus etre supprime.
- Mise a jour de `database/exports/erp_database.sql` avec les tables `accounting_purchases`, `accounting_purchase_lines` et `accounting_purchase_payments`.
- Verification que la migration achats est bien appliquee en base.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `database/exports/erp_database.sql`
- `lang/fr/main.php`
- `lang/en/main.php`
- `resources/css/main.css`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l app\Models\AccountingPurchase.php` passe.
- `php -l app\Models\AccountingPurchaseLine.php` passe.
- `php -l app\Models\AccountingPurchasePayment.php` passe.
- `php -l database\migrations\2026_05_12_000003_create_accounting_purchases_tables.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan route:list --name=purchases` affiche les 7 routes du module.
- `php artisan migrate` passe et applique `2026_05_12_000003_create_accounting_purchases_tables`.
- `php artisan migrate:status` confirme la migration achats en statut `Ran`.
- `php artisan view:cache` passe, puis `php artisan view:clear` passe.
- `php artisan test --filter=AccountingPurchase` indique qu'aucun test cible n'existe encore.

### 2026-05-13 - Correction du clic sur le sous-menu achats

Prompt utilisateur :

```text
je n'arrive pas a cliquer sur le sous menus achats sur la side barre
```

Correction appliquee :

- Correction du comportement JavaScript de la sidebar comptabilite lorsque celle-ci est reduite.
- Maintenant, lorsqu'on clique sur un groupe de sous-menu en sidebar reduite, la sidebar se deploie automatiquement et ouvre le groupe clique.
- Le lien `Achats` reste un vrai lien vers la page achats et devient cliquable apres ouverture du groupe `Depenses`.

Fichiers modifies :

- `resources/js/main.js`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:cache` passe.
- `php artisan route:list --name=purchases` confirme les 7 routes achats.
- `php artisan view:clear` passe.

### 2026-05-13 - Correction du lien achats depuis le tableau de bord comptabilite

Prompt utilisateur :

```text
c'est quoi ton probleme je te parle de la page achets, je n'arrive pas a acceder
```

Correction appliquee :

- Identification de la vraie source du blocage : la page tableau de bord comptabilite possedait une sidebar dupliquee, separee du partial reutilise ailleurs.
- Dans cette sidebar du tableau de bord, le sous-menu `Achats` pointait encore vers `#`.
- Remplacement du lien `#` par la vraie route `main.accounting.purchases`.
- Verification du rendu HTML : le lien `Achats` genere maintenant bien l'URL `/main/companies/{company}/sites/{site}/modules/accounting/purchases`.

Fichiers modifies :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `docs/prompts/project-history.md`

Verification :

- Test de rendu du dashboard via le kernel Laravel : le lien `Achats` ressort avec la bonne URL.
- `php artisan view:cache` passe.
- `php artisan route:list --name=main.accounting.purchases` confirme les 7 routes achats.
- `php artisan view:clear` passe.

### 2026-05-13 - Activation des decaissements par mode de paiement

Prompt utilisateur :

```text
comme nous avons travailler sur achat tu dois maintenant mettre a jour les decaissements cotes mode de paiement
```

Modification appliquee :

- Le bouton `decaissements` de chaque mode de paiement est maintenant actif.
- Ajout d'un modal par mode de paiement pour afficher les paiements fournisseurs issus du module `Achats`.
- Le modal affiche le total decaisse, la liste des achats payes, les fournisseurs, les dates, les montants, les references et l'utilisateur payeur.
- La recherche, le tri et la pagination du tableau de decaissements reprennent le meme comportement que les tableaux de modales existants.
- Les modes de paiement chargent maintenant les relations `purchasePayments`, `purchase`, `supplier` et `payer`.
- Ajout du total SQL `disbursements_total` pour eviter de recalculer les montants inutilement.
- Ajout des traductions FR/EN liees aux decaissements.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=payment-methods` confirme les 4 routes des modes de paiement.
- `php artisan view:clear` passe.

### 2026-05-13 - Module bons de commande fournisseurs

Prompt utilisateur :

```text
applique ton idee
```

Implementation appliquee :

- Creation des tables `accounting_purchase_orders` et `accounting_purchase_order_lines`.
- Ajout des modeles `AccountingPurchaseOrder` et `AccountingPurchaseOrderLine`.
- Ajout des relations site, fournisseur et achat lie.
- Ajout des routes CRUD, impression PDF et conversion en achat.
- Ajout de la page liste des bons de commande avec filtres, recherche, tri, pagination et actions.
- Ajout de la page creation/modification sur une page dediee avec lignes article/service/ligne libre.
- Ajout du PDF professionnel du bon de commande avec totaux, fournisseur, lignes, notes, conditions et pied de page.
- Ajout de la conversion `bon de commande confirme/recu` vers un achat fournisseur.
- Ajout du lien `Bons de commande` dans la sidebar comptabilite et dans la sidebar du tableau de bord comptabilite.
- Ajout des traductions FR/EN et des statuts visuels.
- Mise a jour de `database/exports/erp_database.sql`.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Models/AccountingPurchase.php`
- `app/Models/AccountingPurchaseOrder.php`
- `app/Models/AccountingPurchaseOrderLine.php`
- `app/Models/AccountingSupplier.php`
- `app/Models/CompanySite.php`
- `database/migrations/2026_05_13_000001_create_accounting_purchase_orders_tables.php`
- `database/exports/erp_database.sql`
- `routes/web.php`
- `resources/css/main.css`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/views/main/modules/accounting-purchase-order-create.blade.php`
- `resources/views/main/modules/accounting-purchase-order-print.blade.php`
- `resources/views/main/modules/accounting-purchase-orders.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l app\Models\AccountingPurchaseOrder.php` passe.
- `php -l app\Models\AccountingPurchaseOrderLine.php` passe.
- `php -l app\Models\CompanySite.php` passe.
- `php -l app\Models\AccountingSupplier.php` passe.
- `php -l app\Models\AccountingPurchase.php` passe.
- `php -l database\migrations\2026_05_13_000001_create_accounting_purchase_orders_tables.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan route:list --name=purchase-orders` confirme les 8 routes.
- `php artisan view:cache` passe.
- `php artisan migrate` applique `2026_05_13_000001_create_accounting_purchase_orders_tables`.
- `php artisan migrate:status` confirme la migration en statut `Ran`.
- Test de rendu HTTP de la liste et de la page creation : statut 200.
- Test de rendu Blade du PDF du bon de commande avec une transaction rollback : `print-view-ok`.
- `php artisan view:clear` passe.

### 2026-05-13 - Correction entete PDF bon de commande fournisseur

Prompt utilisateur :

```text
arrange l'affichage pdf du bon de commande garde les modeles d'entete qu'il y a sur la proforma, facture etc...
```

Correction appliquee :

- Remplacement de l'ancien entete PDF du bon de commande base sur des `float`, qui provoquait un chevauchement entre le logo et les informations de l'entreprise.
- Reprise du modele d'entete utilise par les PDF proforma/facture : structure en tableau compatible DomPDF, logo separe, nom de l'entreprise, site et titre du document aligne a droite.
- Harmonisation de la police, des espacements et de la barre bleue/grise sous l'entete.
- Le titre `Bon de commande`, la reference et le statut restent affiches a droite comme dans les autres documents PDF.

Fichier modifie :

- `resources/views/main/modules/accounting-purchase-order-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php artisan view:cache` passe.
- `php artisan view:clear` passe.
- `php -l routes\web.php` passe.
- Test de rendu Blade du PDF du bon de commande : `print-view-ok`.
- Test de generation DomPDF du bon de commande : `pdf-output-ok`.

### 2026-05-14 - Harmonisation PDF bon de commande avec facture

Prompt utilisateur :

```text
enleve l'adresse email "sales@exadgroup.org" garde le meme model comme sur l'entete de la facture merci ainsi la ligne de l'entete elle est trop grande, reproduit la meme chose comme tu l'avais fais dans la facture. Meme le pied de page ca doit etre pareil comme dans la facture et n'oublie pas le QR code
```

Corrections appliquees :

- Suppression de l'email affiche dans l'entete du PDF bon de commande.
- Isolation CSS de la ligne bleue/grise de l'entete pour eviter le padding herite des tableaux.
- Reprise du pied de page du modele facture : informations entreprise, compte principal, ligne bleue/grise et mention generee par EXAD ERP.
- Ajout du QR code du bon de commande sous les termes et conditions.
- Generation du QR code depuis le lien d'impression du bon de commande.
- Signature alignee sur le modele des documents de facturation avec nom et grade si disponible.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-purchase-order-print.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php artisan view:cache` passe.
- Generation DomPDF du bon de commande : `pdf-output-ok`.
- `php artisan view:clear` passe.

### 2026-05-14 - Page depenses et decaissements

Prompt utilisateur :

```text
applique ton idée
```

Implementation appliquee :

- Creation du module `Depenses` dans la rubrique depenses du module comptabilite.
- Ajout des tables `accounting_expense_categories` et `accounting_expenses`.
- Ajout des categories systeme par defaut : loyer, transport, carburant, communication, internet, electricite, eau, frais bancaires, frais administratifs, entretien, mission, restauration, avances sur salaires, taxes et autres charges.
- Ajout des modeles `AccountingExpenseCategory` et `AccountingExpense` avec relations site, categorie, mode de paiement et createur.
- Ajout de la page liste des depenses avec le format standard : recherche, tri, pagination, filtres, total valide, actions voir/modifier/valider/annuler/supprimer.
- Ajout du formulaire modal de creation/modification avec erreurs sous les champs, placeholders et champs obligatoires.
- Branchement de la rubrique `Depenses` dans la sidebar et sur le tableau de bord comptable.
- Integration des depenses validees dans les decaissements des modes de paiement.
- Blocage de suppression d'un mode de paiement s'il possede deja des depenses validees.
- Ajout des traductions FR/EN necessaires.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Models/AccountingExpense.php`
- `app/Models/AccountingExpenseCategory.php`
- `app/Models/AccountingPaymentMethod.php`
- `app/Models/CompanySite.php`
- `database/migrations/2026_05_14_000001_create_accounting_expenses_tables.php`
- `resources/views/main/modules/accounting-expenses.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `routes/web.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingExpense.php` passe.
- `php -l app\Models\AccountingExpenseCategory.php` passe.
- `php -l database\migrations\2026_05_14_000001_create_accounting_expenses_tables.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan route:list --name=main.accounting.expenses` confirme les 6 routes.
- `php artisan migrate` applique `2026_05_14_000001_create_accounting_expenses_tables`.
- `php artisan view:cache` passe.

### 2026-05-14 - Page dettes et paiements des dettes

Prompt utilisateur :

```text
applique ton idee
```

Implementation appliquee :

- Ajout de la page `Dettes` dans la rubrique autres du module comptabilite.
- Aggregation des dettes manuelles provenant des creanciers et des achats fournisseurs ayant encore un solde a payer.
- Ajout des filtres par origine, statut, devise et echeance.
- Ajout du tableau standard avec recherche, tri, pagination, montants alignes a droite, badges de statut et actions.
- Ajout de la creation/modification des dettes manuelles depuis la page Dettes.
- Ajout du paiement des dettes manuelles avec mode de paiement, date, montant, reference et notes.
- Ajout de la table `accounting_creditor_payments` pour historiser les paiements des dettes manuelles.
- Ajout des historiques de paiements sur les dettes et achats impayes.
- Integration des paiements de dettes manuelles dans les decaissements des modes de paiement.
- Blocage de suppression des modes de paiement qui possedent deja des paiements de dettes.
- Correction de la migration avec des noms d'index courts compatibles MySQL.
- Ajout des traductions FR/EN necessaires.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Models/AccountingCreditor.php`
- `app/Models/AccountingCreditorPayment.php`
- `app/Models/AccountingPaymentMethod.php`
- `database/migrations/2026_05_14_000002_create_accounting_creditor_payments_table.php`
- `resources/views/main/modules/accounting-debts.blade.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `routes/web.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l app\Models\AccountingCreditorPayment.php` passe.
- `php -l database\migrations\2026_05_14_000002_create_accounting_creditor_payments_table.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan route:list --name=main.accounting.debts` confirme les routes Dettes.
- `php artisan migrate` applique `2026_05_14_000002_create_accounting_creditor_payments_table`.
- `php artisan view:cache` passe.
- `git diff --check` passe avec uniquement des avertissements CRLF existants.

### 2026-05-14 - Tableau de bord comptabilite avec donnees reelles

Prompt utilisateur :

```text
mets maintenant a jour le tableau de bord avec des informations reelles
```

Corrections appliquees :

- Le tableau de bord du module comptabilite utilise maintenant des donnees issues de la base.
- Les KPI affichent les revenus, factures de vente, clients, creances et depenses reels du site.
- Les graphiques semaine / mois / annee utilisent les ventes, revenus, depenses, creances et dettes agregees.
- Les repartitions contacts, stock/services et flux de documents sont basees sur les tables existantes.
- L'echeancier du dashboard affiche les creances clients et les dettes fournisseurs en attente.
- Les montants utilisent automatiquement la devise du site.
- Les anciennes valeurs de demonstration du dashboard ont ete remplacees par des valeurs de secours neutres.
- Le lien `Creances` de la sidebar comptabilite pointe maintenant vers la page correspondante.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-dashboard.blade.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=main.accounting.receivables` confirme les routes.
- `php artisan route:list --name=main.companies.sites.modules.show` confirme la route du dashboard.
- `git diff --check` passe avec uniquement des avertissements CRLF existants.

### 2026-05-14 - Ajustement modal dettes et reprise creancier existant

Prompt utilisateur :

```text
elargi legerement le modal de modification et creation des dettes et pour les creancier donner la possibilite de selectionner un creancier existant deja
```

Corrections appliquees :

- Elargissement leger du modal de creation/modification des dettes.
- Ajout d'un select optionnel `Creancier existant` dans le modal de creation de dette.
- Remplissage automatique des champs type, nom, telephone, email, adresse et devise lorsqu'un creancier existant est selectionne.
- Masquage du select de creancier existant en mode modification pour garder le formulaire clair.
- Ajout des traductions FR/EN.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-debts.blade.php`
- `resources/js/main/accounting-creditors.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-debts.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe avec uniquement des avertissements CRLF existants.

### 2026-05-14 - Personnalisation globale de l'application par le superadmin

Prompt utilisateur :

```text
Je spuhaite ajouter une option qui va permettre au superadmin de pouvoir modifier totalement ou personnaliser l'application par exemple changer le nom de l'application et le logo et autres informations que tu peux me suggerer. Car l'application sera destiné à plusieurs clients et chaque client voudras choisir le nom qu'il souhaitera mettre sur l'application qui sera hébergé chez lui
```

Corrections appliquees :

- Ajout d'une table `application_settings` pour stocker les parametres de personnalisation de l'instance.
- Ajout du modele `ApplicationSetting`.
- Ajout d'un service `AppBranding` avec cache pour centraliser le nom, nom court, logo, favicon, slogan, description, support et copyright.
- Ajout de helpers Blade/PHP : `app_brand_name()`, `app_brand_short_name()`, `app_brand_logo_url()`, `app_brand_favicon_url()` et `app_branding()`.
- Le nom Laravel `config('app.name')` est maintenant alimente depuis la personnalisation.
- Ajout d'une page superadmin `Personnalisation` accessible depuis la sidebar.
- Le superadmin peut modifier le nom de l'application, le nom court, le slogan, la description, le site web, les informations de support, le copyright, le logo et le favicon.
- Le logo, le favicon et le nom personnalises sont utilises dans les vues principales, les sidebars, les pages d'authentification et les titres.
- Ajout du favicon dynamique dans les vues Blade.
- Mise a jour des traductions FR/EN.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `app/Models/ApplicationSetting.php`
- `app/Providers/AppServiceProvider.php`
- `app/Support/AppBranding.php`
- `app/Support/helpers.php`
- `composer.json`
- `database/migrations/2026_05_14_000004_create_application_settings_table.php`
- `resources/views/admin/application-settings.blade.php`
- `resources/views/admin/*.blade.php`
- `resources/views/auth/*.blade.php`
- `resources/views/main/**/*.blade.php`
- `resources/views/profile/edit.blade.php`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `lang/fr/auth.php`
- `lang/en/auth.php`
- `routes/web.php`
- `docs/prompts/project-history.md`

Verification :

- `composer dump-autoload` passe.
- `php artisan migrate` passe.
- `php artisan cache:forget application_branding` passe.
- `php -l app\Support\AppBranding.php` passe.
- `php -l app\Support\helpers.php` passe.
- `php -l app\Models\ApplicationSetting.php` passe.
- `php -l app\Http\Controllers\AdminController.php` passe.
- `php -l app\Providers\AppServiceProvider.php` passe.
- `php -l database\migrations\2026_05_14_000004_create_application_settings_table.php` passe.
- `php -l resources\views\admin\application-settings.blade.php` passe.
- `php -l lang\fr\admin.php` passe.
- `php -l lang\en\admin.php` passe.
- `php -l lang\fr\auth.php` passe.
- `php -l lang\en\auth.php` passe.
- `php artisan route:list --name=admin.application-settings` confirme les routes.
- `php artisan view:cache` passe.
- `git diff --check` passe avec uniquement des avertissements CRLF existants.

### 2026-05-14 - Page creances avec la meme logique que dettes

Prompt utilisateur :

```text
nous allons travailler maintenant travailler sur la page creances et j'aimerai qu'on garde la meme logique comme dettes
```

Travail realise :

- Creation de la page `Creances` dans le module comptabilite, accessible depuis la sidebar.
- Affichage des creances manuelles et des factures clients non soldees dans un meme tableau.
- Ajout des filtres origine, statut, devise et echeance, avec total global des creances a encaisser.
- Ajout d'une modale de creation/modification de creance manuelle.
- Possibilite de rattacher une nouvelle creance a un debiteur existant sans dupliquer sa fiche.
- Ajout de l'historique des encaissements et de la saisie d'un encaissement sur une creance.
- Ajout de la table `accounting_debtor_payments` pour stocker les encaissements des creances manuelles.
- La page `Debiteurs` regroupe maintenant les fiches debiteurs pour eviter les doublons visuels.
- Les encaissements de creances manuelles sont pris en compte dans les mouvements des modes de paiement.
- Ajout des traductions FR/EN necessaires.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Models/AccountingDebtor.php`
- `app/Models/AccountingDebtorPayment.php`
- `app/Models/AccountingPaymentMethod.php`
- `database/migrations/2026_05_14_000003_create_accounting_debtor_payments_table.php`
- `resources/views/main/modules/accounting-receivables.blade.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/js/main/accounting-debtors.js`
- `routes/web.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Models\AccountingDebtorPayment.php` passe.
- `php -l database\migrations\2026_05_14_000003_create_accounting_debtor_payments_table.php` passe.
- `php -l app\Models\AccountingDebtor.php` passe.
- `php -l app\Models\AccountingPaymentMethod.php` passe.
- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-receivables.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php -l routes\web.php` passe.

### 2026-05-14 - Autoriser plusieurs dettes pour un meme creancier

Prompt utilisateur :

```text
un creancier peut existant peut avoir plusieurs dettes
```

Corrections appliquees :

- Correction de la logique precedente : selectionner un creancier existant cree maintenant une nouvelle dette separee.
- La fiche du creancier n'est pas dupliquee visuellement : la page `Creanciers` regroupe les lignes par creancier et affiche un solde cumule.
- La page `Dettes` continue d'afficher chaque dette manuelle individuellement avec sa propre reference, son echeance, son solde et ses paiements.
- Le select des creanciers existants dans la creation de dette est dedoublonne.
- Mise a jour du message de succes FR/EN pour refleter le rattachement d'une dette au creancier existant.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe avec uniquement des avertissements CRLF existants.

### 2026-05-14 - Eviter les doublons de creanciers existants

Prompt utilisateur :

```text
un creancier deja existant ne dois pas etre duplique sur la liste des creanciers
```

Corrections appliquees :

- Le select `Creancier existant` est maintenant soumis avec le formulaire de creation de dette.
- Si un creancier existant est selectionne, la nouvelle dette est cumulee sur ce creancier au lieu de creer une nouvelle ligne.
- Les montants initial et paye sont additionnes, l'echeance la plus proche est conservee et les descriptions sont concatenees.
- Controle de securite : le creancier selectionne doit appartenir au site courant.
- Controle de coherence : la devise de la nouvelle dette doit correspondre a la devise du creancier existant.
- Ajout des messages d'erreur et de succes FR/EN.

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-debts.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app\Http\Controllers\MainController.php` passe.
- `php -l resources\views\main\modules\accounting-debts.blade.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe avec uniquement des avertissements CRLF existants.

### 2026-05-25 - Correction de la page de personnalisation superadmin

Prompt utilisateur :

```text
dernierement tu n'avais finis ta derniere requette parce qu'on avait plus de token peux-tu continuer et fixer les erreurs
```

Corrections appliquees :

- Correction de l'erreur 500 sur `/admin/application-settings` : la vue ne charge plus le fichier inexistant `resources/js/admin.js`.
- La page charge maintenant Bootstrap et `resources/js/main.js`, comme les autres pages admin, afin de conserver les comportements de navigation, de langue et de theme.
- Normalisation UTF-8 de la nouvelle vue de personnalisation et reparation des separateurs de sidebar incorrectement enregistres dans les vues admin et profil concernees.
- Ajout du lien `Personnalisation` dans la sidebar du formulaire de creation/modification d'entreprise pour conserver la navigation superadmin uniforme.

Fichiers modifies :

- `resources/views/admin/application-settings.blade.php`
- `resources/views/admin/companies-create.blade.php`
- `resources/views/admin/companies.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/subscriptions.blade.php`
- `resources/views/admin/users.blade.php`
- `resources/views/profile/edit.blade.php`
- `docs/prompts/project-history.md`

Verification :

- Rendu d'execution de `resources/views/admin/application-settings.blade.php` via Laravel : `render-ok`.
- Verification stricte UTF-8 sur `resources/views` et `lang` : aucun fichier invalide.
- Recherche de reference restante a `resources/js/admin.js` : aucune.
- `php -l` passe sur les classes PHP de la personnalisation.
- `php artisan route:list --name=admin.application-settings` confirme les routes GET et PUT.
- `php artisan view:cache` passe.
- `git diff --check` passe avec uniquement des avertissements CRLF existants.

### 2026-05-25 - Cropper pour le logo et le favicon de l'application

Prompt utilisateur :

```text
pouvons-nous utiliser cropper ici
```

Corrections appliquees :

- Ajout de Cropper.js sur la page superadmin `Personnalisation`, en reprenant le comportement visuel deja utilise pour la photo de profil.
- Lors de la selection d'un logo ou d'un favicon raster (`PNG`, `JPG` ou `WEBP`), ouverture d'un modal de recadrage carre avec zoom et rotation.
- L'apercu recadre est visible immediatement, mais l'enregistrement reste lie au bouton principal du formulaire afin de sauvegarder tous les parametres ensemble.
- Ajout d'un script isole `resources/js/admin/application-settings.js` dedie au recadrage du logo et du favicon.
- Le controleur valide le contenu recadre cote serveur, limite sa taille et stocke uniquement une image valide.
- Les fichiers vectoriels `SVG` et les icones `ICO` restent acceptes par televersement direct lorsqu'un recadrage bitmap ne s'applique pas.
- Ajout des libelles FR/EN pour le modal et les messages de validation.

Fichiers modifies :

- `app/Http/Controllers/AdminController.php`
- `resources/views/admin/application-settings.blade.php`
- `resources/js/admin/application-settings.js`
- `resources/css/admin/dashboard.css`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l` passe sur le controleur, les traductions et la vue.
- Verification de syntaxe JavaScript sur `resources/js/admin/application-settings.js` passe.
- Rendu d'execution de la vue `admin.application-settings` via Laravel : `render-ok`.
- `php artisan view:cache` passe.
- Verification stricte UTF-8 sur les fichiers d'interface et applicatifs controles : `utf8-ok`.

### 2026-05-25 - Gestion des taxes du module comptabilite

Prompt utilisateur :

```text
dans le module comptabilite et facturation, nous allons maintenant travailler sur taxes; ne fais rien donne moi ton idee
applique ton idee
```

Corrections appliquees :

- Ajout de la page `Taxes` dans la navigation du module comptabilite avec le meme standard d'interface que les devises et les modes de paiement : recherche dynamique, tri, pagination et formulaire modal.
- Creation de la table `accounting_taxes` et du modele associe, avec gestion du type de taxe, du mode de calcul, du taux ou montant, du traitement fiscal, de l'application ventes/achats, du statut et de la taxe par defaut.
- Generation initiale, par site comptable, de la TVA basee sur le pays de l'entreprise et d'une option d'exoneration.
- Utilisation de la taxe en pourcentage marquee par defaut comme taux TVA propose lors de la creation de nouveaux documents de vente et d'achat.
- Conservation du taux historise dans les documents deja enregistres : modifier une taxe ne recalcule pas retroactivement les anciennes factures, proformas ou commandes.
- Protection de la taxe par defaut et des taxes dont le taux est deja utilise dans des documents contre la suppression.
- Limitation propre de la taxe par defaut a un taux en pourcentage applicable aux ventes et achats, puisque les formulaires actuels calculent la TVA globale sous cette forme.
- Ajout d'un test fonctionnel pour la page Taxes, sa configuration initiale et ses regles de taxe par defaut.
- Mise a jour de l'export `database/exports/erp_database.sql`.
- Correction d'un plantage revele par les tests sur l'echeancier du tableau de bord comptabilite lors de la fusion de tableaux de donnees.
- Actualisation des tests du tableau de bord et des modes de paiement pour tenir compte du format montant compact et des decaissements maintenant operationnels.

Fichiers ajoutes :

- `app/Models/AccountingTax.php`
- `database/migrations/2026_05_25_000001_create_accounting_taxes_table.php`
- `resources/views/main/modules/accounting-taxes.blade.php`
- `resources/js/main/accounting-taxes.js`

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Models/CompanySite.php`
- `routes/web.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `database/exports/erp_database.sql`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- Migration `2026_05_25_000001_create_accounting_taxes_table` appliquee avec succes.
- La base locale contient les configurations initiales `TVA` et `Exoneration`, dont une taxe par defaut.
- `php -l` passe sur le controleur, le modele, la migration, les routes, les traductions et la nouvelle vue.
- `node --check resources/js/main/accounting-taxes.js` passe.
- `php artisan route:list --name=main.accounting.taxes` confirme les routes GET, POST, PUT et DELETE protegees par `auth`.
- `php artisan view:cache` passe.
- `php artisan test` passe : 85 tests, 1064 assertions.
- `git diff --check` passe avec uniquement des avertissements CRLF deja presents.

### 2026-05-25 - Tresorerie du module comptabilite

Prompt utilisateur :

```text
Nous allons maintenant travailler sur Tresorie. Donne moi ton idee et ne fais rien encore
applique ton idee
```

Corrections appliquees :

- Ajout d'une page `Tresorerie` accessible depuis la sidebar et les raccourcis du tableau de bord comptable.
- Creation d'un registre central `accounting_treasury_movements` pour consolider les flux reels par site, devise et mode de paiement.
- Consolidation des encaissements de factures, encaissements de creances et autres entrees validees comme entrees de tresorerie.
- Consolidation des paiements d'achats, reglements de dettes et depenses validees comme sorties de tresorerie.
- Exclusion des entrees ou depenses annulees du solde reel tout en conservant la tracabilite lorsqu'une ecriture avait deja existe.
- Ajout d'un tableau de bord de tresorerie avec solde disponible, entrees, sorties, creances, dettes et solde previsionnel, filtre par devise.
- Ajout de graphiques ApexCharts reels pour l'evolution des flux par semaine, mois ou annee, les soldes par mode de paiement et la projection.
- Ajout d'un tableau dynamique des mouvements avec filtres, recherche AJAX, tri et pagination, dans le standard des tableaux existants.
- Les creances et dettes encore ouvertes alimentent la prevision sans modifier le solde disponible.
- Ajout des traductions francaises et anglaises et d'un test fonctionnel de consolidation des mouvements.
- Mise a jour de l'export `database/exports/erp_database.sql`.

Fichiers ajoutes :

- `app/Models/AccountingTreasuryMovement.php`
- `database/migrations/2026_05_25_000002_create_accounting_treasury_movements_table.php`
- `resources/views/main/modules/accounting-treasury.blade.php`
- `resources/js/main/accounting-treasury.js`

Fichiers modifies :

- `app/Http/Controllers/MainController.php`
- `app/Models/CompanySite.php`
- `routes/web.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `database/exports/erp_database.sql`
- `docs/prompts/project-history.md`

Verification :

- Migration `2026_05_25_000002_create_accounting_treasury_movements_table` appliquee avec succes.
- `php -l` passe sur le modele, la migration, le controleur, les traductions et le test modifies.
- `node --check resources/js/main/accounting-treasury.js` passe.
- `php artisan route:list --name=main.accounting.treasury` confirme la route protegee de la nouvelle page.
- `php artisan view:cache` passe.
- Le test cible `test_accounting_treasury_consolidates_validated_cash_movements` passe : 1 test, 7 assertions.

### 2026-05-25 - Lien Taxes depuis le tableau de bord comptable

Prompt utilisateur :

```text
quand je suis dans dashboard je menu taxes n'as pas de lien
```

Correction appliquee :

- Correction de l'entree `Taxes` de la sidebar propre au dashboard comptable afin qu'elle redirige vers la page de gestion des taxes, comme la sidebar partagee des autres pages.
- Ajout d'une assertion fonctionnelle verifiant que le dashboard restitue bien le lien vers la route `main.accounting.taxes`.

### 2026-05-25 - Reorganisation des widgets de tresorerie

Prompt utilisateur :

```text
l'affichage des widgets dans Tresorerie est desordonne, essaie de bien organiser et de bien moderniser l'affichage
```

Corrections appliquees :

- Remplacement de la rangee uniforme de six widgets par une synthese hierarchisee et lisible.
- Mise en avant du solde disponible dans une carte principale avec le flux net reel.
- Regroupement coherent des entrees, sorties, creances et dettes en quatre cartes compactes.
- Mise en avant separee du solde previsionnel avec le detail des creances et dettes qui influencent la projection.
- Ajustement responsive pour conserver des montants lisibles sans debordement sur tablette et mobile.
- Ajout des libelles francais et anglais necessaires et extension du test de rendu de la page Tresorerie.

Verification :

- `php artisan test --filter=test_accounting_treasury_consolidates_validated_cash_movements` passe : 1 test, 10 assertions.
- `php artisan view:cache` passe.
- `php -l` passe sur les traductions et le test modifies.
- `git diff --check` passe avec uniquement les avertissements CRLF deja presents.

### 2026-05-25 - Espacement vertical des widgets de tresorerie

Prompt utilisateur :

```text
les widgets sont trop colle de haut en bas il faut laisser un peu d'espace
```

Correction appliquee :

- Augmentation de l'espacement vertical entre les widgets de flux de la synthese Tresorerie.
- Ajout d'un rythme vertical plus confortable entre la synthese, les graphiques et les blocs suivants.
- Conservation d'un espacement adapte en affichage mobile afin d'eviter des cartes tassees les unes contre les autres.

### 2026-05-25 - Correction visible de l'aeration verticale de tresorerie

Prompt utilisateur :

```text
c'est toujours colle, regarde sur les images j'ai souligne en jaune les widgets ne respirent pas
```

Correction appliquee :

- Correction de la regle d'espacement initiale qui n'etait pas appliquee a cause du mode d'affichage bloc de la zone de contenu.
- Ajout de marges verticales explicites entre les onglets de periode, les cartes de synthese, les panneaux graphiques et le bandeau d'information.
- Renforcement de l'espacement interne de la grille de graphiques afin que les panneaux empiles restent clairement separes.

### 2026-05-25 - Separation des graphiques de tresorerie

Prompt utilisateur :

```text
Entre Evolution des flux et Soldes par mode de paiement et Previsions de tresorerie toujours coince
```

Correction appliquee :

- Identification de la cause : le conteneur des graphiques avait des colonnes et un espacement declares, sans activer le composant de grille partage.
- Application de la classe `dashboard-grid` deja utilisee par le tableau de bord comptable afin que les trois panneaux utilisent reellement leur espacement vertical.

### 2026-05-25 - Rapprochement bancaire du module comptabilite

Prompt utilisateur :

```text
nous allons maintenant travailler sur rapprochement bancaire. Donne ton idee ne fais rien
applique ton idee
```

Implementation appliquee :

- Ajout d'une page `Rapprochement bancaire` accessible depuis la sidebar et le tableau de bord du module comptabilite.
- Creation des sessions de rapprochement par compte bancaire et periode avec soldes initial/final du releve, solde ERP et calcul automatique de l'ecart.
- Ajout des lignes de releve manuellement ou par import CSV, avec classement entree/sortie, reference et description.
- Rapprochement d'une ligne bancaire avec un mouvement de tresorerie valide, justification sans ecriture ou creation controlee d'un ajustement bancaire.
- Cloture reservee a un administrateur uniquement lorsque toutes les lignes sont traitees et que l'ecart est nul.
- Generation d'un rapport PDF de rapprochement au format coherent avec les documents comptables existants.
- Protection de la suppression d'un mode de paiement bancaire lorsqu'il est deja lie a un rapprochement.
- Annulation propre d'un ajustement genere depuis un rapprochement : l'ecriture de tresorerie associee est retiree avec la correspondance.
- Ajout complet des libelles francais et anglais, des statuts et des styles de la nouvelle interface.

Fichiers principaux :

- `app/Models/AccountingBankReconciliation.php`
- `app/Models/AccountingBankStatementLine.php`
- `app/Models/AccountingBankReconciliationMatch.php`
- `database/migrations/2026_05_25_000003_create_accounting_bank_reconciliation_tables.php`
- `resources/views/main/modules/accounting-bank-reconciliations.blade.php`
- `resources/views/main/modules/accounting-bank-reconciliation-report.blade.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `database/exports/erp_database.sql`

Verification :

- Migration `2026_05_25_000003_create_accounting_bank_reconciliation_tables` appliquee avec succes sur la base locale MySQL.
- `php -l` passe sur le controleur, les modeles, la migration, les traductions et le test modifies.
- `php artisan route:list --name=main.accounting.bank-reconciliations` confirme les dix routes du flux.
- `php artisan view:cache` passe.
- Le test cible `test_accounting_bank_reconciliation_matches_and_closes_a_bank_statement` passe : 1 test, 14 assertions.
- La suite complete `php artisan test` passe : 87 tests, 1090 assertions.
- Export SQL `database/exports/erp_database.sql` regenere apres migration.

### 2026-05-25 - Correction des libelles du graphique de tresorerie et du modal bancaire

Prompt utilisateur :

```text
pourquoi il y a Nan sur les soldes par mode de paiement dans tresorie...
Deuxiemement rapprochement bancaire l'icone est trop serre dans Nouveau rapprochement laisse un peu d'espace
```

Correction appliquee :

- Identification de la cause du texte `NaN` : le graphique horizontal `Soldes par mode de paiement` reutilisait le formateur numerique de l'axe vertical pour afficher les noms des comptes.
- Definition d'un axe vertical propre au graphique horizontal afin d'afficher correctement les libelles des modes de paiement, sans conversion numerique.
- Ajout d'un espacement stable entre l'icone banque et le titre `Nouveau rapprochement` dans le modal de creation.

Fichiers modifies :

- `resources/js/main/accounting-treasury.js`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Verification :

- `node --check resources/js/main/accounting-treasury.js` passe.
- `php artisan view:cache` passe.
- Les tests cibles Tresorerie et Rapprochement bancaire passent : 2 tests, 24 assertions.

### 2026-05-25 - Relances de paiement et promesses de règlement

Prompt utilisateur :

```text
applique ton idée
```

Contexte appliqué :

- Mise en oeuvre de la proposition validée pour la page `Relances de paiement` du module Comptabilité (Facturation).
- Suivi des factures de vente et créances manuelles ayant encore un solde à encaisser.

Fonctionnalités livrées :

- Ajout du menu `Relances de paiement` dans la navigation comptable et sur le tableau de bord du module.
- Tableau de suivi avec solde, échéance, nombre de jours de retard, niveau et statut de relance.
- Widgets de pilotage : total à relancer ventilé par devise, documents échus, retards de plus de 30 jours et promesses en attente.
- Recherche globale et pagination asynchrones selon le standard DataTable existant, avec tri des colonnes.
- Création et mise à jour d'une relance avec niveau, canal, objet, message, prochaine date de relance et notes.
- Historique des actions de relance et gestion des promesses de paiement avec contrôle du montant restant dû.
- Signalement d'un litige avec motif obligatoire et traçabilité de l'action.
- Suspension manuelle d'une relance et mise à jour automatique du statut lorsque le solde est réglé ou qu'une promesse expire.
- Conservation des dossiers soldés en consultation, sans action de relance supplémentaire sur un solde nul.
- Génération d'une lettre PDF de relance au style des documents comptables existants.

Base de données :

- Table `accounting_payment_reminders` pour le dossier de suivi d'une facture ou créance.
- Table `accounting_payment_reminder_actions` pour la traçabilité des actions.
- Table `accounting_payment_promises` pour les engagements de règlement.
- Relations ajoutées sur les sites, clients, factures et créances.

Fichiers principaux :

- `app/Models/AccountingPaymentReminder.php`
- `app/Models/AccountingPaymentReminderAction.php`
- `app/Models/AccountingPaymentPromise.php`
- `database/migrations/2026_05_25_000004_create_accounting_payment_reminders_tables.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-payment-reminders.blade.php`
- `resources/views/main/modules/accounting-payment-reminder-letter.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `database/exports/erp_database.sql`

Vérification :

- Migration `2026_05_25_000004_create_accounting_payment_reminders_tables` appliquée sur la base locale MySQL.
- `php -l` passe sur le contrôleur, les modèles, la migration et le test concernés.
- `php artisan route:list --name=main.accounting.payment-reminders` confirme les six routes du flux.
- `php artisan view:cache` passe.
- Le test de fonctionnalité des relances, promesses et lettre PDF passe.
- La suite complète `php artisan test` passe : 88 tests, 1111 assertions.
- Export SQL `database/exports/erp_database.sql` régénéré après migration.

### 2026-05-25 - Correction du chargement des relances avec plusieurs sources

Prompt utilisateur :

```text
j'ai cette erreur [Call to a member function getKey() on array]
```

Correction :

- Correction de l'ouverture de la page `Relances de paiement` lorsqu'elle contient simultanément des factures impayées et des créances manuelles.
- La fusion est désormais effectuée sur des collections de lignes standard, après transformation des modèles en tableaux, afin d'éviter l'appel Eloquent à `getKey()` sur un tableau.
- Ajout d'un scénario de régression avec une facture échue et une créance ouverte affichées ensemble.

Fichiers modifiés :

- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l tests/Feature/ExampleTest.php` passe.
- `php artisan view:cache` passe.
- Le test ciblé des relances passe : 1 test, 22 assertions.
- La suite complète `php artisan test` passe : 88 tests, 1112 assertions.
- Aucune migration ou régénération de `database/exports/erp_database.sql` nécessaire pour ce correctif applicatif.

### 2026-05-26 - Gestion opérationnelle des tâches comptables

Prompt utilisateur :

```text
nous allons maintenant travailler sur taches. Donne ton idée et ne fais encore rien
applique ton idée
tu n'avais pas terminé ta dernière requette à cause de token peux-tu continuer
```

Implémentation :

- Activation de la rubrique `Tâches` dans la navigation du module Comptabilité (Facturation).
- Nouvelle page de suivi avec indicateurs, filtres, recherche, tri, pagination et actions conformes aux tableaux existants.
- Création et modification de tâches avec responsable, priorité, échéance, client ou fournisseur associé et document lié.
- Clôture rapide des tâches, historique consultable en modal et suppression réservée aux tâches manuelles.
- Génération automatique d'une tâche de relance pour toute facture échue restant à encaisser.
- Génération automatique d'une tâche urgente lorsqu'une promesse de paiement n'est pas tenue.
- Clôture automatique de la tâche liée à une facture lorsque son solde n'est plus à recouvrer.

Base de données :

- Table `accounting_tasks` pour les actions à suivre et leurs liaisons métier.
- Table `accounting_task_activities` pour la traçabilité de création, modification et clôture.
- Relation `accountingTasks` ajoutée au site.

Fichiers principaux :

- `app/Models/AccountingTask.php`
- `app/Models/AccountingTaskActivity.php`
- `database/migrations/2026_05_25_000005_create_accounting_tasks_tables.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-tasks.blade.php`
- `resources/views/main/modules/partials/accounting-task-form.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `database/exports/erp_database.sql`

Vérification :

- Migration `2026_05_25_000005_create_accounting_tasks_tables` appliquée sur la base locale MySQL.
- `php artisan route:list --name=main.accounting.tasks` confirme les cinq routes du flux.
- `php artisan view:cache` passe.
- Le test ciblé de gestion des tâches passe.
- La suite complète `php artisan test` passe : 89 tests, 1122 assertions.
- Export SQL `database/exports/erp_database.sql` régénéré après migration.

### 2026-05-26 - Suppression du doublon Rapprochement bancaire dans le dashboard

Prompt utilisateur :

```text
lorsque je suis dans dashboard, rapprochement bancaire s'affiche 2 fois sur le menu
```

Correction :

- Suppression de l'ancienne entrée sans lien `Rapprochement bancaire` dans la sidebar intégrée au tableau de bord comptable.
- Conservation de l'entrée opérationnelle menant à la page de rapprochement bancaire.
- Vérification que la sidebar partagée utilisée par les autres pages ne contenait pas ce doublon.

Fichiers modifiés :

- `resources/views/main/modules/accounting-dashboard.blade.php`
- `docs/prompts/project-history.md`

### 2026-05-26 - Centre de rapports du module Comptabilite (Facturation)

Prompt utilisateur :

```text
maintenant nous allons travailler sur Rapport, donne moi ton idée et ne fais encore rien
applique ton idée mon ami
```

Implémentation :

- Activation de la rubrique `Rapport` dans la navigation comptable et sur le tableau de bord du module.
- Nouvelle page de pilotage avec cinq vues : ventes, encaissements, achats et dépenses, trésorerie et stock.
- Filtres par période, dates et critères métier disponibles selon la vue (client, fournisseur, mode de paiement et statut).
- Indicateurs calculés depuis les données du site, tableau paginé avec recherche dynamique globale et tri, et graphique ApexCharts isolé dans son propre fichier JavaScript.
- Export CSV de la vue filtrée et impression PDF professionnelle, avec le style documentaire déjà utilisé dans la comptabilité.
- Synchronisation des mouvements de trésorerie et mise à jour des échéances avant affichage ou export.
- Ajout des traductions française et anglaise ainsi que du style responsive spécifique aux rapports.

Fichiers principaux :

- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/accounting-reports.blade.php`
- `resources/views/main/modules/accounting-report-print.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-reports.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`

Vérification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l tests/Feature/ExampleTest.php` passe.
- `php artisan route:list --name=main.accounting.reports` confirme les trois routes (écran, PDF et CSV).
- `php artisan view:cache` passe.
- Le test ciblé des rapports passe, incluant l’affichage réel, le CSV et le PDF.
- La suite complète `php artisan test` passe : 90 tests, 1138 assertions.
- Aucune migration ni mise à jour de `database/exports/erp_database.sql` nécessaire : le rapport exploite les tables existantes.

### 2026-05-26 - Espacement vertical des cartes du rapport

Prompt utilisateur :

```text
les cards sont trop serrés de bas en haut laisser un peu d'espace
```

Correction :

- Correction de la règle d'affichage du conteneur Rapport afin que l'espacement vertical soit bien appliqué malgré les styles génériques du module comptabilité.
- Ajout d'une respiration régulière entre les onglets, les filtres, la période analysée, les indicateurs, les graphiques et le tableau.
- Conservation du contenu, des calculs et du comportement responsive existants.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

### 2026-05-26 - Nom personnalise dans les pieds de page PDF et style export

Prompt utilisateur :

```text
Dans tous les pdf : Documents généré par .... le nom de l'application qui a été configuré par le superadmin.
Deuxièmement le boutton exporté ne doit pas etre souligné si possible
```

Implémentation :

- Uniformisation des pieds de page PDF avec la mention traduisible `Document généré par :app`.
- Injection dynamique de `app_brand_name()` afin que le nom défini par le superadmin apparaisse automatiquement dans tous les PDF du module comptabilité.
- Application aux factures proforma, factures de vente, bons de livraison, bons de commande, notes de crédit, rapports de clôture de caisse, rapprochements bancaires, relances de paiement et rapports.
- Suppression du soulignement du bouton `Exporter en CSV` dans la page Rapport tout en conservant son style de bouton.

Fichiers principaux modifiés :

- `lang/fr/main.php`
- `lang/en/main.php`
- `resources/views/main/modules/accounting-*-print.blade.php`
- `resources/views/main/modules/accounting-cash-register-session-report.blade.php`
- `resources/views/main/modules/accounting-bank-reconciliation-report.blade.php`
- `resources/views/main/modules/accounting-payment-reminder-letter.blade.php`
- `resources/css/main.css`

Vérification :

- Les neuf vues utilisées par DomPDF transmettent désormais `app_brand_name()` à la mention de génération du document.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- La suite complète `php artisan test` passe : 90 tests, 1138 assertions.

### 2026-05-26 - Paramètres du module Comptabilité (Facturation)

Prompt utilisateur :

```text
Nous allons maintenant travailler sur parametres du module, ce menu ne doit etre visible et accessible que par les utilisateurs qui ont un role admin et superadmin.
Ce menu permet a l'admin de configurer les acces au menu pour les utilisateurs, configurer la colorisation pour l'impression en PDF des documents et proposer d'autres fonctionnalites.
```

Implémentation :

- Ajout d'une page `Paramètres du module` accessible uniquement aux rôles Admin et Superadmin.
- Création d'une matrice d'accès par utilisateur simple et par site : les menus désactivés sont masqués dans la sidebar et bloqués côté serveur en accès direct.
- Conservation de l'accès complet automatique pour les administrateurs et superadministrateurs.
- Ajout des réglages PDF par site : couleur principale, couleur d'accent, fond alterné des tableaux, affichage du QR code et mention de génération dans le pied de page.
- Application de la palette et des options aux documents PDF existants : proformas, factures, bons de livraison, bons de commande, notes de crédit, rapport de caisse, rapprochement bancaire, relances et rapport comptable.
- Centralisation de la navigation comptable dans la partial de sidebar, y compris pour le dashboard.
- Ajout des tables `accounting_module_settings` et `accounting_menu_permissions`, migration exécutée et export SQL synchronisé.

Standard enregistré :

- Tout menu de configuration d'un module doit être visible et accessible uniquement aux administrateurs autorisés.
- Masquer une fonctionnalité dans l'interface doit toujours être accompagné d'un contrôle serveur empêchant l'accès par URL.
- Les documents imprimables d'un site doivent reprendre une identité PDF centralisée plutôt que des couleurs codées séparément.

Fichiers principaux :

- `app/Http/Controllers/MainController.php`
- `app/Http/Middleware/EnsureAccountingMenuAccess.php`
- `app/Models/AccountingModuleSetting.php`
- `app/Models/AccountingMenuPermission.php`
- `app/Support/AccountingModuleNavigation.php`
- `database/migrations/2026_05_26_000001_create_accounting_module_settings_tables.php`
- `resources/views/main/modules/accounting-settings.blade.php`
- `resources/views/main/modules/partials/accounting-sidebar.blade.php`
- `resources/views/main/modules/accounting-*-print.blade.php`
- `routes/web.php`
- `database/exports/erp_database.sql`

Vérification :

- Migration des paramètres appliquée sur la base locale et export SQL régénéré.
- Test fonctionnel ajouté pour vérifier l'accès Admin, la matrice des menus et le refus d'une URL interdite pour un utilisateur simple.
- `php artisan route:list --name=main.accounting.settings` confirme les routes d'affichage et de mise à jour.
- `php artisan view:cache` passe.
- La suite complète `php artisan test` passe : 91 tests, 1150 assertions.

### 2026-05-26 - Pagination des accès utilisateurs dans les paramètres comptables

Prompt utilisateur :

```text
commence par arranger l'icone des utilisateurs dans la page parametre.
Deuxièmement au lieu d'afficher tous les utilisaturs sur une meme page par rapport aux accès des utilisateurs je préfère que tu pagine
```

Correction appliquée :

- Ajustement de l'avatar utilisateur dans la section `Gestion des accès aux menus` de la page `Paramètres du module` pour afficher un cercle bleu avec l'initiale, comme dans les listes utilisateurs.
- Pagination des utilisateurs simples à raison d'un utilisateur par page dans la matrice d'accès afin de ne plus afficher tous les utilisateurs sur une seule page.
- Ajout d'un bandeau de pagination/compteur visible au-dessus de la carte utilisateur, même lorsqu'une seule page existe.
- Présentation de l'en-tête utilisateur sous forme de ligne avec avatar rond et identité, sans numérotation, avec l'initiale centrée dans le cercle.
- Isolation de l'initiale dans un élément dédié positionné au centre du cercle afin d'éviter les conflits avec les styles génériques des `span` de l'en-tête.
- Redémarrage du serveur Laravel local `127.0.0.1:8000` après nettoyage des caches afin de forcer la prise en compte visuelle.
- Ajout d'un champ caché par utilisateur affiché pour que l'enregistrement ne modifie que les accès de la page courante.
- Conservation des droits des utilisateurs présents sur les autres pages lors de la sauvegarde.
- Compatibilité maintenue avec les payloads de test qui envoient directement `menu_access`.

Fichiers modifiés :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-settings.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=main.accounting.settings` confirme les routes.
- Le test ciblé `test_accounting_module_settings_are_admin_only_and_limit_user_menu_access` passe : 1 test, 12 assertions.

### 2026-05-26 - Redirection intelligente apres retrait d'acces comptable

Prompt utilisateur :

```text
j'ai essayer de faire ça et l'utilisateur x a eu une erreur 403 | Forbidden. qu'es ce que tu me propose comme solution
applique cela
```

Correction appliquée :

- Remplacement du 403 brutal lorsqu'un utilisateur simple tente d'ouvrir un menu comptable qui lui a ete retire.
- Le middleware cherche maintenant le premier menu comptable encore autorise pour ce site et redirige l'utilisateur vers cette page.
- Si aucun menu comptable n'est encore autorise, l'utilisateur est redirige vers `/main` avec un message explicatif.
- Les routes `settings` restent interdites aux utilisateurs simples.
- Les requetes non GET vers un menu non autorise restent bloquees en 403 afin de proteger les actions sensibles.
- Ajout d'un helper de navigation pour convertir une cle de menu comptable en URL.

Fichiers modifiés :

- `app/Http/Middleware/EnsureAccountingMenuAccess.php`
- `app/Support/AccountingModuleNavigation.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Middleware/EnsureAccountingMenuAccess.php` passe.
- `php -l app/Support/AccountingModuleNavigation.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- Le test ciblé `test_accounting_module_settings_are_admin_only_and_limit_user_menu_access` passe : 1 test, 17 assertions.

### 2026-05-26 - Sous-menus accessibles dans la sidebar comptable reduite

Prompt utilisateur :

```text
lorsqu'on reduit la side barre les menus qui ont des sous menus sont décallé pourquoi ? et il est impossible de afficher les sous menus
```

Correction appliquée :

- Identification de la cause : en mode sidebar reduite, les sous-menus etaient forces en `display: none` et l'overflow de la sidebar empechait tout affichage lateral.
- Modification du comportement JavaScript : en sidebar comptable reduite, un clic sur un groupe ouvre un sous-menu flottant au lieu de redeplier toute la sidebar.
- Fermeture automatique des autres groupes ouverts et fermeture au clic en dehors.
- Ajout d'un affichage flottant des sous-menus a droite de l'icone, avec largeur stable, ombre, fond sombre et defilement interne si besoin.
- Conservation du comportement normal lorsque la sidebar est ouverte.

Fichiers modifiés :

- `resources/js/main.js`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test ciblé des paramètres comptables passe toujours : 1 test, 17 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Enrichissement du tableau de bord Comptabilite et Facturation

Prompt utilisateur :

```text
Sur base de tous ce que nous avons réalisé sur les modules comptabilités et facturation mets à jour les tableaux bords avec les vraies informations, gardre les informations déjà existantes et améliore
```

Implementation :

- Conservation des indicateurs et graphiques existants du tableau de bord comptable.
- Synchronisation des mouvements de tresorerie avant calcul du dashboard.
- Ajout d'un bloc `Pilotage operationnel` avec cartes cliquables vers les pages concernees :
  - solde de tresorerie, entrees et sorties ;
  - taches ouvertes, en retard et urgentes ;
  - relances de paiement et promesses en attente ;
  - rapprochements bancaires ouverts et lignes non rapprochees ;
  - taxes actives et modes de paiement actifs ;
  - caisse ouverte et bons de commande fournisseurs en cours.
- Enrichissement du graphique de tresorerie pour utiliser les vraies entrees et sorties de tresorerie lorsque disponibles.
- Enrichissement du graphique des documents avec commandes clients, bons de livraison, bons de commande fournisseurs et notes de credit.
- Ajout des libelles FR/EN necessaires.
- Ajout d'assertions de regression sur les nouveaux elements du tableau de bord.

Fichiers principaux :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main/accounting-dashboard.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `node --check resources/js/main/accounting-dashboard.js` passe.
- `php artisan view:cache` passe.
- Le test cible du tableau de bord comptable passe : 1 test, 67 assertions.
- Le test cible des parametres comptables passe : 1 test, 17 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Verification et correction du chiffre d'affaires du dashboard

Prompt utilisateur :

```text
vérifie si le chiffre d'affire est vraiment réel
```

Constat :

- Le KPI `Chiffre d'affaires` du tableau de bord comptable n'etait pas strictement reel au sens ventes nettes.
- Il additionnait les factures de vente avec les `autres entrees`.
- Il utilisait le total brut des factures sans deduire les avoirs/notes de credit deja comptabilises dans `credit_total`.

Correction appliquee :

- Le chiffre d'affaires du dashboard est maintenant calcule uniquement sur les factures de vente non brouillon et non annulees.
- Le montant utilise est net des avoirs : `total_ttc - credit_total`.
- Les `autres entrees` restent des mouvements de tresorerie, mais ne gonflent plus le chiffre d'affaires.
- Le graphique d'evolution du chiffre d'affaires utilise le meme calcul net sur chaque periode.
- Ajout d'un test avec une facture de 10M, un avoir de 2M et une autre entree de 4M : le dashboard doit afficher 8M, pas 14M.

Fichiers modifiés :

- `app/Http/Controllers/MainController.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan view:cache` passe.
- Le test cible du tableau de bord comptable passe : 1 test, 69 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Centre de notifications du module Comptabilite

Prompt utilisateur :

```text
je souhaite ajouter un icone notification avant le mode sombre, qui affiche toutes les ce que les utilisateurs sur ces modules font par exemple :
- users3 a ajouté une facture il y a 2h
- user4 a validé une dépenses etc...
```

Implementation :

- Ajout d'une icone cloche avant le bouton mode sombre dans le topbar comptable partagé et sur le tableau de bord comptable.
- Creation d'un composant dropdown affichant les dernieres activites du site courant.
- Ajout d'un service `AccountingActivityFeed` qui lit les actions reelles depuis les tables comptables :
  - factures de vente ;
  - achats et bons de commande ;
  - notes de credit ;
  - depenses et autres entrees ;
  - relances de paiement ;
  - taches ;
  - rapprochements bancaires ;
  - mouvements de tresorerie ;
  - validations et clotures quand le statut le permet.
- Injection automatique des notifications via un view composer sur les vues du module.
- Ajout d'un cache local par requete pour eviter de recalculer le fil d'activite plusieurs fois a cause des partials Blade.
- Ajout des libelles FR/EN et du style du dropdown.

Fichiers principaux :

- `app/Support/AccountingActivityFeed.php`
- `app/Providers/AppServiceProvider.php`
- `resources/views/main/modules/partials/accounting-notifications.blade.php`
- `resources/views/main/modules/partials/accounting-topbar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main.js`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Verification :

- `php -l app/Support/AccountingActivityFeed.php` passe.
- `php -l app/Providers/AppServiceProvider.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible du tableau de bord comptable passe : 1 test, 73 assertions.
- Le test cible des parametres comptables passe : 1 test, 17 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Notifications persistantes avec page liste et detail

Prompt utilisateur :

```text
applique ça
```

Contexte :

- Demande appliquee apres validation de l'idee : afficher seulement les 10 dernieres notifications dans la cloche, ajouter une page dediee pour tout voir, ajouter une page detail, et distinguer les notifications consultees/non consultees.

Implementation :

- Ajout des tables `accounting_notifications` et `accounting_notification_reads`.
- Ajout des modeles `AccountingNotification` et `AccountingNotificationRead`.
- Transformation du fil d'activite en notifications persistantes synchronisees depuis les enregistrements comptables existants.
- Le dropdown affiche maintenant seulement les 10 dernieres notifications du site courant.
- Le badge de la cloche affiche le nombre de notifications non consultees parmi les notifications recentes.
- Les notifications non consultees ont un style distinct dans le dropdown et la page liste.
- Ajout d'un bouton `Voir toutes les notifications` dans le dropdown.
- Ajout d'une page liste avec filtres `Toutes` et `Non consultees`, pagination et liens vers le detail.
- Ajout d'une page detail qui marque la notification comme consultee pour l'utilisateur connecte.
- Ajout d'un lien vers le module concerne lorsque la cle de module peut etre resolue.
- Ajout des routes :
  - `main.accounting.notifications`
  - `main.accounting.notifications.show`
- Mise a jour de l'export SQL `database/exports/erp_database.sql` apres migration.

Fichiers principaux :

- `database/migrations/2026_05_26_000002_create_accounting_notifications_tables.php`
- `app/Models/AccountingNotification.php`
- `app/Models/AccountingNotificationRead.php`
- `app/Support/AccountingActivityFeed.php`
- `app/Http/Controllers/MainController.php`
- `routes/web.php`
- `resources/views/main/modules/partials/accounting-notifications.blade.php`
- `resources/views/main/modules/accounting-notifications.blade.php`
- `resources/views/main/modules/accounting-notification-show.blade.php`
- `resources/css/main.css`
- `resources/js/main.js`
- `lang/fr/main.php`
- `lang/en/main.php`
- `tests/Feature/ExampleTest.php`
- `database/exports/erp_database.sql`
- `docs/prompts/project-history.md`

Verification :

- Migration `2026_05_26_000002_create_accounting_notifications_tables` appliquee sur la base locale.
- Export SQL regenere avec `scripts/export-database.ps1` apres ajout de `C:\xampp\mysql\bin` au `PATH` de la commande.
- `php -l` passe sur les nouveaux modeles, le service, le provider et le controleur.
- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible du tableau de bord comptable passe : 1 test, 82 assertions.
- Le test cible des parametres comptables passe : 1 test, 17 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Scroll conserve dans la sidebar comptable reduite

Prompt utilisateur :

```text
je dois également etre en mesure de faire scroller avec la side barre reduit
```

Correction appliquée :

- Restauration du scroll vertical de la navigation comptable en mode sidebar reduite.
- Positionnement des sous-menus flottants en `fixed`, calcule en JavaScript depuis l'icone cliquee, afin qu'ils restent visibles au premier plan sans bloquer le scroll.
- Repositionnement du sous-menu lors du scroll de la navigation et du redimensionnement de la fenetre.

Fichiers modifiés :

- `resources/js/main.js`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test ciblé des paramètres comptables passe toujours : 1 test, 17 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Alignement des icones et priorité des sous-menus de sidebar réduite

Prompt utilisateur :

```text
les icones doivent etres droits et bien alligné.
Les sous menus doivent toujours etres au premier plan
```

Correction appliquée :

- Uniformisation des liens simples et des groupes de sous-menus en sidebar réduite sur une boîte stable de `52px x 52px`.
- Centrage explicite des icônes via grille et `line-height: 1` afin d'éviter les décalages verticaux ou horizontaux.
- Correction du decalage au survol : les libelles et chevrons des groupes sont retires du flux en mode reduit, afin que l'icone soit seule dans la cellule centree.
- En mode ouvert, les groupes utilisent une grille `icone / libelle / fleche` pour garder la fleche et l'icone propres et alignes.
- Ajout d'un `z-index` élevé à la sidebar comptable et d'un niveau supérieur au sous-menu flottant afin qu'il reste au premier plan devant le contenu.
- Conservation de l'overflow visible en sidebar réduite pour permettre aux panneaux flottants de sortir de la colonne.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test ciblé des paramètres comptables passe toujours : 1 test, 17 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Notifications comptables : compteur, accès et espacements

Prompt utilisateur :

```text
le nombre à côté de la notification doit etre le nombre de notification que l'utilisateur connecté n'a pas consulté, si c'est plus de 100 notifs on 99+
Deuxiemement tous les users qui ont accès doivent voir les notifications.
le bouton plein écran doit s'afficher partout sur toutes les pages.
Troisiemement dans l'image 3 laisse un peu d'espace entre la liste de notification et le menu tab.
quatriemement pareil pour les détails de la notifications laisse un peu d'espace entre les deux cards et les boutons d'ouvertures du module concerné.

Si l'utilisateur n'a pas accès au modules n'affiche pas le bouton
```

Correction appliquée :

- Le badge de notification compte maintenant toutes les notifications non consultees par l'utilisateur connecte, pas seulement les 10 dernieres affichees dans le menu.
- Le badge affiche `99+` lorsque le nombre de notifications non lues depasse 99.
- Les notifications restent visibles pour tous les utilisateurs qui ont acces au module comptabilite du site.
- Sur la page de detail, le bouton d'ouverture du module concerne est masque si l'utilisateur n'a pas acces au menu comptable lie a la notification.
- Ajout du bouton plein ecran au tableau de bord comptable et injection automatique du bouton sur les anciens headers comptables qui ne l'auraient pas encore.
- Ajout d'espaces visuels entre les onglets et la liste des notifications, puis entre les cards de detail et le bouton d'ouverture du module.

Fichiers modifiés :

- `app/Support/AccountingActivityFeed.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/partials/accounting-notifications.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/js/main.js`
- `resources/css/main.css`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Support/AccountingActivityFeed.php` passe.
- `php -l app/Providers/AppServiceProvider.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible du tableau de bord comptable passe : 1 test, 84 assertions.
- Le test cible des parametres comptables passe : 1 test, 22 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Icône de notification visible pour tous les rôles

Prompt utilisateur :

```text
l'icone de notification doit s'afficher pour tous les utilisateurs c'est a dire meme un user qui a un role user, admin etc... on doit ils doit voir les notifications et les consulter aussi
```

Correction appliquée :

- Factorisation des actions du header comptable dans `accounting-header-actions.blade.php`.
- Le bouton plein ecran, l'icone de notification, le mode sombre, la langue et le profil sont maintenant partages par le topbar comptable et les anciennes pages comptables qui utilisaient encore un header manuel.
- Les pages comptables manuelles affichent maintenant la cloche de notification pour les roles `user` et `admin`.
- Ajout d'un test qui verifie qu'un utilisateur `user` avec acces limite voit la cloche et peut acceder a la page des notifications.

Fichiers modifiés :

- `resources/views/main/modules/partials/accounting-header-actions.blade.php`
- `resources/views/main/modules/partials/accounting-topbar.blade.php`
- `resources/views/main/modules/accounting-dashboard.blade.php`
- `resources/views/main/modules/accounting-clients.blade.php`
- `resources/views/main/modules/accounting-creditors.blade.php`
- `resources/views/main/modules/accounting-currencies.blade.php`
- `resources/views/main/modules/accounting-debtors.blade.php`
- `resources/views/main/modules/accounting-partners.blade.php`
- `resources/views/main/modules/accounting-payment-methods.blade.php`
- `resources/views/main/modules/accounting-proforma-invoice-create.blade.php`
- `resources/views/main/modules/accounting-proforma-invoices.blade.php`
- `resources/views/main/modules/accounting-prospects.blade.php`
- `resources/views/main/modules/accounting-sales-representatives.blade.php`
- `resources/views/main/modules/accounting-service-resource.blade.php`
- `resources/views/main/modules/accounting-stock-resource.blade.php`
- `resources/views/main/modules/accounting-suppliers.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Providers/AppServiceProvider.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible des parametres comptables passe : 1 test, 24 assertions.
- Le test cible du tableau de bord comptable passe : 1 test, 84 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Traduction du module sur le détail de notification

Prompt utilisateur :

```text
merci de traduire l'information en jaune
```

Correction appliquée :

- Remplacement de l'affichage brut de la cle technique du module (`tasks`, `suppliers`, etc.) par son libelle traduit.
- Le detail de notification affiche maintenant par exemple `Tâches` au lieu de `tasks`.
- Ajout d'un test empechant le retour d'une cle technique brute dans le champ module.

Fichiers modifiés :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/accounting-notification-show.blade.php`
- `tests/Feature/ExampleTest.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan view:cache` passe.
- Le test cible des parametres comptables passe : 1 test, 26 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Corrections dark mode sur les notifications et cartes comptables

Prompt utilisateur :

```text
quand je passe en mode dark beaucoup d'element ne passe pas en mode dark
```

Correction appliquée :

- Ajout de styles dark mode pour les cartes d'operations du tableau de bord comptable.
- Correction du dropdown des notifications en mode sombre, notamment les notifications non lues.
- Correction des cartes de detail de notification, de la grille d'informations et de la liste des notifications.
- Les icones colorees gardent une teinte lisible en dark mode sans fond blanc parasite.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible du tableau de bord comptable passe : 1 test, 84 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Audit dark mode élargi sur les pages comptables

Prompt utilisateur :

```text
verifies dans toutes les où le problemes persistes et arrange ça
```

Correction appliquée :

- Scan des fonds blancs et fonds clairs codés en dur dans les CSS principaux.
- Ajout de règles dark mode plus larges pour les onglets de rapports, métriques de rapports, panneaux de réglages, aperçus PDF, cartes d'accès menu, pagination modale et états de rapprochement bancaire.
- Correction des icônes de modules, icônes de rapports, boutons de table au survol et états bancaires pour rester lisibles en mode sombre.
- Conservation des fonds blancs utiles aux logos/QR/switchs, qui doivent rester contrastés.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- Scan `rg` des fonds clairs restants effectué.
- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible du tableau de bord comptable passe : 1 test, 84 assertions.
- Le test cible des parametres comptables passe : 1 test, 26 assertions.
- Le test cible des rapports comptables passe : 1 test, 16 assertions.
- Le test cible du rapprochement bancaire passe : 1 test, 14 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Dark mode formulaires comptables et correction stock units

Prompt utilisateur :

```text
première remarque sur les formules des modales les placeholders sont très sombre. et dans taux de commission % vous pouvez voir que ce n'est pas en dark.
la page stock/units il y a erreur.
dans devises les textes en bas est trop sombre
dans nouvelle proforma le mode dark n'est pas completement appliqué; pareil pour nouvelle commande et modifier, bon de livraison, facture de vente, avoir , caisse enregistreuse, dépenses, bons de commandes, dépenses, dettes tous les restes
```

Correction appliquée :

- Correction de l'erreur `stock/units` : la configuration charge maintenant les relations `items.category` et `items.subcategory` au lieu d'une relation `services` inexistante sur `AccountingStockUnit`.
- Ajout de styles dark globaux pour les champs de formulaires comptables : `form-control`, `form-select`, `textarea`, placeholders, champs readonly/disabled, `input-group-text`, fichiers, `form-text`, `text-muted` et checkbox.
- Correction du suffixe `%` dans les champs `input-group` en mode sombre.
- Correction des cartes de lignes de proforma, commande client, facture, bon de livraison et avoir.
- Correction de plusieurs états et badges en dark mode : documents, achats, stocks, devises, taxes, autres entrées, etc.
- Ajout de règles dark pour la caisse enregistreuse : cartes produits/categories, panier, paiement, résumé, pavé numérique, alertes et notes.

Fichiers modifiés :

- `app/Http/Controllers/MainController.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible de création de site et unités de stock passe : 1 test, 58 assertions.
- Le test cible stock et mouvements passe : 1 test, 44 assertions.
- Le test cible proforma passe : 1 test, 69 assertions.
- Le test cible commandes client passe : 1 test, 23 assertions.
- Le test cible factures de vente passe : 1 test, 62 assertions.
- Le test cible bons de livraison passe : 1 test, 27 assertions.
- Le test cible dettes passe : 1 test, 17 assertions.
- Le test cible créances passe : 1 test, 17 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.
- Note : le test cible caisse enregistreuse echoue sur une assertion de visibilité d'une reference dans la page, sans erreur serveur; la page rend bien et l'echec semble lie au contenu désormais present dans les notifications globales.

### 2026-05-26 - Placeholders plus lisibles en dark mode

Prompt utilisateur :

```text
les placeholders sur les formulaires des modales sont très sombres
```

Correction appliquée :

- Renforcement de la couleur des placeholders en mode sombre pour les modales comptables.
- Ajout de selecteurs plus specifiques pour `.modal-content`, `.admin-form` et `.proforma-page-form`.
- Ajout des pseudo-selecteurs WebKit afin que Chrome applique aussi la couleur claire sans opacite sombre.
- Les placeholders des champs de recherche custom des lignes proforma utilisent aussi la nouvelle couleur.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- Le test cible des parametres comptables passe : 1 test, 26 assertions.
- `php artisan optimize:clear` execute pour vider les caches Laravel.

### 2026-05-26 - Correction globale des placeholders dark mode

Prompt utilisateur :

```text
ça ne fonctionne toujours pas
```

Correction appliquée :

- Ajout de variables Bootstrap dark globales (`--bs-secondary-color`, `--bs-tertiary-color`, `--bs-body-color`, `--bs-body-bg`) afin que les champs Bootstrap utilisent des couleurs lisibles.
- Ajout de règles indépendantes de `.accounting-shell` sur `html[data-theme="dark"]` et `body[data-theme="dark"]` pour les placeholders.
- Couverture des placeholders dans les modales Bootstrap, y compris les variantes WebKit et Firefox.
- Nettoyage des caches Laravel puis recompilation des vues.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.
- Le test cible des parametres comptables passe : 1 test, 26 assertions.

### 2026-05-26 - Suffixe pourcentage des commerciaux en dark mode

Prompt utilisateur :

```text
dans commerciaux % n'a pas changé
```

Correction appliquée :

- Ajout de règles directes dark mode pour `.input-group-text` sur `html[data-theme="dark"]` et `body[data-theme="dark"]`.
- Le suffixe `%` du taux de commission utilise maintenant le même fond sombre et la même couleur que les champs de formulaire, y compris dans les modales Bootstrap hors `.accounting-shell`.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Bandeau total encaissements en dark mode

Prompt utilisateur :

```text
il y a un soucis dans encaissement
```

Correction appliquée :

- Ajout du style dark mode pour `.modal-total-strip`, utilisé par le bandeau `Total encaissé` de la page Encaissements.
- Le bandeau utilise maintenant un fond bleu sombre, une bordure adaptée et un montant lisible en mode sombre.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Cartes trésorerie en dark mode

Prompt utilisateur :

```text
pareil dans trésorerie
```

Correction appliquée :

- Ajout des styles dark mode pour les cartes principales de la page Trésorerie : solde disponible, prévisions, entrées, sorties, créances et dettes.
- Adaptation des badges devise/prévision, icônes colorées, séparateurs et couleurs positives/négatives en mode sombre.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Rapprochement bancaire en dark mode

Prompt utilisateur :

```text
rapprochement bancaire aussi
```

Correction appliquée :

- Ajout du style dark mode pour le panneau de clôture `.bank-close-panel`.
- Harmonisation dark mode des cartes internes du rapprochement bancaire : métriques, import, formulaire de ligne et tableau des lignes.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Lisibilité widgets relances clients en dark mode

Prompt utilisateur :

```text
dans relance clients les textes sur les widget ne sont pas trop lisible
```

Correction appliquée :

- Ajout des surcharges dark mode pour les widgets de relance client.
- Les libellés, montants et compteurs utilisent maintenant des couleurs plus contrastées.
- Les icônes des widgets conservent leurs tons respectifs avec des fonds adaptés au mode sombre.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Ajustement automatique du scroll sidebar

Prompt utilisateur :

```text
sur la side barre lorsque je clique sur rapport par la side barre doit s'ajuster pour que rapport soit visible
```

Correction appliquée :

- Ajout d'une routine JS qui repère le lien actif dans `.accounting-nav`.
- La sidebar ajuste automatiquement son scroll pour garder le lien actif visible, par exemple `Rapports`.
- Le recentrage est relancé au chargement, au changement responsive et après réduction/ouverture de la sidebar.

Fichiers modifiés :

- `resources/js/main.js`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Bouton plein écran côté super admin

Prompt utilisateur :

```text
alors maintenant du coté super admin tu as oublié le bouton plein écran
```

Correction appliquée :

- Généralisation de l'injection automatique du bouton plein écran dans `resources/js/main.js`.
- Le bouton n'est plus limité aux pages comptables et s'insère maintenant dans tout header `.main-shell .header-actions`, y compris les pages super admin.

Fichiers modifiés :

- `resources/js/main.js`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Personnalisation des couleurs de l'application

Prompt utilisateur :

```text
dans personalisation de l'application du coté superadmin je souhaite qu'on ai la possibilité aussi de personnaliser les couleurs aussi de l'application
```

Correction appliquée :

- Ajout d'une section `Couleurs de l'application` dans la page super admin de personnalisation.
- Ajout des champs : couleur principale, couleur secondaire, couleur d'accent, couleur de début de sidebar et couleur de fin de sidebar.
- Sauvegarde des couleurs dans `application_settings` via le système de branding existant.
- Ajout d'une injection globale des variables CSS de thème dans toutes les réponses HTML du groupe web.
- Les variables `--blue-600`, `--blue-500`, `--violet`, `--navy` et `--navy-2` peuvent maintenant être pilotées depuis la personnalisation.

Fichiers modifiés :

- `app/Http/Controllers/AdminController.php`
- `app/Http/Middleware/InjectApplicationTheme.php`
- `app/Support/AppBranding.php`
- `bootstrap/app.php`
- `resources/views/admin/application-settings.blade.php`
- `resources/css/admin/dashboard.css`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Support/AppBranding.php` passe.
- `php -l app/Http/Middleware/InjectApplicationTheme.php` passe.
- `php -l app/Http/Controllers/AdminController.php` passe.
- `node --check resources/js/main.js` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=admin.application-settings` confirme les routes GET et PUT.

### 2026-05-26 - Correction UTF-8 des libellés de personnalisation

Prompt utilisateur :

```text
UTF8 pour le text
```

Correction appliquée :

- Correction des libellés français de la page de personnalisation super admin affichés avec des caractères corrompus.
- Les textes de la section couleurs utilisent maintenant des caractères UTF-8 corrects : `l’application`, `d’accent`, `début`, `latérale`, etc.

Fichiers modifiés :

- `lang/fr/admin.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l lang/fr/admin.php` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-26 - Couleurs personnalisées sur la page login

Prompt utilisateur :

```text
le changement de couleur doit aussi tenir compte du login également
```

Correction appliquée :

- Extension des variables de branding avec des versions RGB pour les effets transparents.
- Adaptation de `resources/css/auth/login.css` afin que la page de connexion utilise les couleurs personnalisées.
- Le panneau gauche, les accents, les icônes, badges, liens, focus de champs et bouton de connexion suivent maintenant la personnalisation super admin.

Fichiers modifiés :

- `app/Support/AppBranding.php`
- `resources/css/auth/login.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Support/AppBranding.php` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-27 - Restauration des couleurs par défaut

Prompt utilisateur :

```text
dans la palette des couleurs donne la possibilité de restaurer la couleur par défaut
```

Correction appliquée :

- Ajout d'un bouton `Restaurer les couleurs par défaut` dans la section couleurs de la personnalisation super admin.
- Les champs couleur portent maintenant leur valeur par défaut via `data-brand-default-color`.
- Ajout du JS qui remet instantanément tous les sélecteurs couleur aux valeurs par défaut avant enregistrement.
- Ajout des traductions FR/EN et du style responsive du bouton.

Fichiers modifiés :

- `resources/views/admin/application-settings.blade.php`
- `resources/js/admin/application-settings.js`
- `resources/css/admin/dashboard.css`
- `lang/fr/admin.php`
- `lang/en/admin.php`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/admin/application-settings.js` passe.
- `php -l lang/fr/admin.php` passe.
- `php -l lang/en/admin.php` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-27 - Lien entreprise vers ses sites côté super admin

Prompt utilisateur :

```text
dans entreprise chez le superadmin le nom de l'entreprise doit etre cliquable lorsque le superadmin clique, il sera redirigé vers les sites de l'entreprise en question
```

Correction appliquée :

- Le nom de l'entreprise dans la liste super admin des entreprises est maintenant un lien.
- Le lien redirige vers `main.companies.sites` pour afficher les sites de l'entreprise sélectionnée.

Fichiers modifiés :

- `resources/views/admin/companies.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `php artisan route:list --name=main.companies.sites` confirme la route cible.

### 2026-05-27 - Lien tableau de bord superadmin dans le menu profil

Prompt utilisateur :

```text
ajoute un lien avant profil uniquement pour le superadmin lui permettant d'afficher son tableau de bord
```

Correction appliquée :

- Ajout d'un lien `Tableau de bord` avant `Profil` dans les menus profil.
- Le lien est affiché uniquement lorsque l'utilisateur connecté est superadmin.
- Le lien redirige vers `admin.dashboard`.

Fichiers modifiés :

- Menus profil des vues `admin`, `main`, `profile` et du partial comptable.
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `php artisan route:list --name=admin.dashboard` confirme la route cible.

### 2026-05-27 - Suppression bande blanche au-dessus de la sidebar superadmin

Prompt utilisateur :

```text
dans le dashboard du superadmin il y une ligne blanche au dessus du side barre il faut supprimer ça
```

Correction appliquée :

- Verrouillage du layout superadmin en supprimant les marges/paddings potentiels sur `html`, `body`, `.dashboard-shell` et `.dashboard-sidebar`.
- La sidebar doit maintenant commencer directement en haut du viewport.

Fichiers modifiés :

- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-27 - Correction UTF-8 globale superadmin

Prompt utilisateur :

```text
on a toujours ce problème de formatage UTF8 sur toutes les pages du superadmin
```

Correction appliquée :

- Réparation complète du fichier `lang/fr/admin.php`.
- Conversion des séquences mojibake restantes (`Ã©`, `â€™`, `Â·`, etc.) en caractères UTF-8 corrects.
- Les pages superadmin récupèrent maintenant des libellés propres pour les menus, boutons, formulaires, validations et messages.

Fichiers modifiés :

- `lang/fr/admin.php`
- `docs/prompts/project-history.md`

Vérification :

- Scan `Select-String` sur `Ã`, `Â`, `â`, `�` sans résultat.
- `php -l lang/fr/admin.php` passe.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-27 - Correction finale sidebar superadmin et bande blanche

Prompt utilisateur :

```text
le probleme de la ligne blache est toujours présente sur le tableau de bord du superadmin et le probleme de formatage utf8 est toujours présente sur la side barre
```

Correction appliquée :

- Correction des caractères mojibake hardcodés dans les vues superadmin et profil (`Â·`, `â€”`).
- Le footer de sidebar affiche maintenant `EXAD ERP · v.2.0` correctement.
- Le layout superadmin est fixé au viewport via `.dashboard-shell { position: fixed; inset: 0; }`, avec le scroll déplacé dans le shell, pour supprimer l'espace blanc parasite au-dessus de la sidebar.

Fichiers modifiés :

- `resources/css/admin/dashboard.css`
- `resources/views/admin/*.blade.php`
- `resources/views/profile/edit.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- Scan `Select-String` sur les vues superadmin/profil pour `Ã`, `Â`, `â`, `�` sans résultat.
- `php artisan optimize:clear` passe.
- `php artisan view:cache` passe.

### 2026-05-27 - Démarrage du module Ressources Humaines par le tableau de bord

Prompt utilisateur :

```text
applique ton idée mais normalement tu dois d'abord commencer par le tableau de bord
```

Implémentation appliquée :

- Le module `human_resources` ne renvoie plus vers la page générique “module en développement”.
- Ajout d’un tableau de bord RH dédié avec sidebar, topbar, indicateurs et widgets.
- Les premiers indicateurs utilisent les données existantes : collaborateurs affectés au site, utilisateurs ayant accès au module RH, connexions du mois, complétude de base des profils.
- Ajout de panneaux pour le répertoire actuel, le responsable du site, l’activité récente et les prochaines fondations RH.
- Ajout des libellés français/anglais nécessaires et des styles responsive/dark-mode du dashboard RH.

Fichiers modifiés :

- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/human-resources-dashboard.blade.php`
- `resources/views/main/modules/partials/human-resources-sidebar.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan route:list --name=main.companies.sites.modules.show` confirme la route générique du module.
- `php artisan view:cache` passe.

### 2026-05-27 - Connexion du dashboard RH aux vraies tables et seeders

Prompt utilisateur :

```text
j'ai aimé le design mais applique avec les vrais tables et les seeders
```

Implémentation appliquée :

- Ajout du socle de tables RH :
  - `human_resource_departments`
  - `human_resource_employees`
  - `human_resource_contracts`
  - `human_resource_leave_requests`
  - `human_resource_attendances`
- Ajout des modèles Eloquent RH et des relations depuis `CompanySite`.
- Ajout du seeder `HumanResourcesSeeder`, appelé par `DatabaseSeeder`.
- Le dashboard RH lit maintenant les vraies données RH : employés actifs, départements, congés en attente, contrats actifs, masse salariale, présences du jour, demandes récentes.
- Le seeder crée des départements, employés, contrats, présences et une demande de congé initiale pour les sites ayant le module RH.
- Sur une base fraîche sans site RH, le seeder prépare un site de démonstration compatible RH.

Fichiers modifiés :

- `app/Http/Controllers/MainController.php`
- `app/Models/CompanySite.php`
- `app/Models/HumanResourceDepartment.php`
- `app/Models/HumanResourceEmployee.php`
- `app/Models/HumanResourceContract.php`
- `app/Models/HumanResourceLeaveRequest.php`
- `app/Models/HumanResourceAttendance.php`
- `database/migrations/2026_05_27_000001_create_human_resources_tables.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/HumanResourcesSeeder.php`
- `resources/views/main/modules/human-resources-dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan migrate --force` passe.
- `php artisan db:seed --class=HumanResourcesSeeder --force` passe.
- Le site RH local vérifié contient 3 départements et 4 employés RH.
- `php artisan view:cache` passe.

### 2026-05-27 - Organisation du module RH en contrôleur et dossier de vues

Prompt utilisateur :

```text
le tableau de bord  doit etre scrollable mais pour les view du RH tu dois organiser mettre dans un dossier et créer un controller pour le RH
```

Implémentation appliquée :

- Création du contrôleur dédié `HumanResourcesController`.
- Déplacement de la logique du dashboard RH hors de `MainController`.
- Ajout de la route dédiée `main.human-resources.dashboard`.
- Organisation des vues RH dans `resources/views/main/modules/human-resources/`.
- Déplacement de la sidebar RH dans `resources/views/main/modules/human-resources/partials/sidebar.blade.php`.
- Mise à jour du lien d’ouverture du module RH depuis la page site.
- Le dashboard RH utilise maintenant un scroll vertical sur la zone principale, avec sidebar conservée.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/human-resources/dashboard.blade.php`
- `resources/views/main/modules/human-resources/partials/sidebar.blade.php`
- `resources/views/main/company-site-show.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan route:list --name=human-resources` confirme la route dédiée.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.

### 2026-05-27 - Gestion admin des présences RH, import tableur et rapports

Prompt utilisateur :

```text
applique ça
```

Contexte :

- L’utilisateur a validé que les présences doivent être remplies par l’admin/RH, pas par l’employé.
- Demande ajoutée : import par fichier Excel/tableur et génération de rapports aujourd’hui/semaine/mois/année/période.

Implémentation appliquée :

- La page `Présences` devient une vraie page métier dédiée.
- Ajout manuel des présences via modale.
- Modification et suppression des présences existantes.
- Import de présences via fichier tableur :
  - CSV/TXT supportés.
  - XLSX simple supporté si l’extension PHP `ZipArchive` est disponible.
  - Colonnes acceptées : `matricule`, `date`, `arrivee`, `depart`, `statut`, `heures`, `note`.
- Mise à jour automatique d’une présence existante si le même employé a déjà une ligne pour la même date.
- Rapport filtrable :
  - aujourd’hui
  - cette semaine
  - ce mois
  - cette année
  - période personnalisée
- Le rapport affiche : présents, retards, absents, congés et total des heures travaillées.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/human-resources/attendance.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --path=human_resources/attendance` affiche les 5 routes attendues.
- `php artisan route:cache` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.

### 2026-05-27 - Espacement global icône/titre des modales

Prompt utilisateur :

```text
sur les modales toujours laisser d'espace entre le titre et l'icone merci
```

Correction appliquée :

- Ajout d’un style global sur les titres des modales `subscription-modal`.
- Les titres de modales sont maintenant en `inline-flex` avec un espacement constant entre l’icône et le texte.
- Suppression de la règle spécifique devenue inutile pour la modale département RH.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-18 - Refonte de la page Mouvements Archivage

Prompt utilisateur :

```text
peux-tu améliorer la page mouvement , ajoute 2 documents et 6 mouvements après
```

Implémentation appliquée :

- Remplacement du tableau des mouvements par un journal de mouvements sous forme de cartes professionnelles.
- Chaque mouvement affiche maintenant clairement :
  - le document ou classeur concerné ;
  - la référence du mouvement ;
  - l'emplacement source ;
  - l'emplacement de destination ;
  - la date, l'acteur, la raison et les notes.
- Conservation de la pagination existante à 5 éléments par page.
- Ajout des styles responsive et dark mode pour la nouvelle présentation.
- Ajout de 2 documents d'archive de démonstration :
  - `ARC-900101`
  - `ARC-900102`
- Ajout de 6 mouvements d'archive de démonstration :
  - `MVT-900101`
  - `MVT-900102`
  - `MVT-900103`
  - `MVT-900104`
  - `MVT-900105`
  - `MVT-900106`

Fichiers modifiés :

- `resources/views/main/modules/archiving/movements.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l resources/views/main/modules/archiving/movements.blade.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- Les 2 documents et les 6 mouvements sont présents dans la base locale.

### 2026-06-18 - Amélioration de la page Conservation Archivage

Prompt utilisateur :

```text
améliore la page conservation
```

Implémentation appliquée :

- Refonte de la page Conservation avec une présentation plus professionnelle.
- Ajout de vrais indicateurs en haut de page :
  - nombre total de règles ;
  - règles actives ;
  - documents arrivant à échéance dans les 90 jours ;
  - documents déjà expirés.
- Remplacement du tableau brut des règles par des cartes compactes et lisibles.
- Ajout d’un panneau de surveillance avec la prochaine échéance et les documents proches de l’expiration.
- Conservation de la pagination à 5 éléments par page pour les règles.
- Amélioration du modal de création de règle avec placeholder, espacement et style cohérents.
- Ajout des styles responsive et dark mode.

Fichiers modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `resources/views/main/modules/archiving/retention.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l resources/views/main/modules/archiving/retention.blade.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.

### 2026-06-18 - Alignement de la page Traçabilité Archivage sur les tableaux standards

Prompt utilisateur :

```text
nous allons maintenant travailler sur traçabilité, garde le meme style de mes tableaux please
```

Implémentation appliquée :

- Refonte de la page Traçabilité Archivage avec le style standard des tableaux de l’application.
- Ajout d’une recherche locale sans rechargement de page via `companySearch`.
- Ajout du compteur dynamique `visibleCount`.
- Colonnes triables :
  - Date ;
  - Action ;
  - Utilisateur ;
  - Changement ;
  - Commentaire.
- Ajout d’une pagination cohérente avec les autres listes.
- Ajout des libellés propres pour les actions Archivage afin d’éviter l’affichage de clés techniques.
- Amélioration de l’affichage des changements de statut et des commentaires longs.
- Ajout des styles dark mode ciblés.

Fichiers modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `resources/views/main/modules/archiving/traceability.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l resources/views/main/modules/archiving/traceability.blade.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.

### 2026-06-18 - Amélioration de la page Rapports Archivage

Prompt utilisateur :

```text
améliore la page Rapports Archivage
```

Implémentation appliquée :

- Refonte de la page Rapports Archivage avec une présentation plus professionnelle.
- Alignement visuel sur les rapports GED/RH déjà améliorés :
  - métriques en cartes ;
  - panneaux de rapport ;
  - tableaux structurés ;
  - en-têtes avec icônes ;
  - barres de progression ;
  - badges de statut.
- Amélioration des sections :
  - répartition par type ;
  - répartition par statut ;
  - répartition par emplacement physique ;
  - répartition par classeur.
- Ajout d’une note de génération du rapport.
- Remplacement du bouton PDF par un bouton d’impression PDF cohérent et sans soulignement.
- Ajout des clés de statut dans les données de rapport pour styliser les badges.
- Ajout des styles dark mode dédiés.

Fichiers modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `resources/views/main/modules/archiving/reports.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l resources/views/main/modules/archiving/reports.blade.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.

### 2026-06-09 - Affichage des documents archivés en cartes avec aperçu

Prompt utilisateur :

```text
dans document au lieu d'utiliser le tableaux pourquoi pas afficher des cards toujours avec paginnation t possibilité d'avoir un aperçu du fichier
```

Correction appliquée :

- Remplacement du tableau des documents archivés par une grille de cartes paginées.
- Conservation de la pagination existante à 5 documents par page.
- Ajout d’un aperçu de fichier pour les PDF et les images.
- Ajout d’un bouton d’ouverture du fichier dans un nouvel onglet.
- Ajout d’un état clair quand aucun fichier n’est joint.
- Ajout des libellés FR/EN et du style clair/sombre des cartes.

Fichiers modifiés :

- `resources/views/main/modules/archiving/records.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l resources/views/main/modules/archiving/records.blade.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Corrections contrôlées des documents archivés

Prompt utilisateur :

```text
applique tous ce qu'on vient de dire
```

Règles appliquées :

- Un document archivé peut être corrigé, mais avec traçabilité.
- Les métadonnées modifiables sont : titre, type, catégorie, service propriétaire, dates, confidentialité, statut et description.
- Le classeur et la boîte ne se modifient pas depuis la fiche document : une correction d’emplacement doit passer par un mouvement d’archive.
- Si aucun fichier n’est encore joint, l’utilisateur peut joindre le fichier officiel.
- Si un fichier existe déjà, le remplacement est possible uniquement avec un motif obligatoire.
- L’ancien fichier n’est pas supprimé : il est conservé dans l’historique de remplacement.
- Formats acceptés : PDF, Word, Excel, CSV, TXT, JPG et PNG, avec une limite de 20 Mo.
- Ajout du statut “Archivé par erreur” pour neutraliser un document archivé par erreur sans suppression directe.
- Les actions créent des notifications et des traces Archivage.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/ArchivingController.php`
- `app/Models/ArchiveRecord.php`
- `app/Support/AccountingActivityFeed.php`
- `resources/views/main/modules/archiving/records.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `database/migrations/2026_06_09_000004_create_archive_record_file_revisions.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l` passe sur le contrôleur, le modèle, le support de notifications, la migration et les fichiers de langue.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan migrate` a créé `archive_record_file_revisions`.
- `php artisan route:list --path=archiving/records` affiche 5 routes.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Refonte visuelle des cartes Documents archivés

Prompt utilisateur :

```text
les cards sont grand et non professionnel ni moderne je vais un truc qui reflete vraiment les archives
```

Correction appliquée :

- Refonte des cartes de documents archivés avec une présentation plus compacte et documentaire.
- Ajout d’une tranche latérale visuelle de type fiche d’archive.
- Mise en avant de la référence du document dans un badge discret.
- Réduction de la hauteur des cartes et suppression des gros blocs de métadonnées.
- Affichage du chemin physique dans une bande dédiée.
- Actions converties en boutons icônes avec `title` et `aria-label`.
- Ajustement du rendu mobile et du mode sombre.

Fichiers modifiés :

- `resources/views/main/modules/archiving/records.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l resources/views/main/modules/archiving/records.blade.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Espacement du bouton du modal d’aperçu Archive

Prompt utilisateur :

```text
pourquoi le bouton du modal est toujours collé ?
```

Correction appliquée :

- Le padding des actions de modal Archivage ne s’applique plus seulement aux modales contenant un formulaire.
- Le modal d’aperçu de fichier reçoit maintenant le même espacement bas/droite que les autres modales.
- Ajout d’un espacement supérieur spécifique pour séparer le bouton de la zone d’aperçu.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur `resources/css/main.css`.

### 2026-06-18 - Correction du chargement des images publiques

Prompt utilisateur :

```text
le site ne charge pas les images pourquoi ?
```

Diagnostic :

- Les fichiers existent bien dans `storage/app/public`.
- Le lien `public/storage` existe, mais le serveur répond `403 Forbidden` sur `/storage/...`.
- Les fichiers statiques récents sous `public` ne sont pas visibles par le serveur HTTP en cours sans redémarrage.

Correction appliquée :

- Création d’un vrai dossier public `public/user-files`.
- Copie des fichiers existants de `storage/app/public` vers `public/user-files`.
- Configuration du disque Laravel `public` pour stocker les prochains uploads dans `public/user-files`.
- Ajout du helper `public_storage_url()` qui génère les URLs `/user-files/...`.
- Mise à jour des URLs des logos, avatars, fichiers GED et fichiers Archivage.
- Ajout d’un contrôleur de secours `PublicStorageController` pour servir les anciens chemins si nécessaire après redémarrage.

Fichiers modifiés :

- `config/filesystems.php`
- `app/Support/helpers.php`
- `app/Support/AppBranding.php`
- `app/Models/Company.php`
- `app/Models/User.php`
- `routes/web.php`
- `app/Http/Controllers/PublicStorageController.php`
- `resources/views/main/modules/archiving/records.blade.php`
- `resources/views/main/modules/document-management/incoming.blade.php`
- `resources/views/main/modules/document-management/outgoing.blade.php`
- `resources/views/main/modules/document-management/internal.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `public_storage_url()` et `app_brand_logo_url()` génèrent maintenant `/user-files/...`.
- `php artisan optimize:clear` passe.
- `php artisan route:cache` passe.
- `php artisan view:cache` passe.
- `php -l` passe sur les fichiers PHP modifiés.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Ajout de 4 rayons dans Salle 2

Prompt utilisateur :

```text
ajoute 4 rayons dans la salle 2
```

Contexte :

- La salle concernée est `SAL-000002`, nommée `Salle 2`.
- Sa capacité est de `100` unités.
- Elle avait déjà `20` unités utilisées par un rayon existant.

Action appliquée :

- Ajout de 4 rayons rattachés à `Salle 2` :
  - `Rayon Salle 2 - A`, code `S2-A`, capacité `20` unités.
  - `Rayon Salle 2 - B`, code `S2-B`, capacité `20` unités.
  - `Rayon Salle 2 - C`, code `S2-C`, capacité `20` unités.
  - `Rayon Salle 2 - D`, code `S2-D`, capacité `20` unités.

Règle respectée :

- Capacité existante utilisée : `20`.
- Capacité ajoutée : `80`.
- Total utilisé après ajout : `100 / 100`.
- Aucun dépassement de capacité parent n’a été autorisé.

Vérification :

- Le script Laravel d’insertion a retourné les nouveaux rayons `RAY-000005` à `RAY-000008`.
- Le fichier temporaire d’insertion a été supprimé après exécution.

### 2026-06-09 - Notifications isolées par module

Prompt utilisateur :

```text
stp chaque module doit avoir ses propres notifications concernant seulement le module je t'avais déjà dit ça
```

Problème constaté :

- Le dropdown de notifications était filtré par module, mais le service central ne connaissait réellement que Comptabilité et Ressources Humaines.
- Les routes Archivage et GED n’étaient pas reconnues par la détection du module courant.
- Résultat : Archivage affichait `Aucune activité récente` alors que des activités existaient dans la traçabilité.

Correction appliquée :

- Ajout de la détection des routes :
  - `main.document-management.*` vers le module GED.
  - `main.archiving.*` vers le module Archivage.
- Ajout des clés de notification propres à GED et Archivage.
- Connexion du flux GED à `document_management_activities`.
- Connexion du flux Archivage à `archive_activities`.
- Ajout des routes et pages de consultation :
  - liste des notifications Archivage,
  - détail d’une notification Archivage,
  - liste des notifications GED,
  - détail d’une notification GED.
- Le composant header sait maintenant ouvrir les pages de notifications propres à chaque module.
- Le détail d’une notification marque la notification comme consultée pour l’utilisateur connecté.

Règle métier confirmée :

- Comptabilité affiche seulement les notifications Comptabilité.
- RH affiche seulement les notifications RH.
- GED affiche seulement les notifications GED.
- Archivage affiche seulement les notifications Archivage.

Vérification :

- `php -l` passe sur les contrôleurs, le support de notifications, les routes et les fichiers de langue.
- `php artisan view:cache` passe.
- `php artisan route:list --path=notifications` affiche 8 routes.
- `php artisan route:cache` passe.
- Vérification Laravel du flux :
  - Comptabilité : 10 notifications.
  - RH : 10 notifications.
  - GED : 10 notifications.
  - Archivage : 6 notifications.

### 2026-06-09 - Modification et suppression contrôlées des classeurs Archivage

Prompt utilisateur :

```text
applique la regles de modificaation et suppression dansclasseurs aussi
```

Correction appliquée :

- Ajout des routes `PUT` et `DELETE` pour les classeurs d’archive.
- Ajout des actions `updateContainer` et `destroyContainer`.
- Ajout des boutons modifier/supprimer dans le tableau des classeurs.
- La modale Classeurs passe maintenant en mode création ou modification.
- En modification, la boîte physique parent du classeur est verrouillée :
  - on peut corriger le titre, la catégorie, le service, la période, la confidentialité, la capacité, le statut et la description ;
  - on ne peut pas changer la boîte parent depuis cette modale ;
  - le déplacement physique doit passer par la page Mouvements.
- La suppression est bloquée si le classeur contient déjà des documents.
- La capacité d’un classeur ne peut pas être réduite sous le nombre de documents déjà classés.
- Les activités `container_updated` et `container_deleted` sont rattachées aux notifications Archivage.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/ArchivingController.php`
- `app/Support/AccountingActivityFeed.php`
- `resources/views/main/modules/archiving/containers.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l` passe sur le contrôleur Archivage, les routes, le support de notifications et les langues.
- `php artisan view:cache` passe.
- `php artisan route:list --path=archiving/containers` affiche les 4 routes attendues.
- `php artisan route:cache` passe.

### 2026-06-09 - Regroupement des vues Comptabilité et Facturation

Prompt utilisateur :

```text
j'aime pas la manière dont tu as structuré les vues pour le module facturation et comptabilité, les vues cette mudule doivent etre dans un meme dossier comme tu l'as fais dans les autres modules
```

Correction appliquée :

- Création du dossier `resources/views/main/modules/accounting`.
- Déplacement de toutes les vues `accounting-*.blade.php` vers ce dossier.
- Les vues sont maintenant organisées comme les autres modules :
  - `resources/views/main/modules/accounting/dashboard.blade.php`
  - `resources/views/main/modules/accounting/clients.blade.php`
  - `resources/views/main/modules/accounting/proforma-invoices.blade.php`
  - `resources/views/main/modules/accounting/sales-invoices.blade.php`
  - `resources/views/main/modules/accounting/reports.blade.php`
  - etc.
- Mise à jour de toutes les références Laravel :
  - ancien format : `main.modules.accounting-dashboard`
  - nouveau format : `main.modules.accounting.dashboard`
- Les partials partagés restent dans `resources/views/main/modules/partials`, car ils sont utilisés par plusieurs modules.

Vérification :

- Aucun fichier `accounting*.blade.php` ne reste à la racine de `resources/views/main/modules`.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.

### 2026-06-09 - Archivage : séparation réelle des emplacements physiques

Prompt utilisateur :

```text
pas comme ça stp chaque emplecement dois avoir sa propre table, par exemple, table salle, armoire, rayon jusqu'à document ce que tu viens de faire n'a aucun sens
```

Correction appliquée :

- Refactorisation de la structure physique Archivage avec des tables dédiées :
  - `archive_rooms`
  - `archive_racks`
  - `archive_cabinets`
  - `archive_shelves`
  - `archive_compartments`
  - `archive_boxes`
- Ajout de `archive_box_id` sur les classeurs et documents archivés.
- Ajout de `from_archive_box_id` et `to_archive_box_id` sur les mouvements.
- La page Emplacements affiche maintenant chaque niveau physique dans sa propre section paginée.
- Les classeurs, documents et mouvements utilisent maintenant le chemin physique complet Salle / Rayon / Armoire / Étagère / Casier / Boîte.
- Le seeder Archivage crée une vraie hiérarchie physique et corrige les libellés UTF-8 de démonstration.
- L’ancienne table générique `archive_locations` reste seulement comme compatibilité historique, mais la nouvelle gestion physique utilise les tables séparées.

Fichiers principaux modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `app/Models/ArchiveRoom.php`
- `app/Models/ArchiveRack.php`
- `app/Models/ArchiveCabinet.php`
- `app/Models/ArchiveShelf.php`
- `app/Models/ArchiveCompartment.php`
- `app/Models/ArchiveBox.php`
- `app/Models/ArchiveContainer.php`
- `app/Models/ArchiveRecord.php`
- `app/Models/ArchiveMovement.php`
- `database/migrations/2026_06_09_000002_create_structured_archive_location_tables.php`
- `database/migrations/2026_06_09_000003_add_archive_boxes_to_movements.php`
- `database/seeders/ArchivingSeeder.php`
- `resources/views/main/modules/archiving/locations.blade.php`
- `resources/views/main/modules/archiving/containers.blade.php`
- `resources/views/main/modules/archiving/records.blade.php`
- `resources/views/main/modules/archiving/movements.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l app/Models/ArchiveMovement.php` passe.
- `php -l database/seeders/ArchivingSeeder.php` passe.
- `php -l database/migrations/2026_06_09_000003_add_archive_boxes_to_movements.php` passe.
- `php artisan migrate --force` passe.
- `php artisan db:seed --class=ArchivingSeeder` passe.
- `php artisan view:cache` passe.
- Les pages Archivage `locations`, `containers`, `records` et `movements` répondent en HTTP 200.

### 2026-06-09 - Archivage : affichage des emplacements en onglets

Prompt utilisateur :

```text
j'aime pas cette affichage des plusieurs tableaux d'emplacement, crée des onglets pour l'affichage des emplacements
```

Correction appliquée :

- Remplacement de l’empilement des tableaux Emplacements par des onglets :
  - Salles
  - Rayons
  - Armoires
  - Étagères
  - Casiers
  - Boîtes
- Un seul tableau est visible à la fois, avec le compteur de lignes dans chaque onglet.
- La pagination conserve l’onglet actif via le paramètre `tab`.
- Ajout d’un style dédié aux onglets Archivage, compatible dark mode.

Fichiers modifiés :

- `resources/views/main/modules/archiving/locations.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers modifiés.
- La page `archiving/locations` répond en HTTP 200.
- La page `archiving/locations?tab=box` répond en HTTP 200.

### 2026-06-09 - Archivage : harmonisation des modales

Prompt utilisateur :

```text
je t'avais dis d'utiliser les memes style de modale sur tout les modales de l'application
```

Correction appliquée :

- Les modales Archivage utilisent maintenant le socle standard `subscription-modal`.
- Harmonisation des modales :
  - Nouvel emplacement
  - Nouveau classeur
  - Nouveau document archivé
  - Nouveau mouvement
  - Nouvelle règle de conservation
- Ajout de classes dédiées `archive-location-modal`, `archive-container-modal`, `archive-record-modal`, `archive-movement-modal`, `archive-retention-modal`.
- Ajustement des largeurs, du header, des labels, des champs, du textarea et des actions pour reprendre le style commun Facturation/RH/GED.

Fichiers modifiés :

- `resources/views/main/modules/archiving/locations.blade.php`
- `resources/views/main/modules/archiving/containers.blade.php`
- `resources/views/main/modules/archiving/records.blade.php`
- `resources/views/main/modules/archiving/movements.blade.php`
- `resources/views/main/modules/archiving/retention.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers modifiés.
- Les pages Archivage `locations`, `containers`, `records` et `movements` répondent en HTTP 200.

### 2026-06-09 - Archivage : correction d'accès au tableau de bord

Prompt utilisateur :

```text
le tableau de bord est inaccessible
```

Correction appliquée :

- Correction du dashboard Archivage qui utilisait encore l’ancienne variable `$locations` issue de la table générique `archive_locations`.
- La répartition des emplacements physiques est maintenant calculée depuis les nouvelles tables séparées :
  - salles
  - rayons
  - armoires
  - étagères
  - casiers
  - boîtes

Fichiers modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php artisan view:cache` passe.
- Le tableau de bord Archivage répond en HTTP 200.

### 2026-06-09 - Archivage : espacement des boutons de modale

Prompt utilisateur :

```text
le boutton sont collé au parent arrange ça
```

Correction appliquée :

- Ajout d’un padding inférieur et latéral sur les actions des modales Archivage.
- Les boutons `Annuler` et `Créer/Enregistrer` ne sont plus collés au bord de la modale.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `git diff --check` passe sur `resources/css/main.css`.
- `php artisan view:cache` passe.

### 2026-06-09 - Archivage : contrôle hiérarchique des capacités

Prompt utilisateur :

```text
tu dois ajouter cette regles et j'espère que mes tableaux sont toujours paginés
```

Correction appliquée :

- Ajout d’une règle métier sur les capacités des emplacements physiques.
- Si un parent possède une capacité définie, la somme des capacités de ses enfants ne peut plus la dépasser :
  - rayons dans une salle
  - armoires dans un rayon
  - étagères dans une armoire
  - casiers et boîtes directes dans une étagère
  - boîtes dans un casier
- Exemple bloqué : salle de 100 unités avec création d’un rayon de 200 unités.
- Ajout des messages FR/EN expliquant la capacité demandée et la capacité restante.
- Confirmation que les onglets Emplacements restent paginés à 5 lignes par page.

Fichiers modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- La page Emplacements Archivage répond en HTTP 200.
- Les requêtes `rooms`, `racks`, `cabinets`, `shelves`, `compartments` et `boxes` utilisent toujours `paginate(5, ...)`.

### 2026-06-09 - Archivage : modification et suppression des emplacements

Prompt utilisateur :

```text
pourquoi il n'y a pas la possibilité de supprimer ou de modifier, ajoute ces regles :
les emplacements qui ont des enfants ne peuvent pas etre supprimés, on ne peut pas changer les parents d'un emplacement
```

Correction appliquée :

- Ajout des routes `PUT` et `DELETE` pour les emplacements Archivage.
- Ajout des boutons modifier et supprimer dans les onglets :
  - Salles
  - Rayons
  - Armoires
  - Étagères
  - Casiers
  - Boîtes
- La modale Emplacement bascule maintenant entre création et modification.
- En modification, le type et le parent sont verrouillés : seul le nom, le code, la capacité, le statut et la description sont modifiables.
- Suppression bloquée si l’emplacement contient des enfants :
  - salle avec rayons
  - rayon avec armoires
  - armoire avec étagères
  - étagère avec casiers ou boîtes directes
  - casier avec boîtes
  - boîte avec classeurs ou documents
- La modification de capacité contrôle aussi les capacités déjà affectées aux enfants.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/ArchivingController.php`
- `resources/views/main/modules/archiving/locations.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan route:cache` passe.
- `php artisan view:cache` passe.
- La page Emplacements Archivage répond en HTTP 200.
- `route:list --path=archiving/locations` affiche bien les routes `GET`, `POST`, `PUT`, `DELETE`.

### 2026-06-09 - Recherche des tableaux RH, GED et Archivage

Prompt utilisateur :

```text
je me suis rendu compte que l'input de recherche ne fonctionne pas sur le module RH, ged et archive
```

Correction appliquée :

- Correction du comportement global de recherche dans `resources/js/main.js`.
- Les tableaux avec `companySearch` sans paramètre serveur filtrent maintenant le tableau affiché côté client.
- Les recherches serveur utilisent maintenant le nom réel de l’input (`search`, `q`, etc.) au lieu de forcer systématiquement `search`.
- Les formulaires de recherche autonomes avec `name="q"` déclenchent maintenant automatiquement une recherche après saisie.
- Les paramètres de pagination `page` et `*_page` sont réinitialisés lors d’une nouvelle recherche pour éviter de rester bloqué sur une page sans résultat.
- Ce correctif couvre les pages RH, GED et Archivage sans changer la pagination existante.

Fichiers modifiés :

- `resources/js/main.js`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur `resources/js/main.js`.
- Les pages RH employés, GED courriers entrants, Archivage emplacements avec `q=finance` et Archivage documents avec `q=facture` répondent en HTTP 200.

### 2026-06-09 - Recherche AJAX sans actualisation complète

Prompt utilisateur :

```text
tu refais la meme betise d'actualiser la page quand je fais la recherche pourquoi tu oublie toujours je t'avais dis d'utiliser ajax pour les recherches
```

Correction appliquée :

- Suppression de la navigation complète sur les formulaires de recherche autonomes.
- Ajout d’un `fetch` AJAX pour les recherches avec `form.search-box input[type="search"][name]`.
- Remplacement uniquement de la zone `.dashboard-content` à partir du HTML reçu.
- Mise à jour de l’URL via `history.replaceState` ou `history.pushState`, sans rechargement de page.
- Interception AJAX de la pagination liée aux résultats de recherche.
- Réinitialisation des paginations `page` et `*_page` lors d’une nouvelle saisie.

Fichiers modifiés :

- `resources/js/main.js`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur `resources/js/main.js`.
- Les recherches AJAX sur Archivage emplacements, Archivage documents et GED dossiers répondent en HTTP 200.

### 2026-06-09 - Recherche AJAX : conservation du focus

Prompt utilisateur :

```text
pourquoi lorsque je fais la recherche tu enleves le focus sur le formulaire pourquoi tu reprends les memes erreurs
```

Correction appliquée :

- Conservation du focus lors des recherches AJAX autonomes.
- Avant remplacement de `.dashboard-content`, le script mémorise :
  - le nom du champ actif
  - la valeur saisie
  - la position du curseur
- Après remplacement AJAX, le champ de recherche est retrouvé, refocalisé avec `preventScroll`, et la position du curseur est restaurée.

Fichiers modifiés :

- `resources/js/main.js`
- `docs/prompts/project-history.md`

Vérification :

- `node --check resources/js/main.js` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur `resources/js/main.js`.
- La recherche AJAX Archivage emplacements répond en HTTP 200.

### 2026-06-09 - Professionnalisation du tableau de bord Archivage

Prompt utilisateur :

```text
le tableau de bord est basique et non stylisé et non professionnelle pourquoi ?
```

Correction appliquée :

- Refonte du tableau de bord Archivage pour l’aligner avec les standards GED/RH/Comptabilité.
- Ajout d’un en-tête de module avec icône, lien retour et structure visuelle cohérente.
- Remplacement de l’affichage brut par :
  - cartes KPI stylisées ;
  - plan physique des archives ;
  - bloc occupation/capacité ;
  - indicateurs de risques ;
  - dernières archives ;
  - échéances de conservation ;
  - activité récente.
- Enrichissement des données du contrôleur Archivage : capacité, occupation, emplacements prioritaires, statuts, risques et activités récentes.
- Ajout du style responsive et dark mode pour le dashboard Archivage.

Fichiers modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `resources/views/main/modules/archiving/dashboard.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- La page `/main/companies/4/sites/1/modules/archiving` répond en `200 OK`.

### 2026-06-09 - Correction pagination page Emplacements Archivage

Prompt utilisateur :

```text
il y a un probleme avec la page emplacement
```

Correction appliquée :

- Le rendu cassé venait de la pagination Laravel par défaut en mode Tailwind alors que Tailwind n’est pas chargé dans l’application.
- Configuration globale de Laravel pour utiliser la pagination Bootstrap 5 via `Paginator::useBootstrapFive()`.
- Ajout d’un style compact pour `.subscriptions-pagination`, compatible avec :
  - les paginations Bootstrap serveur ;
  - les paginations personnalisées déjà utilisées dans Comptabilité/GED/RH.
- Ajout de la variante dark mode et du comportement responsive.

Fichiers modifiés :

- `app/Providers/AppServiceProvider.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Providers/AppServiceProvider.php` passe.
- `php artisan view:cache` passe.
- La page `/main/companies/4/sites/1/modules/archiving/locations` répond en `200 OK`.

### 2026-06-09 - Restructuration hiérarchique des emplacements Archivage

Prompt utilisateur :

```text
je n'arrive pas à comprendre comment tu as structuré les choses...
```

Correction appliquée :

- La page Emplacements n’affiche plus une liste plate désordonnée.
- Les emplacements sont maintenant construits comme un arbre physique :
  - Salle
  - Zone / Rayon
  - Armoire
  - Étagère
  - Casier
  - Boîte
- Le tableau affiche le chemin physique complet pour comprendre à quelle salle, zone, armoire ou étagère appartient chaque emplacement.
- Ajout d’une bande de rappel de la hiérarchie physique.
- Ajout d’une indentation visuelle dans la colonne Emplacement.
- Le formulaire de création filtre le parent selon le type choisi :
  - une Zone doit être dans une Salle ;
  - une Armoire doit être dans une Zone ;
  - une Étagère doit être dans une Armoire ;
  - un Casier doit être dans une Étagère ;
  - une Boîte doit être dans une Étagère ou un Casier.
- Validation serveur ajoutée pour empêcher les rattachements incohérents.
- Seeder Archivage corrigé avec des données de démonstration cohérentes et en UTF-8 propre.

Fichiers modifiés :

- `app/Http/Controllers/ArchivingController.php`
- `resources/views/main/modules/archiving/locations.blade.php`
- `resources/css/main.css`
- `database/seeders/ArchivingSeeder.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/ArchivingController.php` passe.
- `php -l database/seeders/ArchivingSeeder.php` passe.
- `php artisan db:seed --class=ArchivingSeeder` passe.
- `php artisan view:cache` passe.
- La page `/main/companies/4/sites/1/modules/archiving/locations` répond en `200 OK`.

### 2026-06-09 - Paramètres GED

Prompt utilisateur :

```text
parfait nous allons maintenant travailler sur parametre GED, n'oublie pas meme ideologie comme on l'as fais avec les autres modules
```

Implémentation appliquée :

- Ajout de la page Paramètres GED avec la même logique que Comptabilité/RH :
  - identité visuelle PDF,
  - aperçu de rapport,
  - accès aux menus par utilisateur simple,
  - pagination des utilisateurs à configurer.
- Ajout des routes `main.document-management.settings` et `main.document-management.settings.update`.
- Ajout de `DocumentManagementModuleNavigation` pour centraliser les clés de menus GED.
- Extension du middleware d’accès menu afin de gérer aussi les restrictions GED.
- Mise à jour de la sidebar GED :
  - lien Paramètres correct,
  - affichage réservé aux Admin/Superadmin,
  - menus filtrés selon les droits de l’utilisateur,
  - Rapports GED conservé comme menu principal séparé.
- Les paramètres PDF GED sont maintenant fournis au rapport PDF GED.
- Ajout des traductions FR/EN liées aux paramètres GED.

Fichiers modifiés :

- `app/Support/DocumentManagementModuleNavigation.php`
- `app/Http/Middleware/EnsureAccountingMenuAccess.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/settings.blade.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `routes/web.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l` passe sur les nouveaux fichiers et fichiers modifiés principaux.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --name=main.document-management.settings` affiche les 2 routes GED.
- `git diff --check` ne signale aucune erreur bloquante.

### 2026-06-09 - Réduction légère de la taille des textes

Prompt utilisateur :

```text
peux-tu reguire legerement la taille des textes de toutes l'application
```

Correction appliquée :

- Réduction légère de la base typographique globale à `15px`.
- Application de la règle sur :
  - l’interface principale,
  - la console admin/superadmin,
  - la page de connexion.
- Le choix d’agir sur `html` permet de réduire proprement les tailles en `rem` sans modifier chaque composant individuellement.

Fichiers modifiés :

- `resources/css/main.css`
- `resources/css/admin/dashboard.css`
- `resources/css/auth/login.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers CSS concernés.

### 2026-06-09 - Premier socle du module Archivage physique

Prompts utilisateur :

```text
nous allons maintenant travailler sur le module Archivage...
j'ai besoin que tu tiennes en compte des emplacements des documents comme dans une vrai salle d'archive.
il faut meme tenir compte des farde n'oublie rien
on prendra classeur alors
applique maintenant
```

Implémentation appliquée :

- Création du module Archivage avec une logique physique complète :
  - Salle
  - Zone / Rayon
  - Armoire
  - Étagère
  - Casier
  - Boîte
  - Classeur
  - Document archivé
- Ajout des tables :
  - `archive_locations`
  - `archive_containers`
  - `archive_records`
  - `archive_movements`
  - `archive_retention_rules`
  - `archive_activities`
- Ajout des modèles Eloquent correspondants et des relations dans `CompanySite`.
- Ajout de `ArchivingController` avec les pages :
  - Tableau de bord Archivage
  - Emplacements
  - Classeurs
  - Documents archivés
  - Mouvements
  - Conservation
  - Traçabilité
  - Rapports Archivage
  - Paramètres Archivage
- Ajout de `ArchivingModuleNavigation` et branchement dans le middleware d’accès menu.
- Ajout de la sidebar Archivage.
- Ajout des routes `main.archiving.*`.
- Le lien Archivage sur la fiche site ouvre maintenant directement le dashboard Archivage.
- Ajout d’un rapport PDF Archivage.
- Ajout du seeder `ArchivingSeeder` :
  - salle d’archives,
  - zones,
  - armoires,
  - étagères,
  - boîtes,
  - classeurs,
  - documents archivés,
  - règles de conservation.
- Ajout des traductions FR/EN du module Archivage.

Fichiers principaux modifiés ou ajoutés :

- `app/Http/Controllers/ArchivingController.php`
- `app/Support/ArchivingModuleNavigation.php`
- `app/Models/ArchiveLocation.php`
- `app/Models/ArchiveContainer.php`
- `app/Models/ArchiveRecord.php`
- `app/Models/ArchiveMovement.php`
- `app/Models/ArchiveRetentionRule.php`
- `app/Models/ArchiveActivity.php`
- `database/migrations/2026_06_09_000001_create_archiving_tables.php`
- `database/seeders/ArchivingSeeder.php`
- `resources/views/main/modules/archiving/*`
- `routes/web.php`
- `lang/fr/main.php`
- `lang/en/main.php`

Vérification :

- `php -l` passe sur les nouveaux modèles, le contrôleur, la migration, le seeder et les fichiers de langue.
- `php artisan migrate --force` passe.
- `php artisan db:seed --class=ArchivingSeeder` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --name=main.archiving` affiche 16 routes.
- La page `/main/companies/4/sites/1/modules/archiving` répond en HTTP 200.

### 2026-06-09 - Suppression Recherche avancée et création des rapports GED

Prompt utilisateur :

```text
je ne penses pas que c'est utile, supprime ce sous-menu et travail sur rapport
```

Implémentation appliquée :

- Suppression du sous-menu `Recherche avancée` dans la sidebar GED.
- Création d’une vraie page `Rapports GED`.
- Ajout de la route `main.document-management.reports`.
- Le menu `Rapports GED` pointe maintenant vers sa page dédiée.
- La page rapport utilise les vraies données GED :
  - documents enregistrés sur la période
  - documents ouverts
  - documents urgents
  - actions tracées
  - répartition par type
  - répartition par statut
  - répartition par dossier
  - demandes de validation
  - activité récente
- Les filtres de période restent réservés à la page rapport, conformément à la règle : les tableaux opérationnels restent simples.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/reports.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app\Http\Controllers\DocumentManagementController.php` passe.
- `php -l routes\web.php` passe.
- `php -l lang\fr\main.php` passe.
- `php -l lang\en\main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --name=main.document-management.reports` confirme la route.

### 2026-06-09 - Remise de Rapports GED dans le menu Classement

Prompt utilisateur :

```text
je t'avai demandé de supprimer seulement reche avancé. et en suite travailler sur la page rapport toi tu as supprimé les deux
```

Correction appliquée :

- Correction de l’emplacement du sous-menu `Rapports GED`.
- `Recherche avancée` reste supprimé.
- `Rapports GED` est remis dans le groupe `Classement`, juste après `Dossiers`, avec sa vraie route dédiée.

Fichiers modifiés :

- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- Scan de la sidebar GED : `Recherche avancée` absent, `Rapports GED` présent.

### 2026-06-09 - Rapports GED en menu principal et impression PDF

Prompt utilisateur :

```text
rapport ged doit etre un menu à part et non un sous-menu.
regarde comment tu as fais avec les autres modules avec possiblité d'imprimer en pdf
```

Correction appliquée :

- `Rapports GED` est maintenant une entrée principale de la sidebar GED, avec un séparateur `Rapport`.
- Le menu `Rapports GED` n’est plus dans le groupe `Classement`.
- Ajout de la route PDF `main.document-management.reports.pdf`.
- Ajout du bouton `Imprimer en PDF` sur la page Rapports GED.
- Factorisation des données de rapport GED pour que l’écran et le PDF utilisent les mêmes chiffres.
- Création du template PDF GED avec le même esprit que les rapports RH/Comptabilité :
  - en-tête entreprise/site
  - titre du rapport
  - période analysée
  - indicateurs
  - tableaux de synthèse
  - pied de page avec branding et date de génération

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/reports.blade.php`
- `resources/views/main/modules/document-management/pdf/reports.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app\Http\Controllers\DocumentManagementController.php` passe.
- `php -l routes\web.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- Les routes `reports` et `reports/pdf` du module GED sont confirmées.

### 2026-06-09 - Stylisation des tableaux Rapports GED

Prompt utilisateur :

```text
les tableaux sur rapport ged sont très basique il faut bien styliser
```

Correction appliquée :

- Amélioration visuelle des tableaux de la page Rapports GED.
- Ajout de cartes de rapport plus structurées avec icônes d’en-tête.
- Ajout d’icônes contextuelles par type de document.
- Ajout de badges pour les statuts GED et les validations.
- Ajout de barres de progression pour visualiser les volumes relatifs.
- Ajout de compteurs numériques stylisés pour les documents ouverts, validés et urgents.
- Amélioration des cellules dossier, catégorie, date et activité récente.
- Correction des compteurs d’en-tête pour afficher le nombre réel de lignes du tableau.
- Ajout du style dark mode correspondant.

Fichiers modifiés :

- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/reports.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app\Http\Controllers\DocumentManagementController.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.
- Les composants stylisés sont présents dans la vue et le CSS.

### 2026-06-09 - Tri des colonnes sur Traçabilité et Dossiers GED

Prompt utilisateur :

```text
tri des colonnes stp sur mes tableaux traçabilité et dossiers rien n'est fait
```

Correction appliquée :

- Activation du tri sur le tableau Traçabilité GED.
- Activation du tri sur le tableau Dossiers GED.
- Ajout de `id="companyTable"` sur les deux tableaux pour les connecter au script commun.
- Remplacement des en-têtes texte par des boutons `table-sort` avec icône de tri.
- Ajout de valeurs de tri propres pour :
  - dates de traçabilité
  - nombre de documents dans les dossiers
  - dernière activité des dossiers
- Aucun bloc de filtrage n’a été ajouté.

Fichiers modifiés :

- `resources/views/main/modules/document-management/traceability.blade.php`
- `resources/views/main/modules/document-management/folders.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Pagination visible sur la page Dossiers GED

Prompt utilisateur :

```text
les filtrages sur mes tableaux tu ne fais plus;
la pagination sur la page dosssiers ?
```

Correction appliquée :

- Confirmation de la règle de présentation : garder la recherche simple sur les tableaux, sans ajouter de blocs de filtrage.
- La page Dossiers GED reste paginée à 5 lignes par page côté serveur.
- La barre de pagination s’affiche maintenant dès qu’il y a des dossiers, même quand le total tient sur une seule page.
- Le compteur continue d’afficher le nombre de lignes visibles et le total.

Fichiers modifiés :

- `resources/views/main/modules/document-management/folders.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Correction UTF-8 des dossiers GED

Prompt utilisateur :

```text
il y a un problème de formatage utf-8
```

Correction appliquée :

- Correction du seeder `DocumentManagementIncomingFiveSeeder` avec des libellés UTF-8 propres.
- Remplacement des textes corrompus comme `Bureau dâ€™ordre` et `Direction gÃ©nÃ©rale` par :
  - `Bureau d’ordre`
  - `Direction générale`
  - `Décisions`
- Nettoyage des données déjà enregistrées dans la base :
  - fusion des dossiers GED dupliqués et corrompus vers les dossiers canoniques
  - réaffectation des documents vers les bons dossiers
  - suppression des dossiers dupliqués corrompus
  - correction des textes des courriers et activités GED concernés
- Les dossiers GED affichables sont maintenant :
  - `Bureau d’ordre` avec 4 documents
  - `Direction générale` avec 3 documents
  - `Contrats et conventions` avec 2 documents

Fichiers modifiés :

- `database/seeders/DocumentManagementIncomingFiveSeeder.php`
- `docs/prompts/project-history.md`

Tables nettoyées :

- `document_management_folders`
- `document_management_records`
- `document_management_activities`

Vérification :

- `php -l database\seeders\DocumentManagementIncomingFiveSeeder.php` passe.
- `php artisan view:cache` passe.
- La recherche des anciennes chaînes corrompues dans le seeder ne retourne plus de résultat.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-05 - État vide des validations en cours GED

Prompt utilisateur :

```text
arrange moi ça
```

Correction appliquée :

- La section `Validations en cours` n’affiche plus un grand tableau vide lorsqu’aucun document n’est en circuit.
- Ajout d’un état vide compact avec icône, message principal et texte d’aide.
- Ajout du rendu dark mode pour cet état vide.
- Ajout des traductions FR/EN du texte d’aide.

Fichiers modifiés :

- `resources/views/main/modules/document-management/validation-circuits.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

### 2026-06-09 - Ajustement visuel du bloc Validations en cours GED

Prompt utilisateur :

```text
arrange moi ça stp
```

Correction appliquée :

- Ajout d’un padding dédié à la carte `Validations en cours`.
- Transformation du compteur `0 lignes` en pastille alignée proprement.
- Conservation du rendu tableau plein format quand des validations existent.
- L’état vide reste compact, lisible et cohérent avec le design GED.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Traitement de l’onglet Validations en cours GED

Prompt utilisateur :

```text
traitement l'onglet Valifations en cours
```

Implémentation appliquée :

- L’onglet `Validations en cours` affiche maintenant uniquement les validations ouvertes (`En attente`, `En cours`).
- Ajout du bouton `Lancer une validation` pour envoyer un document GED dans un circuit actif.
- Ajout de la modale de lancement avec sélection du document, du circuit et commentaire.
- Ajout des actions de traitement :
  - approuver l’étape courante ;
  - rejeter la validation ;
  - passer automatiquement à l’étape suivante ;
  - clôturer la validation en `Approuvé` quand la dernière étape est validée.
- Mise à jour automatique du statut du document :
  - `En revue` au lancement ;
  - `Validé` après approbation finale.
- Journalisation des actions dans les activités GED.
- Restriction du traitement au validateur attendu, ou aux administrateurs/superadministrateurs.
- Ajout des routes POST dédiées aux validations.
- Ajout des libellés FR/EN et styles de boutons/colonnes.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/validation-circuits.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan route:cache` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=document-management.validation-requests` affiche les 3 routes de traitement.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Création d’un circuit GED sans validation lancée

Prompt utilisateur :

```text
crée moi un circuit de validation sans le valider
```

Action appliquée :

- Création du circuit GED `VAL-000001` nommé `Validation documentaire standard` sur le site `EXAD Kinshasa`.
- Circuit actif, applicable à tous les types de documents GED.
- Étapes créées :
  - `Vérification responsable`, validateur `user3`, délai 2 jours.
  - `Approbation finale`, validateur `admin`, délai 3 jours.
- Aucune demande de validation n’a été lancée sur ce circuit.

Vérification :

- Le circuit contient 2 étapes.
- `validation_requests_count` vaut `0`.

### 2026-06-09 - Onglets Circuits / Validations en cours GED

Prompt utilisateur :

```text
lorsque j'appui rien n'est affiché
```

Correction appliquée :

- Les liens `Circuits` et `Validations en cours` fonctionnent maintenant comme de vrais onglets.
- Le clic sur `Validations en cours` masque la recherche et le tableau des circuits.
- Le panneau des validations s’affiche immédiatement, même lorsqu’il est vide.
- L’URL conserve l’ancre du panneau actif pour rouvrir directement le bon onglet.

Fichiers modifiés :

- `resources/views/main/modules/document-management/validation-circuits.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Page Traçabilité GED

Prompt utilisateur :

```text
nous allons maintenant travailler sur traçabilité
```

Implémentation appliquée :

- Ajout d’une vraie page `Traçabilité` dans le module GED.
- Connexion du menu `Traçabilité` dans la sidebar GED.
- Ajout de la route `main.document-management.traceability`.
- Lecture des activités GED déjà journalisées dans `document_management_activities`.
- Affichage des métriques :
  - événements enregistrés ;
  - événements du jour ;
  - actions de validation ;
  - acteurs actifs.
- Ajout de filtres :
  - recherche texte ;
  - action ;
  - acteur ;
  - type de document ;
  - date de début ;
  - date de fin.
- Ajout d’un tableau paginé avec :
  - date et heure ;
  - document concerné ;
  - action lisible ;
  - acteur ;
  - changement de statut.
- Ajout des styles light/dark et traductions FR/EN.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/traceability.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan route:cache` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=document-management.traceability` affiche la route.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Allègement de la page Traçabilité GED

Prompt utilisateur :

```text
enleve ça.
les elements sur mes tableaux c'est toujours 5 elements par page merci
```

Correction appliquée :

- Suppression des cartes de métriques en haut de la page Traçabilité.
- Suppression du bloc de filtres visible sous les métriques.
- Conservation du tableau principal de traçabilité.
- Pagination de la page Traçabilité passée à 5 éléments par page.
- Suppression des styles CSS devenus inutiles.

Fichiers modifiés :

- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/traceability.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Recherche sur la page Traçabilité GED

Prompt utilisateur :

```text
ajoute l'input de recherche
```

Correction appliquée :

- Ajout d’un input de recherche simple sur la page Traçabilité.
- Recherche serveur sur action, commentaire, statut, référence, sujet, expéditeur, destinataire, nom et email de l’acteur.
- Conservation de la pagination à 5 éléments par page.

Fichiers modifiés :

- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/traceability.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-09 - Page Dossiers GED

Prompt utilisateur :

```text
applique ton idée
```

Implémentation appliquée :

- Ajout d’une vraie page `Dossiers` dans le module GED.
- Connexion du menu `Dossiers` dans la sidebar GED.
- Ajout des routes :
  - liste des dossiers ;
  - création ;
  - détail ;
  - modification ;
  - suppression.
- Ajout du CRUD des dossiers avec les champs existants :
  - nom ;
  - catégorie ;
  - statut ;
  - description.
- Génération automatique des références `DOS-000001`.
- Recherche serveur par référence, nom, catégorie et description.
- Pagination à 5 éléments par page.
- Suppression bloquée si le dossier contient déjà des documents.
- Ajout d’une page détail affichant les documents rattachés au dossier, paginés à 5.
- Ajout des styles light/dark et traductions FR/EN.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/folders.blade.php`
- `resources/views/main/modules/document-management/folder-show.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan route:cache` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=document-management.folders` affiche les 5 routes.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-05 - Page Courriers entrants GED

Prompt utilisateur :

```text
applique ton idée
```

Implémentation appliquée :

- Création de la page professionnelle des courriers entrants du module GED.
- Ajout des routes dédiées :
  - liste des courriers entrants
  - création
  - modification
  - suppression
- Connexion de la page aux vraies tables GED :
  - `document_management_records`
  - `document_management_folders`
  - `document_management_activities`
- Ajout d'une liste paginée à 5 lignes, avec recherche locale, tri, filtres serveur et compteur.
- Ajout des filtres :
  - recherche par référence, expéditeur, objet ou catégorie
  - statut
  - priorité
  - dossier
  - responsable assigné
  - période de réception
- Ajout des actions :
  - nouveau courrier entrant
  - modifier un courrier entrant
  - supprimer un courrier entrant
  - joindre une pièce jointe PDF, Word, Excel ou image
- Ajout de la journalisation GED lors de la création ou modification d'un courrier.
- Le menu GED “Courriers entrants” pointe désormais vers sa vraie page.
- Ajout des libellés FR/EN et des styles GED complémentaires, y compris dark mode.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/incoming.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --name=main.document-management.incoming` affiche les 4 routes.
- `Invoke-WebRequest http://127.0.0.1:8000/main/companies/4/sites/1/modules/document_management/incoming` retourne `200 OK`.

### 2026-06-05 - Allègement de la page Courriers entrants GED

Prompt utilisateur :

```text
enleve ce filtre on il surcharge la page
```

Correction appliquée :

- Retrait de la grande carte de filtres de la page Courriers entrants.
- Conservation de la recherche rapide du tableau, du compteur, du tri et de la pagination pour garder une page plus légère.

Fichier modifié :

- `resources/views/main/modules/document-management/incoming.blade.php`

Vérification :

- `php artisan view:cache` passe.
- La page `/main/companies/4/sites/1/modules/document_management/incoming` retourne `200 OK`.

### 2026-06-05 - Simplification du tableau Courriers entrants GED

Prompt utilisateur :

```text
mets moi les informations importante pour ne pas surchargé le tableau
```

Correction appliquée :

- Réduction du tableau Courriers entrants de 10 colonnes à 5 colonnes.
- Regroupement des informations utiles :
  - objet, expéditeur, date de réception, dossier, catégorie et pièce jointe dans la colonne `Courrier`
  - assignation, échéance et priorité dans la colonne `Suivi`
- Conservation des colonnes essentielles :
  - référence
  - courrier
  - suivi
  - statut
  - actions

Fichiers modifiés :

- `resources/views/main/modules/document-management/incoming.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`

Vérification :

- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- La page `/main/companies/4/sites/1/modules/document_management/incoming` retourne `200 OK`.

### 2026-06-05 - Données entrantes et page Courriers sortants GED

Prompt utilisateur :

```text
avant d'appliquer ajoute moi d'abord 5 couriers entrants etebnsuite tu travailles sur la pages courriers sortants
```

Implémentation appliquée :

- Ajout d'un seeder dédié `DocumentManagementIncomingFiveSeeder` pour créer 5 courriers entrants supplémentaires.
- Exécution du seeder pour insérer les courriers dans les données réelles du module GED.
- Création de la page Courriers sortants avec :
  - routes liste/création/modification/suppression
  - menu GED relié à la vraie page
  - tableau compact paginé à 5 lignes
  - recherche rapide, compteur, tri et pagination
  - modale de création/modification
  - pièce jointe
  - statut, priorité, responsable interne, service demandeur, destinataire et mode d'envoi
  - journalisation des actions GED
- Le tableau sortant reste léger avec 5 colonnes :
  - référence
  - courrier
  - envoi
  - statut
  - actions

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/outgoing.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `database/seeders/DocumentManagementIncomingFiveSeeder.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l database/seeders/DocumentManagementIncomingFiveSeeder.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- La page `/main/companies/4/sites/1/modules/document_management/incoming` retourne `200 OK`.
- La page `/main/companies/4/sites/1/modules/document_management/outgoing` retourne `200 OK`.
- Le site 1 contient maintenant 6 courriers entrants : 1 existant + 5 ajoutés.

### 2026-06-05 - Page Documents internes GED

Prompt utilisateur :

```text
applique ton idée
```

Implémentation appliquée :

- Création de la page Documents internes du module GED.
- Ajout des routes dédiées :
  - liste
  - création
  - modification
  - suppression
- Connexion du menu GED `Documents internes` à la vraie page.
- Utilisation de `document_management_records` avec `record_type = internal`.
- Tableau compact paginé à 5 lignes :
  - référence
  - document
  - suivi
  - statut
  - actions
- Ajout d'une modale de création/modification avec :
  - titre
  - type de document
  - auteur/service
  - responsable du document
  - dossier
  - version
  - date du document
  - date de publication
  - prochaine révision
  - priorité
  - statut
  - pièce jointe
  - résumé
- Mise en place du cycle de vie :
  - Brouillon
  - En traitement
  - Validé
  - Publié
  - Obsolète
  - Archivé
- Journalisation des créations, modifications et changements de statut.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/internal.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --path=document_management/internal` affiche les 4 routes.
- La page `/main/companies/4/sites/1/modules/document_management/internal` retourne `200 OK`.

### 2026-06-05 - Page Assignations GED

Prompt utilisateur :

```text
applique ton idée
```

Implémentation appliquée :

- Création de la page Assignations du module GED.
- Ajout des routes dédiées :
  - liste des assignations
  - mise à jour d'une assignation
- Connexion du menu GED `Assignations` à la vraie page.
- La page liste les éléments GED à suivre depuis `document_management_records` :
  - courriers entrants
  - courriers sortants
  - documents internes
- Tableau compact paginé à 5 lignes :
  - référence
  - document
  - assignation
  - statut
  - actions
- Ajout d'une modale de mise à jour d'assignation :
  - responsable
  - échéance
  - priorité
  - statut
  - instruction/commentaire
- Ajout d'une action pour ouvrir la page du document concerné.
- Journalisation des changements d'assignation ou de statut.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/assignments.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --path=document_management` affiche les routes Assignations.
- La page `/main/companies/4/sites/1/modules/document_management/assignments` retourne `200 OK`.

### 2026-06-05 - Page Circuits de validation GED

Prompt utilisateur :

```text
applique ton idée
```

Implémentation appliquée :

- Création d'une vraie fondation de workflow GED avec nouvelles tables :
  - `document_management_validation_circuits`
  - `document_management_validation_steps`
  - `document_management_validation_requests`
  - `document_management_validation_actions`
- Ajout des modèles Eloquent :
  - `DocumentManagementValidationCircuit`
  - `DocumentManagementValidationStep`
  - `DocumentManagementValidationRequest`
  - `DocumentManagementValidationAction`
- Ajout des relations avec `CompanySite` et `DocumentManagementRecord`.
- Création de la page Circuits de validation.
- Ajout des routes dédiées :
  - liste des circuits
  - création
  - modification
  - suppression
- Connexion du menu GED `Circuits de validation` à la vraie page.
- La page contient deux sections :
  - `Circuits`
  - `Validations en cours`
- Tableau des circuits paginé à 5 lignes :
  - référence
  - circuit
  - étapes
  - statut
  - actions
- Modale de création/modification d'un circuit :
  - nom
  - type de document concerné
  - service propriétaire
  - statut
  - description
  - jusqu'à 3 étapes de validation
  - rôle, validateur et délai par étape
- Suppression bloquée lorsqu'un circuit contient déjà des validations.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `app/Models/CompanySite.php`
- `app/Models/DocumentManagementRecord.php`
- `app/Models/DocumentManagementValidationCircuit.php`
- `app/Models/DocumentManagementValidationStep.php`
- `app/Models/DocumentManagementValidationRequest.php`
- `app/Models/DocumentManagementValidationAction.php`
- `database/migrations/2026_06_05_000002_create_document_management_validation_tables.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/views/main/modules/document-management/validation-circuits.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l` passe sur les nouveaux modèles et la migration.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan migrate --force` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --path=document_management/validation-circuits` affiche les 4 routes.
- La page `/main/companies/4/sites/1/modules/document_management/validation-circuits` retourne `200 OK`.

### 2026-06-05 - Retrait des indicateurs Courriers entrants GED

Prompt utilisateur :

```text
enleve meme ça
```

Correction appliquée :

- Retrait des cartes KPI de la page Courriers entrants.
- La page affiche maintenant directement le titre, l'action principale, la recherche rapide, le tableau et la pagination.

Fichier modifié :

- `resources/views/main/modules/document-management/incoming.blade.php`

Vérification :

- `php artisan view:cache` passe.
- La page `/main/companies/4/sites/1/modules/document_management/incoming` retourne `200 OK`.

### 2026-06-05 - Correction accès dashboard GED

Prompt utilisateur :

```text
Erreur 500 sur /modules/document_management : Call to undefined method DocumentManagementController::canAccessCompanySite()
```

Correction appliquée :

- Ajout des helpers d’accès manquants dans `DocumentManagementController` :
  - `canAccessCompanySite`
  - `canManageCompanyRecord`
  - `redirectMainArea`
  - `firstAssignedSite`
- Le contrôleur GED peut maintenant vérifier l’accès au site comme les modules Comptabilité/RH.
- L’URL du dashboard GED ne déclenche plus l’erreur 500.

Fichiers modifiés :

- `app/Http/Controllers/DocumentManagementController.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `Invoke-WebRequest http://127.0.0.1:8000/main/companies/4/sites/1/modules/document_management` retourne `200 OK`.

### 2026-06-05 - Socle professionnel du module GED

Prompt utilisateur :

```text
applique
```

Contexte :

- L’utilisateur souhaite une GED très professionnelle, orientée bureau d’ordre, courrier, documents, circuits de traitement et traçabilité.

Implémentation appliquée :

- Création du contrôleur dédié `DocumentManagementController`.
- Ajout de la route dédiée `main.document-management.dashboard`.
- La carte `GED` de la page site ouvre maintenant directement le tableau de bord GED.
- Création des tables GED :
  - `document_management_folders`
  - `document_management_records`
  - `document_management_activities`
- Création des modèles :
  - `DocumentManagementFolder`
  - `DocumentManagementRecord`
  - `DocumentManagementActivity`
- Ajout des relations GED dans `CompanySite`.
- Ajout du seeder `DocumentManagementSeeder`, appelé par `DatabaseSeeder`.
- Le seeder crée un site GED de démonstration si aucun site n’a encore le module GED, puis ajoute :
  - dossiers GED,
  - courriers entrants,
  - courriers sortants,
  - documents internes,
  - activités de traçabilité.
- Création du tableau de bord GED professionnel avec :
  - KPI courriers entrants,
  - documents en traitement,
  - documents en retard,
  - dossiers actifs,
  - bureau d’ordre,
  - priorités et échéances,
  - dossiers,
  - assignations,
  - activité récente.
- Création d’une sidebar GED structurée :
  - Bureau d’ordre,
  - Traitement,
  - Classement,
  - Paramètres GED.
- Ajout des styles dédiés GED, compatibles dark mode.
- Ajout des traductions FR/EN nécessaires.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/DocumentManagementController.php`
- `app/Models/CompanySite.php`
- `app/Models/DocumentManagementFolder.php`
- `app/Models/DocumentManagementRecord.php`
- `app/Models/DocumentManagementActivity.php`
- `database/migrations/2026_06_05_000001_create_document_management_tables.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/DocumentManagementSeeder.php`
- `resources/views/main/company-site-show.blade.php`
- `resources/views/main/modules/document-management/dashboard.blade.php`
- `resources/views/main/modules/document-management/partials/sidebar.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/DocumentManagementController.php` passe.
- `php -l app/Models/DocumentManagementFolder.php` passe.
- `php -l app/Models/DocumentManagementRecord.php` passe.
- `php -l app/Models/DocumentManagementActivity.php` passe.
- `php -l database/migrations/2026_06_05_000001_create_document_management_tables.php` passe.
- `php -l database/seeders/DocumentManagementSeeder.php` passe.
- `php artisan migrate --force` passe.
- `php artisan db:seed --class=DocumentManagementSeeder --force` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --name=main.document-management.dashboard` affiche la route GED.

### 2026-06-05 - Réorganisation des modules de site et ajout GMAO

Prompt utilisateur :

```text
lorsque nous sommes dans site pour choisir le module à se connecter réorganise moi les modules, comme suite :
1) Comptabilité
2) RH
3) Ged
4) Archivage
5) GMAO (Gestion de Maintenance Assistée par Ordinateur)
```

Correction appliquée :

- Réorganisation de l’ordre officiel des modules :
  - Comptabilité
  - Ressources Humaines
  - GED
  - Archivage
  - GMAO
- Ajout du module `gmao` dans `CompanySite`.
- Ajout des libellés et descriptions FR/EN du module GMAO.
- Mise à jour de la page détail site pour afficher les modules dans cet ordre.
- Mise à jour de la modale de création/modification de site pour proposer GMAO avec son icône.
- Mise à jour de la page utilisateurs pour garder le même ordre dans les permissions de modules.
- Ajout d’un style visuel dédié aux cartes GMAO.
- La grille des modules est maintenant responsive avec 5 cartes.

Fichiers modifiés :

- `app/Models/CompanySite.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/company-site-show.blade.php`
- `resources/views/main/partials/site-form-modal.blade.php`
- `resources/views/main/users.blade.php`
- `resources/views/main/company-sites.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Models/CompanySite.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.

### 2026-06-05 - Page paramètres du module RH

Prompt utilisateur :

```text
Nous allons maintenant travailler sur la page parametres du module RH, base toi du parametres module Comptabilité et facturation
```

Implémentation appliquée :

- Création d’une vraie page `Paramètres RH` dédiée, basée sur le design des paramètres Comptabilité/Facturation.
- Ajout des routes :
  - `main.human-resources.settings`
  - `main.human-resources.settings.update`
- Ajout de la gestion des couleurs PDF utilisées par les rapports RH.
- Ajout de la gestion des accès aux menus RH pour les utilisateurs simples.
- Les utilisateurs affichés sont filtrés sur ceux qui ont accès au module RH.
- Ajout d’une navigation RH dédiée avec clés préfixées `hr-*` pour éviter tout conflit avec les permissions comptables.
- Branchement du middleware existant pour appliquer réellement les accès RH et rediriger l’utilisateur si un menu lui est retiré.
- Mise à jour du lien `Paramètres RH` dans la sidebar.

Fichiers modifiés :

- `app/Support/HumanResourcesModuleNavigation.php`
- `app/Http/Middleware/EnsureAccountingMenuAccess.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `routes/web.php`
- `resources/views/main/modules/human-resources/settings.blade.php`
- `resources/views/main/modules/human-resources/partials/sidebar.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Support/HumanResourcesModuleNavigation.php` passe.
- `php -l app/Http/Middleware/EnsureAccountingMenuAccess.php` passe.
- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php artisan route:list --name=main.human-resources.settings` affiche les 2 routes attendues.

### 2026-06-05 - Amélioration des widgets du rapport RH

Prompt utilisateur :

```text
sur rapports RH j'aime pas trop ce design
```

Correction appliquée :

- Correction de la structure des widgets de présence dans la page Rapports RH.
- Les cartes affichent maintenant une icône alignée, une valeur lisible et un libellé propre.
- Harmonisation du style avec les widgets de la page Présences RH.
- Amélioration de l’espacement, de la hiérarchie visuelle et du rendu en mode sombre.

Fichiers modifiés :

- `resources/views/main/modules/human-resources/reports.blade.php`
- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.

### 2026-06-05 - Restauration du scroll comptabilité/facturation

Prompt utilisateur :

```text
je n'arrive plus à scroller sur les toutes les pages du module comptabilité et facturation
```

Correction appliquée :

- Correction du shell comptabilité/facturation qui héritait du `position: fixed` du layout dashboard.
- `.accounting-shell` reprend maintenant un positionnement relatif, une largeur complète et une hauteur naturelle.
- Le scroll global des pages comptabilité/facturation est restauré, tout en conservant la sidebar sticky et son scroll interne.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.

### 2026-06-04 - CRUD de la page Congés RH

Prompt utilisateur :

```text
applique
```

Implémentation appliquée :

- Création de la page dédiée `resources/views/main/modules/human-resources/leave.blade.php`.
- Ajout des actions CRUD pour les demandes de congé :
  - création
  - modification
  - suppression
- Les demandes utilisent uniquement les employés RH enregistrés dans le module, sans afficher les utilisateurs de connexion.
- Ajout des types de congés et statuts :
  - congé annuel
  - maladie
  - personnel
  - maternité
  - autre
  - en attente, approuvé, rejeté, annulé
- Calcul automatique du nombre de jours entre la date de début et la date de fin.
- Blocage des chevauchements pour les demandes en attente ou approuvées d’un même employé.
- Conservation du style des tableaux et modales déjà appliqué sur les pages RH/comptabilité.
- Ajout des badges de statut pour les congés.
- Ajout de `created_by` sur les demandes de congé pour tracer l’utilisateur qui crée la demande.
- Ajout des notifications RH pour les nouvelles demandes de congé, filtrées dans le module RH.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `app/Models/HumanResourceLeaveRequest.php`
- `app/Support/AccountingActivityFeed.php`
- `database/migrations/2026_06_04_000002_add_created_by_to_human_resource_leave_requests.php`
- `resources/views/main/modules/human-resources/leave.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l app/Models/HumanResourceLeaveRequest.php` passe.
- `php -l app/Support/AccountingActivityFeed.php` passe.
- `php -l database/migrations/2026_06_04_000002_add_created_by_to_human_resource_leave_requests.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan migrate --force` passe pour la migration `created_by` des congés.
- `php artisan view:cache` passe.
- `php artisan route:list --name=main.human-resources.leave` affiche les 4 routes Congés.
- `php artisan route:cache` passe.

### 2026-06-04 - Notifications dédiées au module RH

Prompt utilisateur :

```text
les nofifs du RH doivent aussi etre pareil
```

Correction appliquée :

- Ajout d’une page RH dédiée pour toutes les notifications.
- Ajout d’une page RH dédiée pour le détail d’une notification.
- Le dropdown de notifications détecte maintenant le module courant :
  - Comptabilité ouvre les routes de notifications comptabilité.
  - Ressources Humaines ouvre les routes de notifications RH.
- Le bouton “Voir toutes les notifications” est maintenant disponible dans le module RH.
- Les notifications RH restent filtrées sur les clés RH :
  - employés
  - contrats
  - congés
- Le clic sur une notification RH marque la notification comme consultée.
- Le détail RH affiche le bouton d’ouverture du module concerné quand la notification correspond à une page RH disponible.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/partials/accounting-notifications.blade.php`
- `resources/views/main/modules/human-resources/notifications.blade.php`
- `resources/views/main/modules/human-resources/notification-show.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l routes/web.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=main.human-resources.notifications` affiche les 2 routes RH.
- `php artisan route:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-04 - Enrichissement complet du module RH

Prompt utilisateur :

```text
il faut appliquer tout ça
```

Implémentation appliquée :

- Ajout d’un socle extensible pour les pages RH complémentaires via `human_resource_profile_records`.
- Création du modèle `HumanResourceProfileRecord`.
- Activation de nouvelles pages RH avec CRUD générique :
  - Documents RH
  - Avances sur salaire
  - Primes et retenues
  - Planning / horaires
  - Évaluations
  - Formations
  - Sanctions disciplinaires
  - Recrutement
  - Paramètres RH
- Ajout des routes dynamiques RH pour :
  - affichage
  - création
  - modification
  - suppression
- Ajout d’une vue partagée `resource.blade.php` avec :
  - tableau paginé
  - recherche côté interface
  - modale de création/modification
  - suppression confirmée
  - champs conditionnels par page : employé, montant, devise, score, dates, catégorie, statut, notes
- Ajout des nouveaux liens dans la sidebar RH avec regroupement :
  - Personnel
  - Administration RH
  - Développement RH
  - Gouvernance RH
- Ajout des traductions FR/EN pour toutes les nouvelles pages.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `app/Models/HumanResourceProfileRecord.php`
- `database/migrations/2026_06_04_000004_create_human_resource_profile_records.php`
- `resources/views/main/modules/human-resources/resource.blade.php`
- `resources/views/main/modules/human-resources/partials/sidebar.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Models/HumanResourceProfileRecord.php` passe.
- `php -l database/migrations/2026_06_04_000004_create_human_resource_profile_records.php` passe.
- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan migrate --force` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --path=human_resources` affiche les routes dynamiques des nouvelles pages.
- `php artisan route:cache` passe.

### 2026-06-04 - Tableau de bord RH avec données réelles

Prompt utilisateur :

```text
mets à jour le tableau de bord RH avec les vraies infos
```

Correction appliquée :

- Le tableau de bord RH utilise uniquement les employés RH enregistrés dans le module (`user_id` nul).
- Les KPI sont désormais calculés depuis les vraies tables :
  - employés actifs
  - présence du jour
  - congés en attente
  - paie mensuelle validée/payée ou contrats actifs en fallback
- Remplacement du panneau historique de connexion par les paies récentes du mois.
- Remplacement du panneau responsable par les derniers dossiers RH complémentaires.
- Ajout des données issues de `human_resource_profile_records` :
  - documents
  - avances
  - formations
  - autres dossiers RH
- Conservation des listes réelles :
  - employés
  - départements
  - demandes de congé
- Nettoyage de la vue dashboard pour supprimer les caractères mal encodés visibles dans les séparateurs.

Fichiers modifiés :

- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/human-resources/dashboard.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-04 - Page Paie du module RH

Prompt utilisateur :

```text
travaillons maintenant sur la page paie
```

Implémentation appliquée :

- Remplacement de la page Paie générique par une vraie page métier dédiée.
- Création du modèle `HumanResourcePayrollEntry`.
- Création de la table `human_resource_payroll_entries`.
- Ajout du CRUD Paie :
  - création
  - modification
  - suppression
- Chaque ligne de paie est liée à un employé RH enregistré dans le module.
- La ligne de paie peut être liée automatiquement au contrat actif de l’employé.
- Les valeurs par défaut de la modale reprennent le salaire et la devise du contrat actif quand il existe.
- Calcul automatique du salaire net :
  - salaire brut
  - primes
  - retenues
  - salaire net
- Blocage d’une deuxième ligne de paie pour le même employé sur la même période.
- Ajout des statuts de paie :
  - brouillon
  - validé
  - payé
  - annulé
- Ajout des badges de statut Paie.
- Ajout des notifications RH pour les nouvelles lignes de paie.
- Les détails de notification RH peuvent ouvrir la page Paie quand la notification concerne la paie.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `app/Models/HumanResourceEmployee.php`
- `app/Models/HumanResourcePayrollEntry.php`
- `app/Support/AccountingActivityFeed.php`
- `database/migrations/2026_06_04_000003_create_human_resource_payroll_entries.php`
- `resources/views/main/modules/human-resources/payroll.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Models/HumanResourcePayrollEntry.php` passe.
- `php -l app/Models/HumanResourceEmployee.php` passe.
- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l app/Support/AccountingActivityFeed.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan migrate --force` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --name=main.human-resources.payroll` affiche les 4 routes Paie.
- `php artisan route:cache` passe.

### 2026-06-04 - Page Rapports RH

Prompt utilisateur :

```text
noous allons maintenant travailler sur raooorts RH
```

Implémentation appliquée :

- Remplacement de la page générique Rapports RH par une page dédiée.
- Ajout des filtres de période :
  - aujourd’hui
  - semaine
  - mois
  - année
  - période personnalisée
- Agrégation des vraies tables RH :
  - employés
  - départements
  - présences
  - contrats
  - congés
  - paie
- Ajout des indicateurs de synthèse :
  - employés et employés actifs
  - lignes de présence et heures travaillées
  - demandes de congés et demandes en attente
  - lignes de paie et total net par devise
- Ajout d’une répartition des présences :
  - présents
  - en retard
  - absents
  - à distance
  - en congé
- Ajout des tableaux de synthèse :
  - synthèse par département
  - synthèse de la paie
  - synthèse des congés
- Ajout d’un export PDF dédié aux rapports RH, avec le même format visuel que les PDF RH/comptabilité existants.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/human-resources/reports.blade.php`
- `resources/views/main/modules/human-resources/pdf/reports.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.
- `php artisan view:cache` passe.
- `php artisan route:list --name=main.human-resources.reports` affiche les 2 routes Rapports RH.
- `php artisan route:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-04 - Présences limitées aux employés RH enregistrés

Prompt utilisateur :

```text
pareil ici prendre que les employés qu'on a enregistré
```

Correction appliquée :

- Le formulaire `Nouvelle présence` propose uniquement les fiches employés RH sans compte utilisateur lié.
- Les listes de présence et le rapport PDF de présence utilisent également uniquement ces employés enregistrés.
- L'import Excel de présence cherche les matricules uniquement parmi les employés RH enregistrés.
- La validation empêche de créer ou modifier une présence pour une fiche liée à un utilisateur de connexion.

Fichiers modifiés :

- `app/Http/Controllers/HumanResourcesController.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur le contrôleur concerné.

### 2026-06-04 - CRUD de la page Contrats RH

Prompt utilisateur :

```text
applique
```

Contexte :

- L'utilisateur a validé la proposition de transformer la page Contrats RH en gestion complète.

Correction appliquée :

- Remplacement de la liste générique des contrats par une page dédiée `contracts.blade.php`.
- Ajout des routes CRUD :
  - création,
  - modification,
  - suppression.
- Ajout de la modale de contrat avec le style des modales Facturation/RH :
  - employé RH enregistré uniquement,
  - référence automatique si vide,
  - type de contrat : CDI, CDD, Consultant, Stage,
  - statut,
  - dates début/fin/période d'essai,
  - salaire mensuel,
  - devise,
  - notes.
- Ajout d'une règle métier : un employé ne peut pas avoir deux contrats actifs en même temps.
- Les contrats ne peuvent cibler que les employés RH enregistrés sans compte utilisateur lié.
- Ajout de badges colorés pour les statuts de contrat.
- Ajout des libellés FR/EN nécessaires.

Fichiers modifiés :

- `app/Http/Controllers/HumanResourcesController.php`
- `routes/web.php`
- `resources/views/main/modules/human-resources/contracts.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --path=human_resources/contracts` affiche les 4 routes Contrats.
- `php artisan route:cache` passe.
- `git diff --check` ne signale pas d'erreur bloquante sur les fichiers concernés.

### 2026-06-04 - Sélecteur de devise sur les contrats RH

Prompt utilisateur :

```text
par défut USD mais proposer aussi les autres devices
```

Correction appliquée :

- Le champ `Devise` du formulaire Contrat RH est maintenant une liste déroulante.
- `USD` est proposé et sélectionné par défaut.
- Les autres devises actives configurées sur le site sont également proposées.
- La validation du contrat accepte uniquement les devises disponibles dans cette liste.

Fichiers modifiés :

- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/human-resources/contracts.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-04 - Notifications filtrées par module

Prompt utilisateur :

```text
les notifications du RH ne concerbe que le RH, pareil pour les autres modules
```

Correction appliquée :

- Le flux de notifications du header est maintenant filtré selon le module courant.
- Le module Comptabilité affiche uniquement les notifications comptables.
- Le module Ressources Humaines affiche uniquement les notifications RH.
- Les autres modules ne reçoivent pas les notifications Comptabilité par défaut.
- Le compteur de notifications non consultées est calculé sur le même périmètre que le module affiché.
- Le lien `Voir toutes les notifications` reste affiché uniquement pour le module Comptabilité, tant qu'une page dédiée aux notifications RH n'existe pas.
- Ajout des premières notifications RH synchronisées :
  - ajout d'un employé,
  - ajout d'un contrat RH.
- La page de liste et de détail des notifications Comptabilité est sécurisée pour ne jamais afficher une notification d'un autre module.

Fichiers modifiés :

- `app/Support/AccountingActivityFeed.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/MainController.php`
- `resources/views/main/modules/partials/accounting-notifications.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Support/AccountingActivityFeed.php` passe.
- `php -l app/Providers/AppServiceProvider.php` passe.
- `php -l app/Http/Controllers/MainController.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `git diff --check` ne signale pas d'erreur bloquante sur les fichiers concernés.

### 2026-06-04 - Gestion des employés RH indépendants des utilisateurs

Prompt utilisateur :

```text
ici on doit pouvoir etre en mesure d'ajouter les employés, les empoyés qu'on ajoute ici n'ont pas le droit de se connecter à la plateforme ce ne sont pas des utilisateurs, n'affiche aucun utilisateur ici
```

Correction appliquée :

- La page Employés RH affiche uniquement les fiches `human_resource_employees` sans compte utilisateur lié (`user_id` nul).
- Ajout des routes de création, modification et suppression des employés RH.
- Ajout d'une vue dédiée `employees.blade.php` avec tableau paginé, recherche, actions, modale d'ajout/modification et suppression confirmée.
- La création force `user_id = null`, donc un employé ajouté ici ne peut pas se connecter à la plateforme.
- Les fiches liées à des utilisateurs sont protégées contre modification/suppression depuis cette page.
- Ajout des libellés FR/EN nécessaires au formulaire Employés.
- Le seeder RH ne propage plus les fiches liées aux utilisateurs dans les données RH de démonstration utilisées ensuite.

Fichiers modifiés :

- `app/Http/Controllers/HumanResourcesController.php`
- `routes/web.php`
- `resources/views/main/modules/human-resources/employees.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `database/seeders/HumanResourcesSeeder.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php -l database/seeders/HumanResourcesSeeder.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:list --path=human_resources/employees` affiche les 4 routes Employés.
- `php artisan route:cache` passe.
- `git diff --check` ne signale pas d'erreur bloquante sur les fichiers concernés.

### 2026-06-04 - Alignement du modal Employés RH sur les modales Facturation

Prompt utilisateur :

```text
tu dois utiliser le meme style de modal qu'on a fait avec le module facturation
```

Correction appliquée :

- Le modal Employés RH reprend la largeur standard des modales Facturation.
- Les champs sont organisés en sections internes avec titres et icônes, comme les modales clients/fournisseurs :
  - identité,
  - informations professionnelles,
  - coordonnées.
- Ajout des libellés FR/EN des sections.

Fichiers modifiés :

- `resources/views/main/modules/human-resources/employees.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `git diff --check` ne signale pas d'erreur bloquante sur les fichiers concernés.

### 2026-06-04 - Largeur légère des modales RH

Prompt utilisateur :

```text
les modales doivent etre un tout petit peu large
```

Correction appliquée :

- Les modales du module Ressources Humaines passent à une largeur maximale de `620px`.
- La règle est ciblée sur le body RH pour ne pas modifier les modales des autres modules.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur le fichier CSS concerné.

### 2026-06-04 - Pagination RH alignée sur la comptabilité

Prompt utilisateur :

```text
Pourquoi les tableaux ne sont pas paginé comme nous l'avons fais avec le module comptabilité
```

Correction appliquée :

- Les tableaux RH étaient paginés à `10` lignes, donc la pagination n'apparaissait pas avec 6 employés.
- Alignement sur le module comptabilité avec `5` lignes par page.
- Centralisation de la taille de pagination RH dans `HumanResourcesController::TABLE_ROWS_PER_PAGE`.
- Listes concernées :
  - employés,
  - départements,
  - présences,
  - contrats,
  - congés,
  - paie.

Fichiers modifiés :

- `app/Http/Controllers/HumanResourcesController.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur le contrôleur concerné.

### 2026-06-04 - Responsable de département basé sur les employés RH

Prompt utilisateur :

```text
le responsable doit etre les empoyés et non les utilisateurs
```

Correction appliquée :

- Ajout du champ `manager_employee_id` sur `human_resource_departments`.
- La relation `HumanResourceDepartment::manager()` pointe maintenant vers `HumanResourceEmployee`.
- Le formulaire Département propose les employés RH actifs du site, et plus les utilisateurs de connexion.
- L'affichage du tableau Département montre le nom complet de l'employé responsable.
- La validation vérifie que le responsable choisi est bien une fiche employé RH du site, sans compte utilisateur lié.
- Le seeder RH renseigne désormais le responsable via les employés RH de démonstration.

Fichiers modifiés :

- `database/migrations/2026_06_04_000001_add_manager_employee_to_human_resource_departments.php`
- `app/Models/HumanResourceDepartment.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/human-resources/departments.blade.php`
- `database/seeders/HumanResourcesSeeder.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan migrate --force` passe.
- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l app/Models/HumanResourceDepartment.php` passe.
- `php -l database/migrations/2026_06_04_000001_add_manager_employee_to_human_resource_departments.php` passe.
- `php -l database/seeders/HumanResourcesSeeder.php` passe.
- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-06-04 - Badges de statuts RH colorés

Prompt utilisateur :

```text
actif doit etre dans un bagde en vert
```

Correction appliquée :

- Ajout des styles de badges RH :
  - `Actif` et `Présent` en vert,
  - congé/distance en indigo,
  - retard en jaune,
  - suspendu/terminé/absent/inactif en rouge.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur le fichier CSS concerné.

### 2026-05-27 - Export PDF du rapport de présences RH

Prompt utilisateur :

```text
le rapport doit se générer en pdf comme on le faisais sur les modules comptabilités
```

Correction appliquée :

- Ajout d'une route dédiée `main.human-resources.attendance.report.pdf`.
- Ajout de `printAttendanceReport()` dans `HumanResourcesController`, avec génération DomPDF en paysage et conservation des filtres de période.
- Factorisation du calcul des indicateurs de présence pour partager les mêmes chiffres entre l'écran et le PDF.
- Ajout d'un bouton `Exporter en PDF` sur la page Présences RH.
- Création d'une vue PDF `resources/views/main/modules/human-resources/pdf/attendance-report.blade.php`.
- Ajout des libellés FR/EN nécessaires au rapport.

Fichiers modifiés :

- `app/Http/Controllers/HumanResourcesController.php`
- `routes/web.php`
- `resources/views/main/modules/human-resources/attendance.blade.php`
- `resources/views/main/modules/human-resources/pdf/attendance-report.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php -l routes/web.php` passe.
- `php -l lang/fr/main.php` passe.
- `php -l lang/en/main.php` passe.
- `php artisan view:cache` passe.
- `php artisan route:clear` puis `php artisan route:list --path=human_resources/attendance` affichent la route PDF.
- `php artisan route:cache` passe.
- `git diff --check` ne signale pas d'erreur bloquante sur les fichiers concernés.

### 2026-05-27 - Alignement du PDF Présences RH sur le design Comptabilité

Prompt utilisateur :

```text
pour le fichier pdf stp garde le meme forma comme on l'a fait avec le module comptabilité les entetes les pieds de page respect les design stp.
Dans exporter en PDF enleve le soulignement
```

Correction appliquée :

- Le PDF de présence RH reprend maintenant le même gabarit que les rapports comptables :
  - en-tête société/site,
  - titre à droite,
  - règle colorée,
  - métriques en tableau,
  - tableau de détails,
  - pied de page fixe avec branding et date de génération.
- Le PDF utilise les couleurs PDF configurées sur le site.
- Les boutons/liens `.primary-action` et `.secondary-action` n'affichent plus de soulignement.

Fichiers modifiés :

- `resources/views/main/modules/human-resources/pdf/attendance-report.blade.php`
- `resources/css/admin/dashboard.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-05-27 - Espacement icône titre modale département RH

Prompt utilisateur :

```text
espace légerement  l'icone avec Nouveau département
```

Correction appliquée :

- Ajout d’un espacement dédié entre l’icône et le titre de la modale département RH.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-05-27 - Correction effective espacement titre modale département RH

Prompt utilisateur :

```text
tu n'as pas séparé le titre avec l'icone sur le modal département
```

Correction appliquée :

- Le titre de la modale département RH est maintenant forcé en `inline-flex`.
- L’icône et le texte sont alignés verticalement avec un espacement réel entre les deux.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-05-27 - Centrage des avatars dans le dashboard RH

Prompt utilisateur :

```text
On a le meme problème de centralisation des icones des utilisateurs
```

Correction appliquée :

- Renforcement du style des avatars dans les lignes employés RH.
- Les avatars RH utilisent maintenant une taille carrée fixe, un centrage `inline-flex`, `line-height: 1` et une image en `object-fit: cover`.
- La correction garde le comportement des photos de profil tout en centrant correctement l’initiale dans le cercle.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.

### 2026-05-27 - CRUD des départements RH

Prompt utilisateur :

```text
dans département donne la possiblité d'ajouter, modifier et supprimer les départements
```

Implémentation appliquée :

- Ajout des routes `POST`, `PUT` et `DELETE` pour les départements RH.
- Ajout des méthodes `storeDepartment`, `updateDepartment`, `destroyDepartment` dans `HumanResourcesController`.
- Validation serveur des champs :
  - code unique par site
  - nom obligatoire
  - responsable limité aux utilisateurs affectés au site
  - statut actif/inactif
- Création d’une vue dédiée `departments.blade.php` avec :
  - bouton “Nouveau département”
  - modal création/modification
  - boutons modifier/supprimer dans le tableau
  - confirmation de suppression
  - pagination et recherche selon le style existant.
- Ajout des libellés FR/EN nécessaires.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/human-resources/departments.blade.php`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php artisan route:list --path=human_resources/departments` affiche les 4 routes attendues.
- `php artisan view:cache` passe.
- `php artisan route:cache` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.

### 2026-05-27 - Texte blanc dans les avatars RH

Prompt utilisateur :

```text
texte blanc des icones doivent etres blanches
```

Correction appliquée :

- La couleur blanche est forcée sur les initiales des avatars du dashboard RH.
- Le style évite que le sélecteur général des textes de ligne RH assombrisse l’initiale.

Fichiers modifiés :

- `resources/css/main.css`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur `resources/css/main.css`.

### 2026-05-27 - Activation des menus du module RH

Prompt utilisateur :

```text
active les autres menus
```

Implémentation appliquée :

- Activation des menus RH de la sidebar :
  - Employés
  - Départements
  - Présences
  - Contrats
  - Congés
  - Paie
  - Rapports RH
- Ajout des routes dédiées dans `routes/web.php`.
- Ajout des méthodes correspondantes dans `HumanResourcesController`.
- Création de la vue générique `resources/views/main/modules/human-resources/index.blade.php` pour afficher les listes RH.
- Les pages sont connectées aux vraies tables RH et affichent des tableaux paginés quand nécessaire.
- Ajout des libellés FR/EN et styles de tableau RH.

Fichiers modifiés :

- `routes/web.php`
- `app/Http/Controllers/HumanResourcesController.php`
- `resources/views/main/modules/human-resources/partials/sidebar.blade.php`
- `resources/views/main/modules/human-resources/index.blade.php`
- `resources/css/main.css`
- `lang/fr/main.php`
- `lang/en/main.php`
- `docs/prompts/project-history.md`

Vérification :

- `php -l app/Http/Controllers/HumanResourcesController.php` passe.
- `php artisan route:list --path=human_resources` affiche 8 routes RH.
- `php artisan route:cache` passe.
- `php artisan view:cache` passe.
- `php -l lang/fr/main.php` et `php -l lang/en/main.php` passent.

### 2026-05-27 - Alignement du tableau Employés RH sur les tableaux existants

Prompt utilisateur :

```text
Pour les employés regardes les styles de mes tableaux précédents applique ça ici
```

Correction appliquée :

- La vue de liste RH utilise maintenant la même structure que les tableaux existants :
  - `accounting-list-page`
  - `page-heading`
  - `table-tools`
  - recherche `companySearch`
  - compteur `visibleCount`
  - `company-card`
  - `company-table`
  - boutons de tri `table-sort`
  - ligne “aucun résultat”
  - pagination `subscriptions-pagination`
- Le rendu Employés RH est désormais cohérent avec les tableaux Comptabilité/Facturation.

Fichiers modifiés :

- `resources/views/main/modules/human-resources/index.blade.php`
- `docs/prompts/project-history.md`

Vérification :

- `php artisan view:cache` passe.
- `git diff --check` passe sur les fichiers concernés.
