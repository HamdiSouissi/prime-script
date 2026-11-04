<?php
declare(strict_types=1);

/**
 * Diagnostic d'installation — à lancer sur la machine où l'erreur apparaît :
 *
 *     php tools/diagnose.php
 *
 * Le script vérifie, sans passer par le navigateur :
 *   1. l'environnement PHP (version, extensions indispensables) ;
 *   2. le fichier de configuration (base, environnement, URL) ;
 *   3. la connexion à la base, les tables et leur contenu ;
 *   4. chaque requête utilisée par le tableau de bord ET par « Mes appels » ;
 *   5. le rendu complet de ces écrans, comme le ferait un navigateur ;
 *   6. les dernières lignes de storage/logs/.
 *
 * Aucune donnée n'est modifiée et aucun mot de passe n'est affiché.
 * Code de sortie : 0 si tout est correct, 1 en cas de problème détecté.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Config;
use App\Core\Db;
use App\Models\Call;
use App\Models\Stats;

/** Les codes ANSI sont ignorés par la plupart des terminaux Windows : --plain les omet. */
$plain = in_array('--plain', $argv ?? [], true) || getenv('NO_COLOR') !== false;
$c = static fn (string $code): string => $plain ? '' : $code;
$Green  = $c("\033[32m");
$Red    = $c("\033[31m");
$Yellow = $c("\033[33m");
$Bold   = $c("\033[1m");
$Off    = $c("\033[0m");

/** @var string[] */
$problems = [];

function titre(string $text): void
{
    global $Bold, $Off;
    echo "\n" . $Bold . $text . $Off . "\n" . str_repeat('-', max(20, strlen($text))) . "\n";
}

function ok(string $label, string $detail = ''): void
{
    global $Green, $Off;
    printf("  %sOK%s  %s%s\n", $Green, $Off, $label, $detail !== '' ? ' — ' . $detail : '');
}

function ko(string $label, string $detail = ''): void
{
    global $Red, $Off, $problems;
    $problems[] = $label . ($detail !== '' ? ' : ' . $detail : '');
    printf("  %sKO%s  %s%s\n", $Red, $Off, $label, $detail !== '' ? ' — ' . $detail : '');
}

function note(string $label, string $detail = ''): void
{
    global $Yellow, $Off;
    printf("  %s!%s   %s%s\n", $Yellow, $Off, $label, $detail !== '' ? ' — ' . $detail : '');
}

/** Exécute une action ; en cas d'échec, affiche l'erreur exacte et son emplacement. */
function essai(string $label, callable $action): void
{
    try {
        $action();
        ok($label);
    } catch (Throwable $e) {
        ko($label, get_class($e) . ' : ' . $e->getMessage());
        printf("        fichier  : %s ligne %d\n", $e->getFile(), $e->getLine());
        if ($e instanceof PDOException) {
            $sqlState = (string) $e->getCode();
            printf("        SQLSTATE : %s\n", $sqlState !== '' ? $sqlState : '(vide)');
            $driverMessage = $e->errorInfo[2] ?? '';
            if (is_string($driverMessage) && $driverMessage !== '') {
                printf("        pilote   : %s\n", $driverMessage);
            }
        }
        foreach (array_slice($e->getTrace(), 0, 4) as $frame) {
            printf("        appel    : %s%s() ligne %s\n",
                isset($frame['class']) ? $frame['class'] . '::' : '',
                $frame['function'] ?? '?',
                $frame['line'] ?? '?');
        }
    }
}

echo $Bold . "Diagnostic Prime Script" . $Off . "\n";
echo "Dossier : " . BASE_PATH . "\n";
echo "Date    : " . date('d/m/Y H:i:s') . "\n";
echo "PHP     : " . PHP_VERSION . " (" . PHP_SAPI . ")\n";

// ------------------------------------------------------------------ 1. Environnement
titre('1. Environnement PHP');

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    ko('version PHP', PHP_VERSION . ' — PHP 8.1 minimum requis');
} else {
    ok('version PHP', PHP_VERSION);
}

