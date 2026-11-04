<?php
declare(strict_types=1);

/**
 * Relecture automatique du contenu éditorial.
 *
 *   php tools/content-audit.php
 *
 * Contrôle :
 *   - expressions interdites (jargon creux, formules publicitaires) ;
 *   - variables inconnues ou mal orthographiées dans les textes ;
 *   - doublons et textes quasi identiques ;
 *   - longueur à l'oral (accroches < 20 s, argumentaires < 45 s) ;
 *   - textes vides, phrases trop longues (plus de 25 mots).
 *
 * Code de sortie 1 si au moins un point bloquant est trouvé.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Services\SpeechRenderer;

/** Expressions à bannir (comparées en minuscules, sans accents). */
$forbidden = [
    'solution innovante',
    'synergie',
    'en constante évolution',
    'je me permets',
    'n\'hésitez pas',
    'n\'hesitez pas',
    'booster',
    'révolutionner',
    'revolutionner',
    'clé en main',
    'cle en main',
    'leader du marché',
    'meilleur du marché',
    'à 100 % garanti',
    'sans risque garanti',
    'lorem ipsum',
    'texte à compléter',
    'à compléter ici',
    'texte exemple',
    'xxx',
];

$knownVariables = array_keys(SpeechRenderer::variableHelp());
$files = glob(BASE_PATH . '/database/content/*.sql') ?: [];
sort($files);

$rows = [];
foreach ($files as $file) {
    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '{') {
            continue;
        }
        $row = json_decode($line, true);
        if (is_array($row)) {
            $row['_file'] = basename($file);
            $rows[] = $row;
        }
    }
}

$errors = [];
$warnings = [];
$seen = [];

/** Vitesse de lecture à voix haute : ~160 mots/minute, en français posé. */
$wordsPerMinute = 160;

$normalise = static fn (string $text): string => mb_strtolower(
    str_replace(['’', '‘'], "'", strip_tags($text))
);

