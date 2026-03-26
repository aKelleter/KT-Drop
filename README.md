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
- **Upload découpé en chunks** pour les fichiers volumineux (> 5 Mo) — voir section dédiée
- Téléchargement sécurisé des fichiers enregistrés
- Prévisualisation intégrée : images, PDF, fichiers texte (TXT, CSV, JSON…)
- Suppression avec protection CSRF (supprime aussi les partages associés)
- **Modification du nom et de la catégorie** d'un fichier directement depuis le dashboard
- Recherche par nom de fichier, extension ou utilisateur
- **Filtrage par catégorie** (badges cliquables + select) sur le dashboard et la vue liste
- Pagination configurable
- Popover des extensions autorisées dans la zone d'upload (pills visuelles)
- Vue liste simplifiée (tous les fichiers sans pagination) avec filtres catégorie, icônes d'aperçu et de téléchargement
- **Bouton "remonter en haut"** flottant sur toutes les pages (apparaît après 200 px de défilement)

### Catégories
- Création de catégories avec nom et couleur personnalisable
- Association d'une catégorie à un fichier à l'upload ou en modification ultérieure
- Badge coloré affiché sur chaque carte fichier et chaque ligne de la vue liste
- Filtrage par catégorie sur toutes les vues de liste
- Gestion complète depuis l'administration (création, édition, suppression)

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
- **Gestion des catégories** : création, édition (couleur + nom), suppression
- **Partages actifs** : liste globale de tous les liens non expirés avec révocation
- **Statistiques** : vue d'ensemble des fichiers, tailles, extensions, activité, répartition par catégorie et **répartition par tranche de taille**
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

# 3. Initialiser la base de données (ou mettre à jour une base existante)
php scripts/init_db.php
```

Pointer le document root d'Apache sur le dossier `public/`.

> **Important :** Modifier immédiatement les identifiants admin par défaut après l'installation (voir ci-dessous).

---

## Configuration

### Fonctionnement des fichiers d'environnement

La configuration repose sur deux fichiers complémentaires :

| Fichier | Versionné | Rôle |
|---|---|---|
| `.env` | Oui | Valeurs par défaut et variables mises à jour à chaque version (`APP_VERSION`, `APP_UPD`, etc.) |
| `.env.local` | **Non** | Surcharges propres à l'environnement (prod, staging…) — créé une seule fois sur le serveur, jamais écrasé |

Au démarrage, `.env` est chargé en premier, puis `.env.local` (s'il existe) **écrase** les variables qu'il redéfinit. Cela permet de mettre à jour `.env` librement sans jamais toucher aux paramètres de production.

### Mise en place sur le serveur de production

```bash
# À faire une seule fois, juste après le premier déploiement
cp .env.local.example .env.local
# Éditer .env.local avec les valeurs réelles
```

Contenu type de `.env.local` en production :

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.be/

DB_DATABASE=database/app.sqlite
```

> `.env.local` est ignoré par git (`.gitignore`). Il ne sera jamais écrasé lors des mises à jour.

### Variables disponibles dans `.env`

```ini
# Application
APP_NAME="KT-Drop"
APP_ENV=dev
APP_DEBUG=true
APP_URL=http://localhost/KT-Drop/

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

## Upload de gros fichiers (chunked upload)

Pour les fichiers **supérieurs à 5 Mo**, l'upload est automatiquement découpé en morceaux (*chunks*) de 2 Mo envoyés séquentiellement. Cette approche permet de contourner les limites de temps d'exécution PHP et d'afficher une progression précise même sur des fichiers volumineux.

### Protocole en 3 phases

```
1. Initialisation  POST ?action=upload_chunk_init
   → le serveur crée une session temporaire et retourne un uploadId unique

2. Envoi des chunks  POST ?action=upload_chunk  (×N)
   → chaque chunk de 2 Mo est envoyé séparément
   → la barre de progression reflète les octets réellement transmis

3. Finalisation  POST ?action=upload_chunk_finalize
   → le serveur assemble les chunks, applique toutes les validations
     (extension, MIME, taille, SHA-256), déplace le fichier dans storage/files/
     et insère l'entrée en base de données