foreach (['pdo', 'json', 'session', 'mbstring'] as $extension) {
    extension_loaded($extension)
        ? ok('extension ' . $extension)
        : ($extension === 'mbstring'
            ? note('extension mbstring absente', 'recommandée pour les accents')
            : ko('extension ' . $extension . ' absente', 'obligatoire'));
}

// ------------------------------------------------------------------ 2. Configuration
titre('2. Configuration');

if (!is_file(BASE_PATH . '/config/config.php')) {
    ko('config/config.php introuvable', 'copiez config/config.example.php vers config/config.php');
} else {
    ok('config/config.php présent');
}

$driver = (string) Config::get('database.driver', 'mysql');
ok('app.env', (string) Config::get('app.env', 'production'));
ok('database.driver', $driver);
$baseUrl = (string) Config::get('app.base_url', '');
ok('app.base_url', $baseUrl !== '' ? $baseUrl : '(vide : détection automatique)');

if (!in_array($driver, ['mysql', 'sqlite'], true)) {
    ko('pilote de base inconnu', $driver . ' — attendu mysql ou sqlite');
}

if ($driver === 'sqlite') {
    $sqlitePath = (string) Config::get('database.sqlite_path', BASE_PATH . '/storage/prime-script.sqlite');
    if (!is_file($sqlitePath)) {
        ko('fichier SQLite introuvable', $sqlitePath . ' — lancez php tools/make-sample-db.php');
    } elseif (!is_readable($sqlitePath)) {
        ko('fichier SQLite illisible', $sqlitePath);
    } else {
        ok('fichier SQLite', $sqlitePath . ' (' . number_format((int) filesize($sqlitePath)) . ' octets)');
    }
    if ((string) Config::get('app.env', 'production') === 'production') {
        note('app.env vaut « production » avec SQLite', 'mode démonstration seulement');
    }
} else {
    ok('hôte / base', (string) Config::get('database.host', '') . ' / ' . (string) Config::get('database.name', ''));
    ok('utilisateur', (string) Config::get('database.user', ''));
}

$sessionKey = (string) Config::get('session.key', '');
if (strlen($sessionKey) < 64) {
    ko('session.key trop courte', strlen($sessionKey) . ' caractères (64 minimum)');
} else {
    ok('session.key', strlen($sessionKey) . ' caractères');
}

// ------------------------------------------------------------------ 3. Base de données
titre('3. Base de données');

