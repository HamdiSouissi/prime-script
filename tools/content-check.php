<?php
declare(strict_types=1);

/**
 * Contrôle du contenu prêt à l'emploi : vérifie que chaque combinaison
 * secteur × étape × service affiche bien les sept blocs du parcours,
 * et que le panneau d'objections se remplit.
 *
 *   php tools/content-check.php            # tableau de synthèse
 *   php tools/content-check.php --verbose  # détail combinaison par combinaison
 *
 * Sortie : code 0 si tout est couvert, 1 sinon (utilisable en intégration continue).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Db;
use App\Models\Objection;
use App\Models\SpeechBlock;

$verbose = in_array('--verbose', $argv, true);
$required = ['presentation', 'accroche', 'decouverte', 'argumentaire', 'preuve', 'tarif', 'appel_action'];

$sectors = Db::all('SELECT id, name, slug FROM sectors WHERE is_active = 1 ORDER BY position, id');
$stages = Db::all('SELECT id, name, slug FROM stages WHERE is_active = 1 ORDER BY position, id');
$services = Db::all('SELECT id, name, slug FROM services WHERE is_active = 1 ORDER BY position, id');

$totalCells = 0;
$complete = 0;
$problems = [];
$perType = array_fill_keys($required, 0);
$objectionCounts = [];

foreach ($sectors as $sector) {
    foreach ($stages as $stage) {
        foreach ($services as $service) {
            $totalCells++;
            $selection = SpeechBlock::forContext(
                $required,
                (int) $sector['id'],
                (int) $stage['id'],
                (int) $service['id']
            );
            $missing = [];
            foreach ($required as $type) {
                $count = count($selection[$type] ?? []);
                $perType[$type] += $count;
                if ($count === 0) {
                    $missing[] = $type;
                }
            }

            $objections = Objection::forContext((int) $sector['id'], (int) $service['id']);
            $objectionCounts[] = count($objections);

            $label = sprintf('%s · %s · %s', $sector['name'], $stage['name'], $service['name']);
            if ($missing === [] && $objections !== []) {
                $complete++;
                if ($verbose) {
                    $counts = [];
                    foreach ($required as $type) {
                        $counts[] = $type . ':' . count($selection[$type]);
                    }
                    echo "OK  {$label}\n    " . implode(' ', $counts) . ' objections:' . count($objections) . "\n";
                }
                continue;
            }
            $problems[] = [
                'label'   => $label,
                'missing' => $missing,
                'objects' => count($objections),
            ];
        }
    }
}

echo "Contenu — couverture des parcours\n";
echo str_repeat('=', 72) . "\n";
echo sprintf("%-34s %s\n", 'Combinaisons secteur × étape × service', (string) $totalCells);
echo sprintf("%-34s %s\n", 'Combinaisons complètes', (string) $complete);
echo sprintf("%-34s %s\n", 'Blocs vus (7 types, toutes cases)', (string) array_sum($perType));
echo sprintf("%-34s %d\n", 'Objections par case (min / max)', $objectionCounts === [] ? 0 : min($objectionCounts));
echo sprintf("%-34s %d\n", '                            (max)', $objectionCounts === [] ? 0 : max($objectionCounts));
echo str_repeat('-', 72) . "\n";
foreach ($perType as $type => $count) {
    printf("  %-16s %5d bloc(s)\n", $type, $count);
}

if ($problems !== []) {
    echo str_repeat('-', 72) . "\n";
    echo 'Cases incomplètes :' . "\n";
    foreach ($problems as $problem) {
        printf(
            "  %s → manquant : %s (objections : %d)\n",
            $problem['label'],
            implode(', ', $problem['missing']) ?: 'aucun bloc manquant',
            $problem['objects']
        );
    }
    exit(1);
}

echo str_repeat('=', 72) . "\n";
echo "Toutes les combinaisons affichent les sept blocs et des objections.\n";
exit(0);
