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

Notes :

- MySQL/XAMPP doit etre lance avant export ou import.
- Le script lit la connexion depuis `.env`.
- Verifier le contenu avant commit si la base contient des donnees sensibles.
- Ne pas versionner de secrets dans `.env`; utiliser `.env.example` comme modele.
