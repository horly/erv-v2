# Déploiement ERP v2 — ERP PLUS et EXAD

État relevé le 6 novembre 2026, heure de Kinshasa. Projet local :
`D:\App\Codex\erv-v2`.

Cette note reconstitue les éléments retrouvés dans les anciens échanges et vérifiés
sur les hébergements. Les premiers relevés distants ont été réalisés en lecture seule. Le déploiement
Hostinger est documenté en section 7 et celui de LWS en section 8.

Ce document concerne uniquement ERP v2. ERP Advanced et Esengo constituent un
autre projet.

## 1. Destinations et chemins vérifiés

| Élément | ERP PLUS / Prestavice | EXAD / LWS |
|---|---|---|
| Site | https://erpplus.prestavice.com | https://erp.exadgroup.org |
| Accès vérifié | SFTP / SSH | FTP explicite sur TLS (FTPS) |
| Hôte | 147.93.92.142 | ftp.exadgroup.org |
| Port | 65002 | 21 |
| Racine de session | /home/u336848046 | / |
| Dossier du projet | /home/u336848046/domains/prestavice.com/public_html/erpplus | /erp.exadgroup.org/erv-v2 |
| Fichiers publics | public/ à l'intérieur du projet | /erp.exadgroup.org |
| Version PHP CLI vérifiée | 8.2.35 | Non vérifiée |

Les mots de passe et clés privées ne sont pas conservés dans cette documentation.

### Correspondance avec les chemins du panneau

- ERP PLUS : le chemin annoncé `/public_html/erpplus` correspond au dossier du
  domaine **prestavice.com**, pas à la racine absolue SFTP. Dans le compte SSH,
  utiliser le chemin complet indiqué dans le tableau.
- Le lien `public_html` à la racine du compte SFTP pointe vers un autre site du
  compte. Ne pas l'utiliser pour ERP PLUS.
- LWS : le chemin historique/panneau `/htdocs/erp.exadgroup.org` est exposé dans
  la session FTP sous `/erp.exadgroup.org`. La racine FTP correspond déjà à
  `htdocs`.
- Au contrôle du 6 novembre, `ftp.exadgroup.org` et `erp.exadgroup.org`
  résolvent vers **83.229.19.90**. L'ancienne adresse **185.98.131.159** fournie
  dans les paramètres n'a pas été utilisée pour une connexion authentifiée.

## 2. ERP PLUS — ancienne installation retrouvée

Source : historique SSH du compte, séquence contenant explicitement le clonage
de `https://github.com/horly/erv-v2.git`, puis l'entrée dans `erpplus/`.

Commandes retrouvées, dans leur ordre historique, avec répétitions retirées :

```text
git clone https://github.com/horly/erv-v2.git
cd erpplus/
composer update
php artisan migrate
php artisan optimize:clear
composer dump-autoload
```

Cette liste est une trace historique, pas un script complet à exécuter :
le passage du dossier cloné `erv-v2` au nom `erpplus` n'est pas établi par
les commandes ci-dessus. La configuration du sous-domaine dans le panneau
d'hébergement n'a pas été inspectée.

Organisation constatée :

```text
/home/u336848046/domains/prestavice.com/public_html/erpplus/
├── artisan
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── vendor/
└── public/
    ├── index.php
    └── .htaccess
```

Le fichier `public/index.php` conserve les chemins Laravel standard :
`../storage`, `../vendor/autoload.php` et `../bootstrap/app.php`.
Aucun `.htaccess` n'a été trouvé à la racine du projet ; celui lu se trouve
dans `public/`. La racine web effective doit être confirmée dans le panneau
avant de modifier le paramétrage du domaine.

État Git distant relevé :

- HEAD : `02f2cc4` (« Fix 3 », 3 juillet 2026).
- Modifications locales au serveur : `AdminController.php`, `AppBranding.php`,
  `composer.lock`, traductions admin, CSS de l'admin/connexion/interface et
  vue des paramètres de l'application.
- Dossier non suivi présent : `storage/deploy-backups/` ; son contenu n'a pas
  été inspecté.

Ces différences doivent être comparées et sauvegardées avant une mise à jour.
La présence d'un dossier de sauvegarde ne prouve pas qu'une restauration est
possible.

## 3. LWS — organisation et adaptation du point d'entrée

Source historique : chat **« Mettre à jour historique prompts »**, échanges
du **26 juin 2026**. Le propriétaire y confirme LWS, le dossier `erv-v2`,
le chemin des uploads et le fonctionnement du lien symbolique.

Organisation confirmée par listing FTP le 6 novembre :

