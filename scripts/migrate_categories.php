<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Config\Config;

$dbFile = Config::path((string) Config::get('DB_DATABASE', 'database/app.sqlite'));
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create categories table
$pdo->exec("
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    color TEXT NOT NULL DEFAULT '#6c757d',
    created_at TEXT NOT NULL
);
");
echo "Table categories créée (ou déjà existante).\n";

// Add category_id column to files if missing
$cols = array_column(
    $pdo->query("PRAGMA table_info(files)")->fetchAll(PDO::FETCH_ASSOC),
    'name'
);

if (!in_array('category_id', $cols, true)) {
    $pdo->exec("ALTER TABLE files ADD COLUMN category_id INTEGER REFERENCES categories(id)");
    echo "Colonne category_id ajoutée à la table files.\n";
} else {
    echo "Colonne category_id déjà présente.\n";
}

echo "Migration terminée.\n";
