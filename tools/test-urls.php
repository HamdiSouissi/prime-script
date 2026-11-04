<?php
declare(strict_types=1);

/**
 * Vérifie la détection du chemin d'installation (Http::path / Http::baseUrl).
 *
 * Pourquoi ce test : `Http` déduit le sous-dossier d'installation de `SCRIPT_NAME`.
 * Certaines passerelles (serveur intégré `php -S`, plusieurs configurations FastCGI)
 * y placent le chemin *demandé* et non le script exécuté. Une détection naïve tronquait
 * alors la route (« /appel/presentation » devenait « /presentation » : page introuvable)
 * et préfixait tous les liens par le chemin courant (« /appel/historique », « /appel/assets/… »).
 * Ce test fige les cas de déploiement à ne jamais casser.
 *
 * Usage : php tools/test-urls.php   (code de sortie 0 = tout passe)
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Http;

/**
 * Cas : [description, SCRIPT_NAME, REQUEST_URI, app.base_url, chemin attendu, URL de base attendue].
 * REQUEST_URI reproduit ce que le serveur web transmet réellement (URL publique, préfixe compris).
 */
$cases = [
    ['Racine du domaine, Apache + rewrite', '/index.php', '/login', '',
        '/login', 'http://exemple.test'],
    ['Racine du domaine, serveur intégré (SCRIPT_NAME = chemin demandé)', '/appel/presentation', '/appel/presentation', '',
        '/appel/presentation', 'http://exemple.test'],
    ['Racine du domaine, slash seul', '/', '/', '',
        '/', 'http://exemple.test'],
    ['Racine, document root au-dessus de public/ (repli .htaccess)', '/public/index.php', '/appel/speech', '',
        '/appel/speech', 'http://exemple.test'],
    ['Sous-dossier, Apache + rewrite', '/prime-script/index.php', '/prime-script/admin/blocs', '',
        '/admin/blocs', 'http://exemple.test/prime-script'],
    ['Sous-dossier, document root au-dessus de public/', '/prime-script/public/index.php', '/prime-script/admin', '',
        '/admin', 'http://exemple.test/prime-script'],
    ['Sous-dossier imposé par la configuration', '/peu-importe.php', '/prime-script/guide', 'https://exemple.test/prime-script',
        '/guide', 'https://exemple.test/prime-script'],
    ['Configuration sans sous-dossier (slash final toléré)', '/index.php', '/historique', 'https://exemple.test/',
        '/historique', 'https://exemple.test'],
];

$echecs = 0;

foreach ($cases as [$label, $scriptName, $uri, $configured, $expectedPath, $expectedBase]) {
    $_SERVER['SCRIPT_NAME']      = $scriptName;
    $_SERVER['REQUEST_URI']      = $uri;
    $_SERVER['HTTP_HOST']        = 'exemple.test';
    $_SERVER['HTTPS']            = 'off';
    $_SERVER['SERVER_PORT']      = '80';
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = '';

    Config::load(['app' => ['base_url' => $configured], 'test' => true]);
    (new ReflectionProperty(Http::class, 'baseUrl'))->setValue(null, null);

    $path = Http::path();
    $base = Http::baseUrl();

    $pathOk = $path === $expectedPath;
    $baseOk = $base === $expectedBase;

    // Les liens dérivés doivent rester justes : c'est ce que voyaient les utilisateurs.
    $url = Http::url('/historique');
    $urlOk = $url === $expectedBase . '/historique';

    if ($pathOk && $baseOk && $urlOk) {
        printf("  \033[32m✓\033[0m %s\n", $label);
        continue;
    }

    $echecs++;
    printf("  \033[31m✗\033[0m %s\n", $label);
    if (!$pathOk) {
        printf("      chemin   : %s (attendu %s)\n", $path, $expectedPath);
    }
    if (!$baseOk) {
        printf("      base     : %s (attendu %s)\n", $base, $expectedBase);
    }
    if (!$urlOk) {
        printf("      lien     : %s (attendu %s/historique)\n", $url, $expectedBase);
    }
}

echo "\n";
if ($echecs === 0) {
    printf("\033[32mDétection des URL : %d cas vérifiés, tout passe.\033[0m\n", count($cases));
    exit(0);
}

printf("\033[31mDétection des URL : %d échec(s) sur %d cas.\033[0m\n", $echecs, count($cases));
exit(1);
