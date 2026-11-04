<?php
declare(strict_types=1);

/**
 * Génère database/content.sql (import MySQL « une seule commande »)
 * à partir des fichiers database/content/*.sql (JSON, un enregistrement par ligne).
 *
 *   php tools/build-content-sql.php
 *
 * Le fichier produit est autonome : il crée la colonne de suivi `code`, met à
 * jour les énumérations, puis insère le contenu. Il est rejouable sans doublon
 * grâce à la clé unique sur `code`.
 *
 * Les fichiers JSON restent la source de vérité ; tools/import-content.php
 * les lit directement (utile en SQLite ou sans accès mysql en ligne de commande).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));

$contentDir = BASE_PATH . '/database/content';
$files = glob($contentDir . '/*.sql') ?: [];
sort($files);

$prologue = '';
$blocks = [];
$objections = [];

foreach ($files as $file) {
    $name = basename($file);
    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    if (str_starts_with($name, '00-')) {
        $prologue .= implode("\n", $lines) . "\n";
        continue;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '{') {
            continue;
        }
        $row = json_decode($line, true);
        if (!is_array($row)) {
            fwrite(STDERR, "JSON illisible dans {$name} : " . mb_substr($line, 0, 80) . "…\n");
            exit(1);
        }
        if (isset($row['question'])) {
            $objections[] = $row;
        } else {
            $blocks[] = $row;
        }
    }
}

/** Échappement d'une chaîne pour MySQL (sans connexion). */
$quote = static function (string $value): string {
    return "'" . str_replace(
        ["\\", "\0", "\n", "\r", "'", '"', "\x1a"],
        ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"],
        $value
    ) . "'";
};

/** Référence d'un lien : NULL ou sous-requête sur le slug. */
$ref = static function (string $table, ?string $slug) use ($quote): string {
    if ($slug === null || $slug === '') {
        return 'NULL';
    }
    return sprintf('(SELECT id FROM %s WHERE slug = %s)', $table, $quote($slug));
};

$out = [];
$out[] = '-- =====================================================================';
$out[] = '--  Prime Script — Contenu prêt à l\'emploi';
$out[] = '--  FICHIER GÉNÉRÉ — ne pas modifier à la main.';
$out[] = '--  Source : database/content/*.sql   Générateur : tools/build-content-sql.php';
$out[] = '--';
$out[] = '--  Import (MySQL) :';
$out[] = '--      mysql -u prime_script -p prime_script < database/content.sql';
$out[] = '--';
$out[] = '--  Rejouable : chaque élément porte un `code` unique et est mis à jour,';
$out[] = '--  jamais dupliqué. Les comptes et le journal d\'appels ne sont pas touchés.';
$out[] = '-- =====================================================================';
$out[] = '';
$out[] = $prologue;

$out[] = "-- ---------------------------------------------------------------------";
$out[] = '--  Blocs de speech';
$out[] = "-- ---------------------------------------------------------------------";

$positionByType = [];
foreach ($blocks as $block) {
    $type = (string) $block['type'];
    $positionByType[$type] = ($positionByType[$type] ?? 0) + 10;
    $position = isset($block['position']) ? (int) $block['position'] : $positionByType[$type];

    $out[] = sprintf(
        'INSERT INTO speech_blocks (code, type, sector_id, stage_id, service_id, title, content, position, is_active)'
        . ' VALUES (%s, %s, %s, %s, %s, %s, %s, %d, %d)'
        . ' ON DUPLICATE KEY UPDATE type = VALUES(type), sector_id = VALUES(sector_id), stage_id = VALUES(stage_id),'
        . ' service_id = VALUES(service_id), title = VALUES(title), content = VALUES(content),'
        . ' position = VALUES(position), is_active = VALUES(is_active);',
        $quote((string) $block['code']),
        $quote($type),
        $ref('sectors', $block['sector'] ?? null),
        $ref('stages', $block['stage'] ?? null),
        $ref('services', $block['service'] ?? null),
        $quote((string) $block['title']),
        $quote((string) $block['content']),
        $position,
        (int) ($block['active'] ?? 1)
    );
}

$out[] = '';
$out[] = "-- ---------------------------------------------------------------------";
$out[] = '--  Objections';
$out[] = "-- ---------------------------------------------------------------------";
foreach ($objections as $index => $objection) {
    $code = 'obj-' . preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(
        (string) preg_replace('/[^a-z0-9]+/i', '-', (string) $objection['question'])
    ));
    $code = trim((string) $code, '-');
    if (mb_strlen($code) > 78) {
        $code = mb_substr($code, 0, 78);
    }
    $out[] = sprintf(
        'INSERT INTO objections (code, question, answer, service_id, sector_id, position, is_active)'
        . ' VALUES (%s, %s, %s, %s, %s, %d, %d)'
        . ' ON DUPLICATE KEY UPDATE question = VALUES(question), answer = VALUES(answer),'
        . ' service_id = VALUES(service_id), sector_id = VALUES(sector_id),'
        . ' position = VALUES(position), is_active = VALUES(is_active);',
        $quote($code),
        $quote((string) $objection['question']),
        $quote((string) $objection['answer']),
        $ref('services', $objection['service'] ?? null),
        $ref('sectors', $objection['sector'] ?? null),
        (int) ($objection['position'] ?? ($index + 1) * 10),
        (int) ($objection['active'] ?? 1)
    );
}

$out[] = '';
$out[] = '-- Bilan : ' . count($blocks) . ' blocs, ' . count($objections) . ' objections.';

$target = BASE_PATH . '/database/content.sql';
file_put_contents($target, implode("\n", $out) . "\n");

echo 'Écrit : database/content.sql' . "\n";
echo '  ' . count($blocks) . ' blocs, ' . count($objections) . ' objections' . "\n";