foreach ($rows as $index => $row) {
    $label = (string) ($row['code'] ?? ('« ' . mb_substr((string) ($row['question'] ?? '?'), 0, 40) . ' »'));
    $label .= ' — ' . mb_substr((string) ($row['title'] ?? $row['question'] ?? ''), 0, 60);
    $text = (string) ($row['content'] ?? $row['answer'] ?? '');

    if (trim(strip_tags($text)) === '') {
        $errors[] = "{$label} : texte vide";
        continue;
    }

    $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $lower = $normalise($plain);

    // 1) Expressions interdites.
    foreach ($forbidden as $phrase) {
        if (str_contains($lower, $phrase)) {
            $errors[] = "{$label} : expression à éviter « {$phrase} »";
        }
    }

    // 2) Variables connues uniquement.
    if (preg_match_all('/\{([a-z_]+)\}/u', $plain, $matches)) {
        foreach (array_unique($matches[1]) as $variable) {
            if (!in_array($variable, $knownVariables, true)) {
                $errors[] = "{$label} : variable inconnue {{$variable}}";
            }
        }
    }

    // 3) Doublons exacts.
    $fingerprint = md5($lower);
    if (isset($seen[$fingerprint])) {
        $errors[] = "{$label} : doublon exact de {$seen[$fingerprint]}";
    } else {
        $seen[$fingerprint] = $label;
    }

    // 4) Longueur à l'oral (hors lignes « Conseil »).
    $spoken = preg_replace('/<em>Conseil.*?<\/em>/isu', '', $text) ?? $text;
    // Fin de paragraphe, de puce ou saut de ligne = fin d'idée à l'oral.
    $spoken = preg_replace('#</p>|<br\s*/?>|</li>#i', "\n", $spoken) ?? $spoken;
    $spoken = html_entity_decode(strip_tags($spoken), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $words = max(1, str_word_count($spoken, 0, 'àâäéèêëîïôöùûüçÀÂÄÉÈÊËÎÏÔÖÙÛÜÇ\''));
    $seconds = (int) round($words / $wordsPerMinute * 60);

    $type = (string) ($row['type'] ?? 'objection');
    if ($type === 'accroche' && $seconds > 20) {
        $warnings[] = "{$label} : accroche de {$seconds} s (cible : moins de 20 s)";
    }
    if (in_array($type, ['argumentaire', 'tarif', 'preuve', 'decouverte'], true) && $seconds > 45) {
        $warnings[] = "{$label} : {$seconds} s à lire (cible : moins de 45 s)";
    }
    if ($type === 'repondeur' && $seconds > 20) {
        $warnings[] = "{$label} : message de répondeur de {$seconds} s (cible : moins de 20 s)";
    }

    // 5) Phrases trop longues.
    $spokenSentences = preg_split('/(?<=[.!?])\s+(?=[A-ZÀÂÄÉÈÊËÎÏÔÖÙÛÜÇ«"])|\n+/u', $spoken) ?: [];
    foreach ($spokenSentences as $sentence) {
        $count = str_word_count(trim($sentence), 0, 'àâäéèêëîïôöùûüçÀÂÄÉÈÊËÎÏÔÖÙÛÜÇ\'');
        if ($count > 25 && !str_contains($sentence, 'Conseil')) {
            $warnings[] = "{$label} : phrase de {$count} mots (« " . mb_substr(trim($sentence), 0, 60) . "… »)";
            break;
        }
    }

    // 6) Chaque bloc doit avoir une ligne « Conseil » (sauf objections et conclusions).
    if (!in_array($type, ['objection', 'conclusion'], true) && !str_contains($text, 'Conseil')) {
        $warnings[] = "{$label} : pas de ligne « Conseil » pour le commercial";
    }
}

// 7) Textes quasi identiques (même début de texte).
$starts = [];
foreach ($rows as $row) {
    $text = html_entity_decode(strip_tags((string) ($row['content'] ?? $row['answer'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Les formules de politesse sont communes à tous les appels : on les ignore
    // pour comparer ce qui distingue vraiment les textes.
    // La formule d'ouverture (contact + prénom + société) est une constante de
    // conformité : on compare ce qui vient après.
    $compare = preg_replace(
        '/^\{contact\}[\s,.:!]*|^(bonjour|rebonjour|bonsoir|merci|alors)[\s,.:!]*|\{prenom_commercial\}[^.?!]*/iu',
        '',
        trim($text)
    ) ?? trim($text);
    $compare = trim(preg_replace('/^[\s,.:!-]+/u', '', $compare) ?? $compare);
    $start = mb_substr(mb_strtolower($compare), 0, 80);
    if ($start === '') {
        continue;
    }
    if (isset($starts[$start])) {
        $errors[] = sprintf(
            '%s : début identique à %s',
            (string) ($row['code'] ?? '?'),
            $starts[$start]
        );
    } else {
        $starts[$start] = (string) ($row['code'] ?? '?');
    }
}

$byType = [];
foreach ($rows as $row) {
    $byType[(string) ($row['type'] ?? 'objection')] = ($byType[(string) ($row['type'] ?? 'objection')] ?? 0) + 1;
}

echo "Relecture du contenu\n";
echo str_repeat('=', 68) . "\n";
echo 'Textes analysés : ' . count($rows) . "\n";
foreach ($byType as $type => $count) {
    printf("  %-16s %4d\n", $type, $count);
}
echo str_repeat('-', 68) . "\n";

if ($errors === []) {
    echo "Aucun point bloquant.\n";
} else {
    echo 'Blocages (' . count($errors) . ") :\n";
    foreach ($errors as $error) {
        echo '  ✗ ' . $error . "\n";
    }
}
if ($warnings !== []) {
    echo 'Remarques (' . count($warnings) . ") :\n";
    foreach ($warnings as $warning) {
        echo '  · ' . $warning . "\n";
    }
}

exit($errors === [] ? 0 : 1);