```

### Stockage temporaire

Les chunks sont conservés dans `storage/tmp/chunks/{uploadId}/` pendant la durée de l'upload. Ce répertoire est automatiquement nettoyé :

- **à la finalisation** : suppression immédiate après assemblage réussi ou en cas d'erreur (bloc `finally`)
- **sessions expirées** : toute session de plus d'**1 heure** est purgée au prochain upload entrant (`pruneExpired()`)

### Sécurité

| Mécanisme | Détail |
|---|---|
| CSRF | Token vérifié sur chacune des 3 requêtes |
| Upload ID | Hex 32 chars (`bin2hex(random_bytes(16))`), validé par regex avant tout accès disque |
| `is_uploaded_file()` | Vérifié sur chaque chunk — impossible de rejouer un fichier arbitraire |
| Extension & MIME | Vérifiés deux fois : à l'init (fail-fast) et sur le fichier assemblé final |
| Taille | Vérifiée à l'init (taille déclarée) et sur le fichier assemblé réel |
| Path traversal | L'uploadId est strictement filtré (`/^[a-f0-9]{32}$/`) avant construction du chemin |

### Seuils configurables (dans `app.js`)

```js
const CHUNK_THRESHOLD = 5 * 1024 * 1024;  // Seuil de bascule : 5 Mo
const CHUNK_SIZE      = 2 * 1024 * 1024;  // Taille d'un chunk : 2 Mo
```

Les fichiers en dessous du seuil utilisent l'upload classique en une seule requête (comportement inchangé).

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
│   └── init_db.php                    # Schéma complet, migrations idempotentes, compte admin
├── src/
│   ├── Config/Config.php              # Accès aux variables d'environnement
│   ├── Controller/
│   │   ├── AdminController.php        # Dashboard, utilisateurs, catégories, partages, stats, paramètres
│   │   ├── AuthController.php         # Connexion / déconnexion
│   │   ├── FileController.php         # Upload, modification, téléchargement, suppression, prévisualisation
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
│   │   ├── CategoryRepository.php     # CRUD catégories
│   │   ├── FileRepository.php         # Fichiers avec filtre catégorie et mise à jour
│   │   ├── SettingsRepository.php     # Clé/valeur en base (extensions, etc.)
│   │   ├── ShareRepository.php        # Partages actifs, révocation
│   │   ├── StatsRepository.php        # Requêtes statistiques (dont stats par catégorie)
│   │   └── UserRepository.php         # CRUD utilisateurs, comptage rôles
│   └── Service/
│       ├── ChunkUploadService.php     # Gestion des uploads en chunks (init, stockage, assemblage, purge)
│       └── FileStorageService.php     # Validation, stockage, extensions (depuis DB)
├── storage/
│   ├── files/                         # Fichiers uploadés (ignoré par git)
│   ├── log/                           # Logs d'erreurs PHP en production (ignoré par git)
│   └── tmp/
├── templates/
│   ├── layout.php                     # Navbar (Fichiers / Administration / Déconnexion)
│   ├── admin/
│   │   ├── categories.php             # Gestion des catégories
│   │   ├── dashboard.php              # Accueil administration (cartes modules)
│   │   ├── settings.php               # Gestion des extensions autorisées
│   │   ├── shares.php                 # Liste globale des partages actifs
│   │   ├── stats.php                  # Statistiques (dont répartition par catégorie)
│   │   └── users.php                  # Gestion des utilisateurs
│   ├── auth/
│   │   └── login.php
│   ├── file/
│   │   ├── dashboard.php              # Vue principale avec upload et liste paginée
│   │   └── simple-list.php            # Vue légère sans pagination
│   └── share/
│       ├── access.php                 # Page publique d'accès au partage
│       └── list.php                   # Liste des partages actifs (vue utilisateur)
├── .env                               # Variables par défaut (versionné)
├── .env.local                         # Surcharges locales/prod (ignoré par git)
├── .env.local.example                 # Modèle pour créer .env.local (versionné)
├── composer.json
└── .htaccess
```

---

## Schéma de base de données

| Table | Rôle |
|---|---|
| `users` | Comptes utilisateurs (email, hash, rôle) |
| `files` | Métadonnées des fichiers uploadés (inclut `category_id` FK nullable) |
| `categories` | Catégories de fichiers (nom, couleur) |
| `shares` | Liens de partage (token, expiration, auteur) |
| `settings` | Configuration applicative clé/valeur |

---

## Vérifications au démarrage (pre-flight)

Avant tout chargement du framework, `public/index.php` effectue trois contrôles et affiche une page d'erreur explicite en cas de problème :

| Contrôle | Cause | Message affiché |
|---|---|---|
| `vendor/autoload.php` absent | `composer install` non exécuté | "Dépendances manquantes" |
| `.env.local` absent | Fichier de config non créé sur le serveur | "Fichier de configuration manquant" |
| Fichier SQLite absent | DB non initialisée ou mauvais chemin `DB_DATABASE` | "Base de données introuvable" |

Ces vérifications évitent une page blanche ou une erreur PHP brute en production.

---

## Gestion des erreurs PHP

Le comportement des erreurs PHP est piloté par la variable `APP_DEBUG` :

| `APP_DEBUG` | `display_errors` | Comportement |
|---|---|---|
| `true` | On | Erreurs affichées dans le navigateur (développement) |
| `false` | Off | Erreurs silencieuses pour l'utilisateur, loguées dans `storage/log/php_errors.log` |

Cela évite notamment que des warnings PHP ne contaminent les réponses JSON des endpoints d'upload.

---

## Pistes d'évolution

- Gestion de quotas par utilisateur
- Suppression automatique des fichiers après une date d'expiration
- Notification email lors d'un nouvel upload ou d'un accès à un partage
- Export CSV du journal des fichiers
- Configuration de la taille maximale d'upload depuis les paramètres admin
