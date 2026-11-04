<?php
declare(strict_types=1);

/**
 * Outil de démonstration locale (hors production).
 *
 * Construit une base SQLite de démonstration à partir de database/seed.sql,
 * afin de pouvoir essayer Prime Script sans serveur MySQL.
 *
 * Utilisation :
 *   php tools/make-sample-db.php
 *
 * Le schéma de référence reste database/schema.sql (MySQL). Celui-ci en est
 * la traduction SQLite, strictement équivalente pour les colonnes utilisées.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Db;

if (Config::get('database.driver') !== 'sqlite') {
    fwrite(STDERR, "Ce script ne s'utilise qu'avec \"driver\" => \"sqlite\" dans config/config.php.\n");
    fwrite(STDERR, "En production, importez database/schema.sql puis database/seed.sql dans MySQL.\n");
    exit(1);
}

$sqlitePath = (string) Config::get('database.sqlite_path');
@mkdir(dirname($sqlitePath), 0775, true);
if (is_file($sqlitePath)) {
    unlink($sqlitePath);
}

$pdo = Db::pdo();

// ------------------------------------------------------------------ Schéma SQLite
$schema = <<<'SQL'
CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'commercial' CHECK (role IN ('commercial','admin')),
    is_active     INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT NULL,
    created_at    TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at    TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

CREATE TABLE sectors (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    slug        TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    icon        TEXT NOT NULL DEFAULT 'building',
    position    INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

CREATE TABLE stages (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    slug        TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    color       TEXT NOT NULL DEFAULT 'blue',
    position    INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

CREATE TABLE services (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    slug        TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    icon        TEXT NOT NULL DEFAULT 'globe',
    price_from  INTEGER NULL,
    position    INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

CREATE TABLE speech_blocks (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    type       TEXT NOT NULL CHECK (type IN ('presentation','accroche','barrage','repondeur','decouverte','argumentaire','preuve','tarif','appel_action','conclusion','objection')),
    code       TEXT NULL UNIQUE,
    sector_id  INTEGER NULL REFERENCES sectors (id) ON DELETE SET NULL,
    stage_id   INTEGER NULL REFERENCES stages (id) ON DELETE SET NULL,
    service_id INTEGER NULL REFERENCES services (id) ON DELETE SET NULL,
    title      TEXT NOT NULL DEFAULT '',
    content    TEXT NOT NULL,
    position   INTEGER NOT NULL DEFAULT 0,
    is_active  INTEGER NOT NULL DEFAULT 1,
    created_by INTEGER NULL REFERENCES users (id) ON DELETE SET NULL,
    updated_by INTEGER NULL REFERENCES users (id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_blocks_selection ON speech_blocks (type, is_active, position);
CREATE INDEX idx_blocks_sector ON speech_blocks (sector_id);
CREATE INDEX idx_blocks_service ON speech_blocks (service_id);

CREATE TABLE speech_block_versions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    block_id    INTEGER NOT NULL REFERENCES speech_blocks (id) ON DELETE CASCADE,
    version_no  INTEGER NOT NULL,
    type        TEXT NOT NULL,
    sector_id   INTEGER NULL,
    stage_id    INTEGER NULL,
    service_id  INTEGER NULL,
    title       TEXT NOT NULL DEFAULT '',
    content     TEXT NOT NULL,
    position    INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1,
    change_note TEXT NOT NULL DEFAULT '',
    created_by  INTEGER NULL,
    created_at  TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    UNIQUE (block_id, version_no)
);

CREATE TABLE objections (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    code       TEXT NULL UNIQUE,
    question   TEXT NOT NULL,
    answer     TEXT NOT NULL,
    service_id INTEGER NULL REFERENCES services (id) ON DELETE SET NULL,
    sector_id  INTEGER NULL REFERENCES sectors (id) ON DELETE SET NULL,
    position   INTEGER NOT NULL DEFAULT 0,
    is_active  INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

CREATE TABLE calls (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id           INTEGER NOT NULL REFERENCES users (id),
    sector_id         INTEGER NULL REFERENCES sectors (id) ON DELETE SET NULL,
    stage_id          INTEGER NULL REFERENCES stages (id) ON DELETE SET NULL,
    service_id        INTEGER NULL REFERENCES services (id) ON DELETE SET NULL,
    entreprise_client TEXT NOT NULL DEFAULT '',
    contact           TEXT NOT NULL DEFAULT '',
    result            TEXT NOT NULL CHECK (result IN ('rdv','devis','rappeler','pas_interesse','injoignable','ne_plus_rappeler')),
    note              TEXT NOT NULL DEFAULT '',
    callback_date     TEXT NULL,
    do_not_call       INTEGER NOT NULL DEFAULT 0,
    created_at        TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_calls_user_date ON calls (user_id, created_at);
CREATE INDEX idx_calls_date ON calls (created_at);

CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    throttle_key TEXT NOT NULL,
    ip           TEXT NOT NULL DEFAULT '',
    successful   INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_attempts_key_date ON login_attempts (throttle_key, created_at);
SQL;

$pdo->exec($schema);

// ------------------------------------------------------------------ Données de démonstration
$seedFile = BASE_PATH . '/database/seed.sql';
$seed = (string) file_get_contents($seedFile);

// Nettoyage des instructions MySQL non portables.
$seed = preg_replace('/^SET (NAMES|FOREIGN_KEY_CHECKS).*$/mi', '', $seed) ?? $seed;
$seed = preg_replace('/^TRUNCATE TABLE (\w+);/mi', 'DELETE FROM $1;', $seed) ?? $seed;
$seed = preg_replace('/^--.*$/m', '', $seed) ?? $seed;

$statements = array_filter(array_map('trim', explode(";\n", $seed . "\n")));
$count = 0;
foreach ($statements as $statement) {
    if ($statement === '' || $statement === ';') {
        continue;
    }
    try {
        Db::run($statement . ';');
        $count++;
    } catch (Throwable $e) {
        fwrite(STDERR, "Erreur sur l'instruction : " . substr($statement, 0, 120) . "…\n" . $e->getMessage() . "\n");
        exit(1);
    }
}

$tables = ['users', 'sectors', 'stages', 'services', 'speech_blocks', 'objections', 'calls'];
echo "Base SQLite de démonstration créée : {$sqlitePath}\n";
foreach ($tables as $table) {
    echo sprintf("  %-18s %d lignes\n", $table, (int) Db::value("SELECT COUNT(*) FROM {$table}", [], 0));
}
echo "\nComptes de démonstration :\n";
echo "  admin      admin@primevisionsolutions.fr  / AdminPrime2026!\n";
echo "  commercial hamdi@primevisionsolutions.fr  / Commercial2026!\n";
echo "  commercial leila@primevisionsolutions.fr  / Commercial2026!\n";
