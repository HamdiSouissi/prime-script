<?php
declare(strict_types=1);

/**
 * Import du contenu prêt à l'emploi dans la base configurée.
 *
 *   php tools/import-content.php               # importe (ou met à jour) le contenu
 *   php tools/import-content.php --dry-run     # vérifie les fichiers, sans écrire
 *   php tools/import-content.php --purge       # retire le contenu importé (code non vide)
 *
 * Le contenu éditorial vit dans database/content/*.sql (un enregistrement JSON
 * par ligne, lisible et versionnable). Chaque élément porte un `code` unique :
 * réimporter ne crée jamais de doublon, cela met à jour et archive la version
 * précédente dans l'historique.
 *
 * Ce qui n'est jamais touché : les comptes, le journal d'appels, les secteurs /
 * étapes / services, et les blocs créés à la main dans l'administration.
 *
 * Le même import est disponible dans l'administration :
 * Contenus → Blocs de speech → Restaurer le contenu d'origine.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Config;
use App\Services\ContentImporter;

$dryRun = in_array('--dry-run', $argv, true);
$purge = in_array('--purge', $argv, true);

echo "Prime Script — contenu prêt à l'emploi\n";
echo 'Base   : ' . Config::get('database.driver', 'sqlite') . "\n";
echo 'Source : database/content/' . "\n";
echo str_repeat('-', 62) . "\n";

try {
    $importer = new ContentImporter();
    echo 'Fichiers de contenu : ' . $importer->fileCount() . "\n";

    if ($purge) {
        if ($dryRun) {
            echo "Simulation : le contenu importé serait retiré (les créations manuelles restent).\n";
            exit(0);
        }
        $removed = $importer->purge();
        echo "Contenu importé retiré : {$removed} bloc(s) et leurs objections.\n";
        echo "Les blocs et objections créés dans l'administration sont conservés.\n";
        exit(0);
    }

    if ($dryRun) {
        $data = $importer->load();
        echo count($data['blocks']) . ' bloc(s) et ' . count($data['objections']) . " objection(s) prêts à importer.\n";
        echo "Simulation : aucune écriture effectuée.\n";
        exit(0);
    }

    $stats = $importer->import();
} catch (Throwable $e) {
    fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
    exit(1);
}

echo str_repeat('-', 62) . "\n";
echo "Blocs      : {$stats['blocks_created']} créé(s), {$stats['blocks_updated']} mis à jour, {$stats['blocks_unchanged']} inchangé(s)\n";
echo "Objections : {$stats['objections_created']} créée(s), {$stats['objections_updated']} mise(s) à jour, {$stats['objections_unchanged']} inchangée(s)\n";
echo "\nVérifiez la couverture des parcours : php tools/content-check.php\n";
