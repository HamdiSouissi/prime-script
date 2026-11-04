<?php
declare(strict_types=1);

/**
 * Amorçage de l'application : autoload, configuration, session, sécurité de base.
 * Ce fichier est inclus par public/index.php (point d'entrée unique).
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// ------------------------------------------------------------------ Autoload PSR-4
// App\Core\Http → app/Core/Http.php (aucune dépendance Composer requise).
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ------------------------------------------------------------------ Environnement
date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'fr_FR.UTF-8', 'fr_FR', 'fr');

App\Core\Config::load();
date_default_timezone_set((string) App\Core\Config::get('app.timezone', 'Europe/Paris'));

$isDev = App\Core\Config::isDev();

// En production : aucune erreur PHP affichée (journalisation uniquement).
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');

// Journal local si le dossier est accessible en écriture (hébergement mutualisé).
$logDir = BASE_PATH . '/storage/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-' . date('Y-m-d') . '.log');
}

// ------------------------------------------------------------------ En-têtes HTTP
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; form-action 'self'; frame-ancestors 'self'; base-uri 'self'");
}

// ------------------------------------------------------------------ Session
App\Core\Session::start();

// ------------------------------------------------------------------ Erreurs fatales → page propre
register_shutdown_function(static function () use ($isDev): void {
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    error_log('[Prime Script] Erreur fatale : ' . $error['message'] . ' — ' . $error['file'] . ':' . $error['line']);
    if (headers_sent()) {
        return;
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    if ($isDev) {
        echo '<h1>Erreur fatale</h1><pre>' . htmlspecialchars($error['message'] . "\n" . $error['file'] . ':' . $error['line']) . '</pre>';
        return;
    }
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Erreur</title>'
        . '<div style="font-family:system-ui;max-width:32rem;margin:15vh auto;padding:0 1.5rem">'
        . '<h1 style="font-size:1.4rem">Une erreur est survenue</h1>'
        . '<p>Merci de réessayer dans quelques instants. Si le problème persiste, contactez votre administrateur.</p>'
        . '</div></html>';
});
