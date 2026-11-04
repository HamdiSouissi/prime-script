<?php
declare(strict_types=1);

/**
 * Contrôle et application des migrations de base de données.
 *
 *     php tools/migrate.php --check     n'écrit rien : signale ce qui manque
 *     php tools/migrate.php             affiche le plan puis applique
 *     php tools/migrate.php --plain     sans couleurs (terminal Windows)
 *
 * Pourquoi cet outil : une base installée avant l'ajout d'une fonctionnalité
 * n'a pas les nouvelles colonnes, d'où des erreurs 500 alors que le code est
 * correct (« Unknown column 'c.do_not_call' »). Aucun test sur une base neuve
 * ne peut révéler cet écart : il faut inspecter la base réellement installée.
 *
 * Chaque attente est décrite en PHP (colonne, index, type de colonne) et
 * comparée au schéma réel via information_schema. Les corrections sont des
 * instructions ALTER idempotentes, exécutées dans une transaction.
 *
 * Code de sortie : 0 si la base est à jour, 1 s'il manque quelque chose
 * (en mode --check) ou si l'application a échoué.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Db;

$plain = in_array('--plain', $argv ?? [], true) || getenv('NO_COLOR') !== false;
$checkOnly = in_array('--check', $argv ?? [], true);

$peindre = static fn (string $code, string $texte): string => $plain ? $texte : "\033[" . $code . 'm' . $texte . "\033[0m";
$okLabel = $peindre('32', 'OK');
$koLabel = $peindre('31', 'KO');
$titreStyle = static fn (string $texte): string => $plain ? $texte : "\033[1m" . $texte . "\033[0m";

$driver = (string) Config::get('database.driver', 'mysql');

echo "\n" . $titreStyle('Migrations de base de données') . "\n";
echo str_repeat('-', 34) . "\n";
echo 'Pilote : ' . $driver . "\n";

if ($driver !== 'mysql') {
    // La base de démonstration est générée intégralement par make-sample-db.php.
    echo "\n  " . $okLabel . "  Base SQLite : schéma créé d'un bloc, aucune migration nécessaire.\n";
    echo "  (Pour MySQL : php tools/migrate.php)\n";
    exit(0);
}

try {
    $pdo = Db::pdo();
} catch (Throwable $e) {
    echo "\n  " . $koLabel . '  Connexion impossible : ' . $e->getMessage() . "\n";
    exit(1);
}

$database = (string) Db::value('SELECT DATABASE()', [], '');
$prefix = (string) Config::get('database.prefix', '');
echo 'Base   : ' . $database . ($prefix !== '' ? ' (préfixe ' . $prefix . ')' : '') . "\n";

/** Colonnes réellement présentes : [table => [colonne => type normalisé]]. */
$colonnes = [];
$requete = $pdo->prepare(
    'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
     FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?'
);
$requete->execute([$database]);
foreach ($requete->fetchAll() as $ligne) {
    $table = str_starts_with((string) $ligne['TABLE_NAME'], $prefix)
        ? substr((string) $ligne['TABLE_NAME'], strlen($prefix))
        : (string) $ligne['TABLE_NAME'];
    $colonnes[$table][strtolower((string) $ligne['COLUMN_NAME'])] = strtolower((string) $ligne['DATA_TYPE']);
}

/** Index réellement présents. */
$index = [];
$requete = $pdo->prepare(
    'SELECT TABLE_NAME, INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ?'
);
$requete->execute([$database]);
foreach ($requete->fetchAll() as $ligne) {
    $table = str_starts_with((string) $ligne['TABLE_NAME'], $prefix)
        ? substr((string) $ligne['TABLE_NAME'], strlen($prefix))
        : (string) $ligne['TABLE_NAME'];
    $index[$table][(string) $ligne['INDEX_NAME']] = true;
}

/** Tables attendues (une installation incomplète est signalée explicitement). */
$tablesAttendues = ['users', 'sectors', 'stages', 'services', 'speech_blocks', 'speech_block_versions', 'objections', 'calls', 'login_attempts'];
$manquants = [];
foreach ($tablesAttendues as $table) {
    if (!isset($colonnes[$table])) {
        $manquants[] = 'table « ' . $table . ' » absente — importez database/schema.sql';
    }
}

/**
 * Attentes issues des migrations versionnées.
 * Chaque entrée : [table, colonne attendue|null, index attendu|null, ALTER, explication]
 */
