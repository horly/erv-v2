# Exports de base de donnees

Ce dossier est prevu pour contenir un export SQL versionnable de la base de donnees de developpement.

Fichier attendu :

```text
database/exports/erp_database.sql
```

Pour generer l'export depuis la machine principale :

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\export-database.ps1
```

Pour importer l'export sur une autre machine apres le clone :

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\import-database.ps1
```

Exporter aussi les fichiers publics uploades, comme les logos d'entreprise :

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\export-public-storage.ps1
```

Importer ces fichiers publics sur une autre machine :

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\import-public-storage.ps1
php artisan storage:link
```

Notes :

- MySQL/XAMPP doit etre lance avant export ou import.
- Le script lit la connexion depuis `.env`.
- Verifier le contenu avant commit si la base contient des donnees sensibles.
- Ne pas versionner de secrets dans `.env`; utiliser `.env.example` comme modele.
- La base stocke seulement les chemins des fichiers uploades. Les fichiers eux-memes doivent etre transportes avec `public-storage.zip` ou recopies depuis `storage/app/public`.
