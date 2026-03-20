# Dépôt de fichiers – KT-Drop

Application web de dépôt de fichiers auto-hébergée, développée en PHP natif. Elle permet aux utilisateurs authentifiés de déposer, prévisualiser, télécharger et supprimer des fichiers via une interface d'administration.

---

## Stack technique

| Composant | Choix |
|---|---|
| Langage | PHP 8.3+ (strict types) |
| Base de données | SQLite 3 (PDO) |
| Frontend | Bootstrap 5.3 + Bootstrap Icons |
| Dépendances | `vlucas/phpdotenv` uniquement |
| Architecture | MVC, front controller unique, router maison |

---

## Fonctionnalités

### Gestion des fichiers
- Upload par glisser-déposer ou sélection (drag & drop avec retour AJAX)
- Téléchargement sécurisé des fichiers enregistrés
- Prévisualisation intégrée : images, PDF, fichiers texte (TXT, CSV, JSON…)
- Suppression avec protection CSRF
- Recherche par nom de fichier
- Pagination configurable

### Sécurité du stockage
- Nom de fichier stocké aléatoire (`date_hex.ext`) — l'original est conservé en base
- Empreinte SHA-256 calculée à l'upload
- Vérification MIME type via `ext-fileinfo`
- Liste blanche d'extensions autorisées
- Taille maximale configurable indépendamment de `php.ini`

### Administration
- Authentification par session avec protection CSRF
- Tableau de bord paginé avec compteur de fichiers
- Vue liste simplifiée (affichage de tous les fichiers sans pagination)
- Affichage de messages flash pour retours d'action

---

## Prérequis

- PHP ≥ 8.3 avec extensions `pdo_sqlite`, `sqlite3`, `fileinfo`
- Composer
- Apache avec `mod_rewrite` activé

---

## Installation

```bash
# 1. Installer les dépendances
composer install

# 2. Configurer l'environnement
cp .env.example .env

# 3. Initialiser la base de données
php scripts/init_db.php
```

Pointer le document root d'Apache sur le dossier `public/`.

> **Important :** Modifier immédiatement les identifiants admin par défaut après l'installation (voir ci-dessous).

---

## Configuration

Toute la configuration se fait dans `.env` :

```ini
# Application
APP_NAME="KT-Drop"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.be/

# Pagination
FILES_PER_PAGE=10

# Base de données
DB_DATABASE=database/app.sqlite

# Stockage
STORAGE_PATH=storage/files
MAX_UPLOAD_SIZE=104857600
```

### Extensions autorisées

Les types de fichiers acceptés sont définis dans `FileStorageService` :

```
pdf, txt, zip, rar, 7z
jpg, jpeg, png, gif, webp
doc, docx, xls, xlsx, ppt, pptx
csv, mp3, mp4
```

---

## Compte admin par défaut

```
Email        : admin@kt-drop.local
Mot de passe : admin1234
```

> **À modifier impérativement avant toute mise en production.**
> Changer le hash directement via `scripts/init_db.php` ou en base SQLite.

---

## Sécurité

| Mécanisme | Détail |
|---|---|
| CSRF | Token `random_bytes(32)`, comparaison en temps constant |
| Régénération de session | `session_regenerate_id()` à chaque connexion |
| Mots de passe | `password_hash()` / `password_verify()` (algorithme agile) |
| Requêtes SQL | Requêtes préparées systématiques (0 concaténation) |
| XSS | `htmlspecialchars()` sur toutes les sorties via `View::e()` |
| Nom de fichier stocké | Aléatoire — le nom original n'est jamais exposé sur le disque |
| MIME type | Vérifié via `finfo` (indépendant de l'extension déclarée) |
| Extension | Liste blanche stricte, comparaison insensible à la casse |
| Taille | Double limite : `MAX_UPLOAD_SIZE` (`.env`) + `upload_max_filesize` (PHP) |
| Fichier temporaire | Vérifié avec `is_uploaded_file()` avant déplacement |

---

## Structure du projet

```text
KT-Drop/
├── config/
│   └── bootstrap.php              # Initialisation de l'application
├── database/
│   └── app.sqlite                 # Base de données (ignorée par git)
├── public/
│   ├── index.php                  # Front controller
│   └── assets/
│       ├── css/app.css
│       ├── js/app.js
│       └── img/
├── scripts/
│   └── init_db.php                # Initialisation du schéma et compte admin
├── src/
│   ├── Config/Config.php          # Accès aux variables d'environnement
│   ├── Controller/
│   │   ├── AuthController.php
│   │   └── FileController.php
│   ├── Core/
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Database.php
│   │   ├── Flash.php
│   │   ├── Response.php
│   │   ├── Router.php
│   │   └── View.php
│   ├── Repository/
│   │   ├── FileRepository.php
│   │   └── UserRepository.php
│   └── Service/
│       └── FileStorageService.php
├── storage/
│   ├── files/                     # Fichiers uploadés (ignoré par git)
│   └── tmp/
├── templates/
│   ├── layout.php
│   ├── auth/login.php
│   └── file/
│       ├── dashboard.php
│       └── simple-list.php
├── .env.example
├── composer.json
└── .htaccess
```

---

## Pistes d'évolution

- Partage public de fichiers via lien avec token à durée de vie limitée
- Gestion de quotas par utilisateur
- Suppression automatique des fichiers après expiration
- Gestion multi-utilisateurs avec rôles (éditeur / super-admin)
- Notification email à l'admin lors d'un nouvel upload
- Export CSV du journal des fichiers