```text
/erp.exadgroup.org/
├── index.php
├── .htaccess
├── js/
├── vendor/                  # fichiers publics, distincts de Composer
├── img/
├── user-files               # lien symbolique décrit dans l'ancien échange
└── erv-v2/
    ├── artisan
    ├── app/
    ├── bootstrap/
    ├── resources/
    ├── storage/
    ├── vendor/              # dépendances PHP Composer
    └── public/
        └── user-files/
```

Le `index.php` réellement lu à la racine du sous-domaine contient :

```php
// Fichier de maintenance :
__DIR__.'/erv-v2/storage/framework/maintenance.php';

// Autoload :
require __DIR__.'/erv-v2/vendor/autoload.php';

// Application :
$app = require_once __DIR__.'/erv-v2/bootstrap/app.php';
```

Le `.htaccess` de cette racine réécrit les requêtes vers `/index.php`.
Il faut préserver ces adaptations ; recopier directement le `public/index.php`
local standard à cet emplacement changerait les chemins attendus.

Commande du lien symbolique retrouvée dans l'ancien chat, exécutée depuis
la racine du sous-domaine :

```text
ln -s erv-v2/public/user-files user-files
```

L'ancien échange confirme son fonctionnement. Le listing actuel confirme
l'entrée `user-files`, mais la cible du lien n'a pas été relue directement.
Ne pas recréer ou remplacer ce lien sans vérifier son état.

## 4. Connexion FTPS LWS