$attentes = [
    [
        'table'   => 'calls',
        'colonne' => 'do_not_call',
        'index'   => 'idx_calls_dnc',
        'sql'     => "ALTER TABLE calls ADD COLUMN do_not_call TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = le prospect a demandé à ne plus être rappelé' AFTER callback_date, ADD KEY idx_calls_dnc (do_not_call)",
        'cause'   => '2026_11_04 — opposition au démarchage (« Ne plus rappeler »)',
    ],
    [
        'table'   => 'speech_blocks',
        'colonne' => 'code',
        'index'   => 'uq_blocks_code',
        'sql'     => "ALTER TABLE speech_blocks ADD COLUMN code VARCHAR(80) NULL DEFAULT NULL COMMENT 'identifiant du contenu livré (import rejouable)' AFTER type, ADD UNIQUE KEY uq_blocks_code (code)",
        'cause'   => '2026_11_04 — import de contenu rejouable',
    ],
    [
        'table'   => 'objections',
        'colonne' => 'code',
        'index'   => 'uq_objections_code',
        'sql'     => "ALTER TABLE objections ADD COLUMN code VARCHAR(80) NULL DEFAULT NULL COMMENT 'identifiant de l''objection livrée' AFTER id, ADD UNIQUE KEY uq_objections_code (code)",
        'cause'   => '2026_11_04 — import de contenu rejouable',
    ],
];

/** Types ENUM à contrôler : la migration élargit deux listes de valeurs. */
$enumsAttendus = [
    ['calls', 'result', ['ne_plus_rappeler'], 'ALTER TABLE calls MODIFY COLUMN result ENUM(\'rdv\',\'devis\',\'rappeler\',\'pas_interesse\',\'injoignable\',\'ne_plus_rappeler\') NOT NULL'],
    ['speech_blocks', 'type', ['barrage', 'repondeur'], "ALTER TABLE speech_blocks MODIFY COLUMN type ENUM('presentation','accroche','barrage','repondeur','decouverte','argumentaire','preuve','tarif','appel_action','conclusion','objection') NOT NULL"],
];

// ------------------------------------------------------------------ Contrôle des colonnes et index
$corrections = [];
$problemes = $manquants;

foreach ($attentes as $attente) {
    $table = $attente['table'];
    if (!isset($colonnes[$table])) {
        continue; // déjà signalé comme table absente
    }
    if (!isset($colonnes[$table][$attente['colonne']])) {
        $problemes[] = $table . '.' . $attente['colonne'] . ' absente — ' . $attente['cause'];
        $corrections[] = $attente['sql'];
    } elseif ($attente['index'] !== null && !isset($index[$table][$attente['index']])) {
        $problemes[] = $table . ' : index ' . $attente['index'] . ' absent — ' . $attente['cause'];
        $corrections[] = $attente['sql'];
    }
}

// ------------------------------------------------------------------ Contrôle des ENUM
foreach ($enumsAttendus as [$table, $colonne, $valeurs, $sql]) {
    if (!isset($colonnes[$table][$colonne])) {
        continue;
    }
    $type = (string) Db::value(
        'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$database, $prefix . $table, $colonne],
        ''
    );
    $absentes = array_values(array_filter($valeurs, static fn (string $v): bool => !str_contains($type, "'" . $v . "'")));
    if ($absentes !== []) {
        $problemes[] = $table . '.' . $colonne . ' : valeur(s) ' . implode(', ', $absentes) . ' absente(s) de l\'ENUM';
        $corrections[] = $sql;
    }
}

echo "\n";

if ($problemes === []) {
    echo '  ' . $okLabel . "  Base à jour : colonnes, index et valeurs ENUM conformes.\n";
    exit(0);
}

echo '  ' . $koLabel . '  ' . count($problemes) . " écart(s) avec le schéma attendu :\n\n";
foreach ($problemes as $i => $probleme) {
    printf("      %d. %s\n", $i + 1, $probleme);
}

if ($checkOnly) {
    echo "\n  Mode --check : aucune modification effectuée.\n";
    echo "  Pour corriger : php tools/migrate.php\n";
    exit(1);
}

// ------------------------------------------------------------------ Application
echo "\n  " . count($corrections) . " correction(s) à appliquer.\n";
echo "  Astuce : sauvegardez d'abord (mysqldump) si la base contient des données.\n\n";

$appliquees = 0;
$echecs = 0;

foreach (array_values(array_unique($corrections)) as $sql) {
    // Chaque instruction est isolée : un échec ne bloque pas les suivantes.
    $resume = preg_replace('/\s+/', ' ', $sql) ?? $sql;
    $resume = strlen($resume) > 90 ? substr($resume, 0, 87) . '…' : $resume;
    echo '  → ' . $resume . "\n";
    try {
        $pdo->exec($sql);
        $appliquees++;
        echo '    ' . $okLabel . " appliquée\n";
    } catch (Throwable $e) {
        // « Duplicate column » signifie qu'une exécution précédente a réussi.
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'Duplicate key name')) {
            echo '    ' . $okLabel . " déjà présente\n";
            continue;
        }
        $echecs++;
        echo '    ' . $koLabel . ' échec : ' . $e->getMessage() . "\n";
    }
}

echo "\n";
if ($echecs > 0) {
    echo '  ' . $koLabel . '  ' . $echecs . " instruction(s) en échec. Vérifiez les droits MySQL de l'utilisateur.\n";
    echo "  Relancez ensuite : php tools/diagnose.php\n";
    exit(1);
}

echo '  ' . $okLabel . '  ' . $appliquees . " instruction(s) appliquée(s).\n";
echo "  Vérifiez avec : php tools/diagnose.php\n";
exit(0);