$connected = false;
try {
    $pdo = Db::pdo();
    $connected = true;
    ok('connexion établie', 'pilote ' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
} catch (Throwable $e) {
    ko('connexion impossible', $e->getMessage());
    if (str_contains($e->getMessage(), 'could not find driver')) {
        note('extension PDO manquante', 'activez pdo_mysql (ou pdo_sqlite) dans php.ini');
    }
    if (str_contains($e->getMessage(), 'Access denied')) {
        note('identifiants refusés', 'vérifiez database.user et database.password');
    }
    if (str_contains($e->getMessage(), 'Unknown database')) {
        note('base absente', 'importez database/schema.sql puis database/content.sql');
    }
}

if ($connected && $driver === 'mysql') {
    // ONLY_FULL_GROUP_BY (actif par défaut depuis MySQL 5.7) fait échouer les
    // requêtes groupées dont l'ORDER BY contient une colonne non agrégée.
    $modes = (string) Db::value('SELECT @@SESSION.sql_mode', [], '');
    if (str_contains($modes, 'ONLY_FULL_GROUP_BY')) {
        note('sql_mode contient ONLY_FULL_GROUP_BY', 'les ORDER BY de requêtes groupées doivent être agrégés');
    } else {
        ok('sql_mode', $modes !== '' ? $modes : '(vide)');
    }
}

if ($connected) {
    // Colonnes que les migrations versionnées ajoutent. Une base installée avant
    // elles les ignore, et le code échoue alors en « Unknown column » (1054) :
    // on le signale ici, avec la commande de correction, avant toute requête.
    $attentes = [
        'calls.do_not_call'        => '2026_11_04 — opposition au démarchage',
        'speech_blocks.code'       => '2026_11_04 — import de contenu rejouable',
        'objections.code'          => '2026_11_04 — import de contenu rejouable',
    ];
    /**
     * Liste les colonnes réelles d'une table, quel que soit le moteur :
     * information_schema pour MySQL, PRAGMA pour SQLite.
     *
     * @return array<int, string>|null null si la table est illisible
     */
    $colonnesDe = static function (string $table) use ($driver): ?array {
        $reelle = Db::table($table);
        try {
            if ($driver === 'sqlite') {
                $lignes = Db::all('PRAGMA table_info(' . $reelle . ')');
                return array_map(static fn (array $l): string => strtolower((string) $l['name']), $lignes);
            }
            $lignes = Db::all(
                'SELECT LOWER(COLUMN_NAME) AS nom FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$reelle]
            );
            return array_map(static fn (array $l): string => (string) $l['nom'], $lignes);
        } catch (Throwable $e) {
            note('lecture du schéma de ' . $reelle . ' impossible', $e->getMessage());
            return null;
        }
    };

    $retard = [];
    $verifie = 0;
    $cacheColonnes = [];
    foreach ($attentes as $cible => $cause) {
        [$table, $nomColonne] = explode('.', $cible);
        if (!array_key_exists($table, $cacheColonnes)) {
            $cacheColonnes[$table] = $colonnesDe($table);
        }
        $liste = $cacheColonnes[$table];
        if ($liste === null) {
            continue; // table absente ou illisible : déjà signalé
        }
        $verifie++;
        if (!in_array(strtolower($nomColonne), $liste, true)) {
            $retard[] = $cible . ' (' . $cause . ')';
        }
    }
    if ($retard !== []) {
        ko('schéma en retard sur le code', count($retard) . ' colonne(s) manquante(s)');
        foreach ($retard as $item) {
            echo '        · ' . $item . "\n";
        }
        echo "        → corriger avec : php tools/migrate.php\n";
    } elseif ($verifie > 0) {
        ok('schéma à jour', $verifie . ' colonne(s) de migration vérifiée(s)');
    } else {
        note('contrôle du schéma non concluant', 'tables illisibles');
    }
}

if ($connected) {
    $tables = ['users', 'sectors', 'stages', 'services', 'speech_blocks', 'speech_block_versions', 'objections', 'calls', 'login_attempts'];
    foreach ($tables as $table) {
        $real = Db::table($table);
        try {
            $count = (int) Db::value('SELECT COUNT(*) FROM ' . $real);
            ok(sprintf('table %s', str_pad($real, 24)), $count . ' ligne(s)');
        } catch (Throwable $e) {
            ko(sprintf('table %s', str_pad($real, 24)), $e->getMessage());
        }
    }
}

// ------------------------------------------------------------------ 4. Requêtes des écrans en erreur
titre('4. Requêtes du tableau de bord et de « Mes appels »');

$userId = 0;
if ($connected) {
    try {
        $userId = (int) Db::value("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1", [], 0);
        if ($userId === 0) {
            $userId = (int) Db::value('SELECT id FROM users ORDER BY id LIMIT 1', [], 0);
        }
        $userId > 0
            ? ok('utilisateur de test', 'id ' . $userId)
            : ko('aucun utilisateur en base', 'importez database/seed.sql');
    } catch (Throwable $e) {
        ko('lecture de la table users', $e->getMessage());
    }

    essai('Stats::counters()', static fn () => Stats::counters());
    essai('Stats::byResult()', static fn () => Stats::byResult(14));
    essai('Stats::byUser()', static fn () => Stats::byUser(14));
    essai('Stats::byService()', static fn () => Stats::byService(14));
    essai('Stats::bySector()', static fn () => Stats::bySector(14));
    essai('Stats::lastSevenDays()', static fn () => Stats::lastSevenDays());
    essai('Stats::contentCounters()', static fn () => Stats::contentCounters());
    essai('Stats::blocksByType()', static fn () => Stats::blocksByType());
    essai('Call::search() [tableau de bord]', static fn () => Call::search([], 8));
    essai('Call::dueCallbacks() [tableau de bord]', static fn () => Call::dueCallbacks(null, 5));

    if ($userId > 0) {
        essai('Call::statsForUser() [mes appels]', static fn () => Call::statsForUser($userId));
        essai('Call::dueCallbacks(user) [mes appels]', static fn () => Call::dueCallbacks($userId, 6));
        essai('Call::search(user) [mes appels]', static fn () => Call::search(['user_id' => $userId], 25));
        essai('Call::countSearch(user) [mes appels]', static fn () => Call::countSearch(['user_id' => $userId]));
    }
}

// ------------------------------------------------------------------ 5. Rendu des écrans
titre('5. Rendu complet des écrans (comme dans le navigateur)');

/**
 * Simule une session connectée et exécute l'action d'un contrôleur.
 */
function rendre(string $controller, string $action, string $path, array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
    } else {
        @session_start();
    }
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_role'] = (string) $user['role'];
    $_SESSION['user_name'] = (string) $user['name'];
    $_SESSION['_fingerprint'] = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . (string) Config::get('session.key'));

    $_SERVER['REQUEST_URI']    = $path;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME']    = '/index.php';
    $_SERVER['HTTP_ACCEPT']    = 'text/html';
    if (!isset($_SERVER['HTTP_HOST'])) {
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    ob_start();
    try {
        $instance = App::make($controller);
        $instance->{$action}();
        $html = (string) ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        ko($path, get_class($e) . ' : ' . $e->getMessage());
        printf("        fichier  : %s ligne %d\n", $e->getFile(), $e->getLine());
        return;
    }

    if (str_contains($html, 'Fatal error') || str_contains($html, 'Erreur applicative')) {
        ko($path, 'la page contient une erreur PHP');
        if (preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $m) === 1) {
            printf("        titre    : %s\n", trim(strip_tags($m[1])));
        }
        if (preg_match('#<p><strong>(.*?)</strong></p>#s', $html, $m) === 1) {
            printf("        message  : %s\n", html_entity_decode(strip_tags($m[1])));
        }
        return;
    }

    ok($path, strlen($html) . ' octets rendus');
}