Le serveur présente un certificat `*.lwspanel.com`, émis par Sectigo,
qui ne porte pas le nom `ftp.exadgroup.org`. Ce comportement est documenté par
[LWS](https://aide.lws.fr/base/Hebergement-web-mutualise/Fichiers/ftp-securise-lws-panel-certificat-lwspanel-com-reprise-session-tls-sftp).

Pour un client habituel, relever dans le LWS Panel le nom exact du serveur
`webdbNNNN.lwspanel.com` et l'utiliser comme hôte FTPS, port 21.
Ne pas reprendre le numéro d'un exemple de la documentation.

Lors du contrôle, l'accès a abouti en FTPS chiffré après validation de la chaîne
Sectigo et du certificat fournisseur attendu, puis épinglage de son empreinte
exacte pour la session. La tentative FTP non chiffrée a été refusée avant
exécution. Aucun réglage permanent du serveur n'a été changé.

## 5. Préparation de la prochaine mise à jour

Les étapes suivantes constituent la préparation à effectuer ; elles n'ont pas
été exécutées en production :

1. Sauvegarder séparément la base, la configuration de production et les fichiers
   utilisateurs de chaque site, et vérifier la restauration.
2. Comparer les modifications déjà présentes sur ERP PLUS avec la version locale
   souhaitée ; ne pas les écraser par un transfert ou une synchronisation globale.
3. Préparer une version précise de l'application avec son `composer.lock`.
   Pour reproduire cette version, utiliser `composer install` avec les options
   de production appropriées, plutôt que reprendre le `composer update`
   de l'installation historique.
4. Préparer les fichiers applicatifs et les ressources publiques selon
   l'organisation propre à chaque hébergeur. Conserver les adaptations LWS
   du point d'entrée et les fichiers utilisateurs existants.
5. Contrôler les migrations en attente et les chemins de stockage réels avant
   de les exécuter.
6. Après une mise à jour autorisée, reconstruire les caches nécessaires et
   vérifier connexion, logos, fichiers privés, facturation et accès par site.

### Points particuliers de la version locale du 6 novembre

- PHP minimum du code et des dépendances locales testées : 8.2. Le paquet LWS préparé conserve les fichiers Composer du serveur, qui exigent PHP `^8.3` ; voir la section 6.
- Les CSS et JavaScript applicatifs sont principalement lus depuis
  `resources/` et intégrés aux vues. Il faut transférer ce dossier ainsi que
  `public/vendor/`, notamment les nouvelles fontes Inter.
- Uploads publics actuels : `public/user-files`.
- Documents GED et archives privés : `storage/app/private`.
- Les scripts historiques d'export/import des fichiers publics visent encore
  `storage/app/public` ; ils ne constituent pas une sauvegarde complète du
  stockage actuel.
- Trois migrations locales concernent le début du suivi de caisse, le paiement
  de TVA et le déplacement des documents sensibles vers le stockage privé.
  Leur état sur les bases de production n'a pas été vérifié.
- Le déplacement des documents utilise `public_path('user-files')`. Il faut
  confirmer ce chemin dans l'organisation réelle de chaque hébergement avant
  l'exécution, particulièrement chez LWS.
## 6. LWS — préparation du 6 novembre 2026, activation non effectuée

Le déploiement demandé concerne **LWS uniquement**. Aucun fichier applicatif,
configuration, migration ou contenu de base de données n'a été modifié en
production. Le paquet décrit ci-dessous est préparé, mais **n'est pas encore
validé pour activation**.

### Accès et comparaison de l'existant

- L'accès FTPS a été vérifié et fonctionne.
- L'accès SSH à `ftp.exadgroup.org:22` refuse le compte fourni ainsi que les clés
  locales essayées. L'activation de SSH et/ou l'identifiant SSH approprié ont été
  demandés au propriétaire du compte. Aucun accès shell LWS n'est établi.
- Une clé locale a été rapprochée de la clé PuTTY préexistante. Empreinte publique
  relevée, conservée uniquement pour identification :

  ```text
  ssh-ed25519 255 332YnX9Hmw9QVY0ExXIV7Ijhjci2i/q+zUW3Jn4ZSLc
  ```

- La comparaison des **409 fichiers du code serveur** est terminée : `app/`,
  `config/`, `resources/`, `routes/`, `lang/`, les 68 migrations, les deux fichiers
  `bootstrap/` et `composer.json`. Après normalisation des fins de ligne,
  **408 correspondent exactement au HEAD local `67556c0`**. Seul `composer.json`
  diffère par la contrainte PHP `^8.3`, préservée dans le paquet. Aucun autre
  changement propre au serveur n'a été identifié dans ce périmètre. Ce contrôle
  ne couvre pas les fichiers utilisateurs, `.env`, les caches ou le contenu de
  `vendor/`.

### Correctifs et validation locale

Les préparatifs comprennent la suppression du BOM avant la déclaration de
namespace du contrôleur GED, la reprise de la migration de stockage privé avec
traitement des collisions de fichiers, et le cloisonnement des notifications de
tickets caisse. Un utilisateur standard ne peut plus lire le ticket d'un autre
utilisateur via la liste ou le détail des notifications.

La suite locale complète passe : **119 tests, 1 321 assertions, aucun échec ni
erreur**. Les six tests aux attentes anciennes ont été actualisés sans changer
les règles métier. Rapport :
`tmp/deployment-20261106/phpunit-results.xml`.

Ces tests ont utilisé les dépendances locales **Laravel 12.58** et SQLite en
mémoire. Ils ne constituent pas une validation des dépendances, extensions PHP,
configuration ou données de production LWS.

### Paquet prévu pour LWS

| Élément | Valeur |
|---|---|
| Archive candidate | `tmp/deployment-20261106/lws-code-release.zip` |
| Manifeste | `tmp/deployment-20261106/lws-code-release-manifest.json` |
| Nombre de fichiers | 444 |
| SHA-256 | `fe951f0ce52e9a1d3506722251507679298fe7407f69743b39fea0f063ce6713` |
| Contenu | Code, assets publics, point d'entrée et règles LWS, avec blocage de l'accès HTTP au sous-dossier `erv-v2` |
| Exclusions | Dépendances Composer `vendor/`, `.env`, base de données et fichiers utilisateurs |

Le dossier public `vendor/` contenant les assets est distinct du `vendor/`
Composer exclu du paquet. Les fichiers `composer.json` et `composer.lock` du
paquet sont les copies exactes de ceux du serveur : ils préservent **Laravel
12.62** et la contrainte **PHP `^8.3`**. Ne pas les remplacer par le lock local
utilisé pour les tests.

L'archive plus volumineuse `lws-release-candidate.zip` contient les dépendances
locales. **Ne pas l'utiliser en production** : elle provoquerait un retour à une
version antérieure des dépendances du serveur.

### Conditions restant à satisfaire avant activation

1. Vérifier les éventuelles modifications du serveur depuis ce relevé et préserver ses
   modifications nécessaires. Vérifier les versions PHP web et CLI, les
   extensions, les dépendances installées et les migrations réellement en
   attente, ainsi que les chemins publics et privés.
2. Sauvegarder la base, `.env`, les uploads publics et privés et les anciens
   fichiers applicatifs **hors de la racine web**. Préparer et vérifier la
   procédure de restauration avant remplacement.
3. Activer la maintenance, puis appliquer le paquet en préservant configuration
   et données existantes. Conserver le point d'entrée LWS et vérifier la
   protection HTTP du sous-dossier applicatif.
4. Installer ou vérifier les dépendances avec `composer install` selon le lock
   de production conservé, sans `composer update` ni remplacement par les
   dépendances locales. Contrôler la compatibilité de l'environnement effectif.
5. Après contrôle des migrations et sauvegardes, exécuter `php artisan migrate
   --force`, puis vider et reconstruire les caches nécessaires.
6. Vérifier la connexion, la facturation, les fichiers publics, le téléchargement
   privé authentifié, le refus d'accès intersites et les notifications de caisse.
   Retirer la maintenance seulement après validation ; sinon appliquer le retour
   arrière préparé.

Aucune de ces étapes d'activation n'a été exécutée. La disponibilité de FTPS
seule ne remplace pas un moyen vérifié d'exécuter les commandes Laravel et
Composer sur LWS.

Guide LWS pour rétablir l'accès : [connexion SSH sur hébergement LWS](https://aide.lws.fr/base/Hebergement-web-mutualise/Terminal-web/comment-se-connecter-en-ssh-hebergement-web-lws), rubrique Logiciels → Accès SSH du panneau.

## 7. Hostinger — déploiement effectué le 6 novembre 2026

ERP v2 a été mis à jour sur **https://erpplus.prestavice.com**. La maintenance
est retirée. LWS n'a pas été modifié durant cette opération.

- Application : /home/u336848046/domains/prestavice.com/public_html/erpplus.
- Sauvegardes privées : /home/u336848046/deployments/erpplus-20261106.
  Le dossier contient database.sql.gz, full-before.tgz, backup.sha256,
  backup-file-list.txt et les relevés de contrôle. Les archives sont hors du web,
  en permissions 0600 ; leur intégrité gzip/tar a été contrôlée.
- Les adaptations applicatives du serveur correspondaient au HEAD local 67556c0.
  Le paquet applique aussi les modifications locales et correctifs validés.
- Archive locale : tmp/deployment-20261106/hostinger/release.zip.
  SHA-256 : 3808c230d8433bf2e3db1042354a423da95f74e78a4fc356b96c11dda8143746.
  Les 427 fichiers déployés ont été vérifiés par SHA-256 sur le serveur.
- composer.json et composer.lock de production conservés ; Laravel 12.64.0,
  PHP CLI 8.2.35. Composer install --no-dev a retiré 35 paquets de développement,
  sans installer ni mettre à jour les dépendances de production. Les caches
  packages/services/config obsolètes référençaient Pail et ont été supprimés
  puis régénérés avant de poursuivre.
- Les trois migrations de novembre ont réussi (batch 2) : début du suivi caisse,
  suivi du paiement TVA et stockage privé. Aucun document sensible n'était présent
  dans les deux dossiers publics visés par la migration.
- Les nombres d'enregistrements des 116 tables métier contrôlées sont inchangés.
  Le hash du fichier .env est inchangé. Ce contrôle ne signifie pas que chaque
  valeur de chaque colonne est identique : les migrations ajoutent leurs champs,
  notamment la date de début du suivi caisse.
- Caches configuration, routes et vues reconstruits.
- Vérifications HTTP après réouverture : /login 200, fonte Inter 200, /main 302
  vers authentification, /private-storage/deploy-check.txt 302,
  /public-storage/document-management/deploy-check.txt 404, /composer.json 404,
  /storage/app/private/deploy-check.txt 403. La page de connexion sert le CSS Inter.
- Les 119 tests / 1 321 assertions locaux avaient réussi avant déploiement.
  Aucun parcours connecté avec un compte utilisateur n'a été exécuté en production.

En cas de restauration nécessaire, remettre la maintenance, restaurer les fichiers
depuis full-before.tgz et la base depuis database.sql.gz avec les paramètres privés
de production, reconstruire les caches puis vérifier avant de retirer la maintenance.
Ne pas publier ces sauvegardes ni les copier dans un dossier servi par le web.

## 8. LWS — déploiement effectué le 6 novembre 2026

ERP v2 est déployé sur **https://erp.exadgroup.org** ; maintenance retirée.
Cette section remplace l'état d'attente de la section 6.

- SSH : exadgroup.org (83.229.19.90), port 22, utilisateur exadg758804.
  Clé dédiée ajoutée par le propriétaire : C:\Users\Horly-Exad\.ssh\lws_erp_v2.
  Ne jamais publier cette clé privée. La clé publique porte le suffixe .pub.
- Empreinte serveur conservée dans C:\Users\Horly-Exad\.ssh\known_hosts_erp_lws.
- Racine web SSH : /htdocs/erp.exadgroup.org ; application : sous-dossier erv-v2.
  Le lien user-files vers erv-v2/public/user-files est conservé.
- La cible FTPS /erp.exadgroup.org correspond à ce dossier SSH sous /htdocs.
  L'ancienne IP 185.98.131.159 refuse les identifiants : ne pas l'utiliser.
- Le shell ne fournit pas scp. Transfert réalisé par flux binaire SSH vers PHP.

Sauvegardes privées : **/home/erp-v2-deploy-20261106**, hors du web, permissions
0700 pour le dossier et 0600 pour les archives. Fichiers : database.sql.gz,
full-before.tgz, backup.sha256, backup-file-list.txt et relevés de contrôle.
Intégrité gzip/tar vérifiée ; six fichiers de stockage extraits puis comparés
par SHA-256 avec les fichiers conservés après déploiement.

Les 412 fichiers du relevé initial ont été vérifiés. Le paquet de la section 6
a été appliqué : **444 fichiers écrits et vérifiés par SHA-256**.
composer.json et composer.lock de production conservés : Laravel **12.62.0**.
PHP CLI : **8.5.12**, prérequis Composer satisfaits. Installation sans dépendances
de développement : 35 suppressions, aucune installation ni mise à jour des
dépendances de production. Caches packages, configuration, routes et vues reconstruits.

Les trois migrations de novembre ont réussi en batch 2. Aucun document sensible
n'était présent dans les dossiers publics visés par la migration de déplacement.
Nombres d'enregistrements des 116 tables métier contrôlées et hash de .env inchangés.
Les champs ajoutés par les migrations, notamment le début du suivi caisse, sont attendus.

Contrôles HTTP après réouverture :

- /login et fonte Inter : 200 ; CSS Inter présent.
- / et /main : 302 vers /login.
- /private-storage/deploy-check.txt : 302 vers /login.
- /public-storage/document-management/deploy-check.txt : 404.
- /erv-v2/composer.json et /erv-v2/storage/app/private/deploy-check.txt : 403.
  Le .htaccess web bloque désormais l'accès HTTP direct au sous-dossier erv-v2.

Les 119 tests / 1 321 assertions locaux avaient réussi avant déploiement.
Aucun parcours connecté avec un compte utilisateur n'a été exécuté en production.

Pour restaurer : maintenance, restauration des fichiers du sous-domaine depuis
full-before.tgz et de la base depuis database.sql.gz, reconstruction des caches,
vérification puis réouverture. Ces sauvegardes contiennent la configuration privée
et doivent rester hors du web.

## 9. Mise à jour CSS sur les deux sites — 20261106-111451

Les trois feuilles de style resources/css/main.css, resources/css/admin/dashboard.css
et resources/css/auth/login.css ont été publiées sur Hostinger et LWS : typographie
plus compacte, champs et boutons réduits, tailles adaptées aux petits écrans.

Les 427 fichiers applicatifs ont été contrôlés avant publication. Les trois CSS
ont été sauvegardés puis remplacés et vérifiés par SHA-256. Les vues ont été
reconstruites. Les pages /login des deux sites répondent 200 et contiennent les
nouvelles tailles de 13.5px et 12.5px. Configuration et dépendances inchangées ;
aucune migration ni modification de base effectuée.

Sauvegardes des CSS précédents dans le sous-dossier before :
- Hostinger : /home/u336848046/deployments/erpplus-css-20261106-111451
- LWS : /home/erp-v2-css-20261106-111451

Paquet et manifestes locaux : D:\App\Codex\erv-v2\tmp\deployment-20261106-111451-css.
Ce relevé représente la version CSS la plus récente ; les archives précédentes
restent conservées pour l'historique et la restauration.

## 10. Modèles d'impression publiés sur les deux sites — 20261106-113048

Les modèles proforma-invoice-print, sales-invoice-print, credit-note-print et
purchase-order-print ont été publiés sur Hostinger et LWS. Ils masquent les
sections TVA et, pour les documents concernés, les remises lorsqu'elles ne
s'appliquent pas.

Validation locale : 2 tests PDF existants réussis, 131 assertions ; compilation
Blade et syntaxe PHP des quatre modèles réussies. Contrôle des 427 fichiers
applicatifs avant publication ; sauvegarde des quatre fichiers remplacés,
vérification SHA-256, reconstruction des vues et HTTP 200 sur /login des deux sites.
Aucune migration ni modification de base, de configuration ou de dépendances.

Sauvegardes dans les sous-dossiers before :
- Hostinger : /home/u336848046/deployments/erpplus-print-20261106-113048
- LWS : /home/erp-v2-print-20261106-113048

Paquet, résultats et manifeste complet de cette version : D:\App\Codex\erv-v2\tmp\deployment-20261106-113048.
Le pointeur docs/deployment/latest-release.json désigne désormais ce déploiement.
Les anciens paquets restent conservés.
