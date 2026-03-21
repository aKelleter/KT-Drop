# Dépôt de fichiers – KT-Drop

Application web de dépôt de fichiers auto-hébergée, développée en PHP natif. Elle permet aux utilisateurs authentifiés de déposer, prévisualiser, partager et télécharger des fichiers, avec une interface d'administration complète.

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
- Suppression avec protection CSRF (supprime aussi les partages associés)
- Recherche par nom de fichier, extension ou utilisateur
- Pagination configurable
- Popover des extensions autorisées dans la zone d'upload (pills visuelles)
- Vue liste simplifiée (tous les fichiers sans pagination)

### Partage public
- Génération d'un lien public signé par token (`random_bytes(32)`)
- Durée de vie configurable : 1 h, 24 h, 7 jours, 30 jours
- Page publique d'accès sans authentification
- Téléchargement direct via lien de partage
- Révocation du lien depuis le dashboard ou l'administration
- Expiration automatique (vérifiée à chaque accès)

### Administration
- Dashboard d'accueil avec cartes de navigation par module
- **Gestion des utilisateurs** : création, édition, suppression, gestion des rôles
- **Partages actifs** : liste globale de tous les liens non expirés avec révocation
- **Statistiques** : vue d'ensemble des fichiers, tailles, extensions, activité
- **Paramètres** : configuration des extensions autorisées via interface web (stockées en base)
- Toutes les actions admin protégées CSRF et réservées au rôle `admin`

---

## Rôles

| Rôle | Accès fichiers | Accès administration |
|---|---|---|
| `admin` | Complet | Oui — tous les modules |
| `editor` | Complet | Non |

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

Toute la configuration applicative se fait dans `.env` :

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

Les extensions acceptées sont gérées depuis **Administration → Paramètres** et stockées en base de données. La liste par défaut (initialisée au premier `init_db.php`) est :

```
pdf, txt, md, zip, rar, 7z
jpg, jpeg, png, gif, webp
doc, docx, xls, xlsx, ppt, pptx
csv, mp3, mp4, psd
```

En cas d'absence d'entrée en base, `FileStorageService` revient automatiquement sur cette liste par défaut.

---

## Compte admin par défaut

```
Email        : admin@kt-drop.local
Mot de passe : admin1234
```

> **À modifier impérativement avant toute mise en production.**
> Changer le mot de passe via Administration → Utilisateurs ou directement en base SQLite.

---

## Sécurité

### Authentification & sessions
| Mécanisme | Détail |
|---|---|
| CSRF | Token `random_bytes(32)`, comparaison en temps constant (`hash_equals`) |
| Régénération de session | `session_regenerate_id()` à chaque connexion |
| Mots de passe | `password_hash()` / `password_verify()` (algorithme agile) |
| Contrôle de rôle | `Auth::isAdmin()` vérifié avant chaque action d'administration |

### Stockage & upload
| Mécanisme | Détail |
|---|---|
| Nom de fichier stocké | Aléatoire (`date_hex.ext`) — le nom original n'est jamais exposé sur le disque |
| Empreinte SHA-256 | Calculée à l'upload, conservée en base |
| MIME type | Vérifié via `finfo` (indépendant de l'extension déclarée) |
| Extension | Liste blanche configurable, comparaison insensible à la casse |
| Taille | Double limite : `MAX_UPLOAD_SIZE` (`.env`) + `upload_max_filesize` (PHP) |
| Fichier temporaire | Vérifié avec `is_uploaded_file()` avant déplacement |

### Partages publics
| Mécanisme | Détail |
|---|---|
| Token | `bin2hex(random_bytes(32))` — 64 caractères hexadécimaux |
| Expiration | Vérifiée à chaque accès (côté serveur) |
| Révocation | Suppression immédiate du token en base |
| Suppression fichier | Supprime automatiquement tous les partages associés |

### Général
| Mécanisme | Détail |
|---|---|
| Requêtes SQL | Requêtes préparées systématiques (0 concaténation) |
| XSS | `htmlspecialchars()` sur toutes les sorties via `View::e()` |

---

## Structure du projet

```text
KT-Drop/
├── config/
│   └── bootstrap.php                  # Initialisation de l'application
├── database/
│   └── app.sqlite                     # Base de données (ignorée par git)
├── public/
│   ├── index.php                      # Front controller & routing
│   └── assets/
│       ├── css/app.css
│       ├── js/app.js
│       └── img/
├── scripts/
│   └── init_db.php                    # Schéma, données par défaut, compte admin
├── src/
│   ├── Config/Config.php              # Accès aux variables d'environnement
│   ├── Controller/
│   │   ├── AdminController.php        # Dashboard, utilisateurs, partages, stats, paramètres
│   │   ├── AuthController.php         # Connexion / déconnexion
│   │   ├── FileController.php         # Upload, téléchargement, suppression, prévisualisation
│   │   └── ShareController.php        # Création, révocation, accès public
│   ├── Core/
│   │   ├── Auth.php                   # Session, rôles (isAdmin)
│   │   ├── Csrf.php
│   │   ├── Database.php               # Singleton PDO SQLite
│   │   ├── Flash.php
│   │   ├── Response.php
│   │   ├── Router.php
│   │   └── View.php                   # Rendu, échappement, utilitaires fichiers
│   ├── Repository/
│   │   ├── FileRepository.php
│   │   ├── SettingsRepository.php     # Clé/valeur en base (extensions, etc.)
│   │   ├── ShareRepository.php        # Partages actifs, révocation
│   │   ├── StatsRepository.php        # Requêtes statistiques
│   │   └── UserRepository.php         # CRUD utilisateurs, comptage rôles
│   └── Service/
│       └── FileStorageService.php     # Validation, stockage, extensions (depuis DB)
├── storage/
│   ├── files/                         # Fichiers uploadés (ignoré par git)
│   └── tmp/
├── templates/
│   ├── layout.php                     # Navbar (Fichiers / Administration / Déconnexion)
│   ├── admin/
│   │   ├── dashboard.php              # Accueil administration (cartes modules)
│   │   ├── settings.php               # Gestion des extensions autorisées
│   │   ├── shares.php                 # Liste globale des partages actifs
│   │   ├── stats.php                  # Statistiques
│   │   └── users.php                  # Gestion des utilisateurs
│   ├── auth/
│   │   └── login.php
│   ├── file/
│   │   ├── dashboard.php              # Vue principale avec upload et liste paginée
│   │   └── simple-list.php            # Vue légère sans pagination
│   └── share/
│       ├── access.php                 # Page publique d'accès au partage
│       └── list.php                   # Liste des partages actifs (vue utilisateur)
├── .env.example
├── composer.json
└── .htaccess
```

---

## Schéma de base de données

| Table | Rôle |
|---|---|
| `users` | Comptes utilisateurs (email, hash, rôle) |
| `files` | Métadonnées des fichiers uploadés |
| `shares` | Liens de partage (token, expiration, auteur) |
| `settings` | Configuration applicative clé/valeur |

---

## Pistes d'évolution

- Gestion de quotas par utilisateur
- Suppression automatique des fichiers après une date d'expiration
- Notification email lors d'un nouvel upload ou d'un accès à un partage
- Export CSV du journal des fichiers
- Configuration de la taille maximale d'upload depuis les paramètres admin
