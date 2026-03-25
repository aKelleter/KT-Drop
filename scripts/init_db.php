<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Config\Config;

$dbFile = Config::path((string) Config::get('DB_DATABASE', 'database/app.sqlite'));

if (!is_dir(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0775, true);
}

$pdo = new \PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    created_at TEXT NOT NULL
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    extension TEXT,
    size_bytes INTEGER NOT NULL,
    sha256 TEXT NOT NULL,
    storage_path TEXT NOT NULL,
    uploaded_by INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(uploaded_by) REFERENCES users(id)
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS shares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    file_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    created_by INTEGER NOT NULL,
    FOREIGN KEY(file_id) REFERENCES files(id),
    FOREIGN KEY(created_by) REFERENCES users(id)
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL UNIQUE,
    color      TEXT NOT NULL DEFAULT '#6c757d',
    created_at TEXT NOT NULL
);
");

// Ajout de category_id dans files si absent (idempotent)
$cols = array_column(
    $pdo->query("PRAGMA table_info(files)")->fetchAll(PDO::FETCH_ASSOC),
    'name'
);
if (!in_array('category_id', $cols, true)) {
    $pdo->exec("ALTER TABLE files ADD COLUMN category_id INTEGER REFERENCES categories(id)");
}

$defaultExtensions = 'pdf,txt,md,zip,rar,7z,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx,csv,mp3,mp4,psd';
$stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('allowed_extensions', :value)");
$stmt->execute(['value' => $defaultExtensions]);

$count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

if ($count === 0) {
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, role, created_at)
        VALUES (:email, :password_hash, :role, :created_at)
    ");

    $stmt->execute([
        'email' => 'admin@kt-drop.local',
        'password_hash' => password_hash('admin1234', PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

echo "Base initialisée.\n";
echo "Compte admin : admin@kt-drop.local / admin1234\n";