$commercialId = 0;
$commercialName = 'Diagnostic';
if ($connected && $userId > 0) {
    try {
        $row = Db::one("SELECT id, name FROM users WHERE role = 'commercial' ORDER BY id LIMIT 1");
        if (is_array($row)) {
            $commercialId = (int) $row['id'];
            $commercialName = (string) $row['name'];
        }
    } catch (Throwable) {
        // Sans commercial, on teste « Mes appels » avec le compte admin.
    }

    $adminUser = ['id' => $userId, 'role' => 'admin', 'name' => 'Diagnostic'];
    $commercialUser = $commercialId > 0
        ? ['id' => $commercialId, 'role' => 'commercial', 'name' => $commercialName]
        : $adminUser;

    rendre('Admin\\DashboardController', 'index', '/admin', $adminUser);
    rendre('HistoryController', 'index', '/historique', $commercialUser);
}

// ------------------------------------------------------------------ 6. Journaux
titre('6. Journaux');

$logDir = BASE_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    note('storage/logs absent', "les erreurs vont dans le journal du serveur web (Apache/Nginx/XAMPP)");
} else {
    $logs = glob($logDir . '/*.log') ?: [];
    if ($logs === []) {
        note('aucun fichier .log', $logDir);
    }
    foreach ($logs as $log) {
        $lines = @file($log) ?: [];
        printf("  %s %s (%d lignes)\n", $Yellow . '!' . $Off, basename($log), count($lines));
        foreach (array_slice($lines, -5) as $line) {
            echo '        ' . rtrim($line) . "\n";
        }
    }
}

// ------------------------------------------------------------------ Synthèse
titre('Synthèse');

if ($problems === []) {
    echo '  ' . $Green . 'OK' . $Off . "  Aucun problème détecté.\n";
    echo "  Si l'erreur persiste dans le navigateur, le détail se trouve dans le journal\n";
    echo "  du serveur web (Apache error.log, Nginx error.log, XAMPP/MAMP : onglet Logs).\n";
    exit(0);
}

echo '  ' . $Red . 'KO' . $Off . '  ' . count($problems) . " problème(s) :\n";
foreach ($problems as $index => $problem) {
    printf("      %d. %s\n", $index + 1, $problem);
}
echo "\nCopiez cette sortie pour obtenir la correction.\n";
exit(1);
