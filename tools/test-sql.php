<?php
declare(strict_types=1);

/**
 * Contrôle de compatibilité MySQL des requêtes agrégées (mode ONLY_FULL_GROUP_BY).
 *
 * Depuis MySQL 5.7, `ONLY_FULL_GROUP_BY` est actif par défaut : une colonne « nue »
 * (ni agrégée ni présente dans le GROUP BY) est refusée dans ORDER BY (erreur 1055).
 * SQLite, la base de démonstration, ne reproduit pas cette règle : une requête
 * invalide pour MySQL peut donc passer tous les tests locaux.
 *
 * C'est ce qui est arrivé à Stats::byService() et Stats::bySector() :
 * « ORDER BY s.position » sur une requête groupée ne listant pas s.position.
 * Résultat en production : erreur 500 et « Une erreur est survenue » sur le
 * tableau de bord, alors que tout fonctionnait en démonstration.
 *
 * Ce script analyse le code source (aucune base nécessaire) : il reconstitue
 * chaque requête SQL, y compris les concaténations PHP, puis vérifie que tout
 * terme d'un ORDER BY est soit agrégé, soit présent dans le GROUP BY.
 *
 * Usage : php tools/test-sql.php
 * Code de sortie : 0 si conforme, 1 si au moins une violation est trouvée.
 */

$root = dirname(__DIR__);

$useColor = getenv('NO_COLOR') === false;
$peindre  = static fn (string $code, string $texte): string => $useColor ? "\033[" . $code . 'm' . $texte . "\033[0m" : $texte;
$okLabel  = $peindre('32', 'OK');
$koLabel  = $peindre('31', 'KO');

/**
 * Reconstitue le SQL d'une expression PHP passée à Db::all(...) & co.
 *
 * Exemple :
 *   'SELECT … FROM calls WHERE created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, $d) . ' DAY) GROUP BY id ORDER BY pos'
 * devient un texte SQL continu, chaque interpolation PHP étant remplacée par un espace.
 */
function normaliserExpression(string $expression): string
{
    $sql = '';
    $i = 0;
    $longueur = strlen($expression);

    while ($i < $longueur) {
        $char = $expression[$i];

        // Littéral de chaîne : on en recopie le contenu.
        if ($char === "'" || $char === '"') {
            $guillemet = $char;
            $i++;
            while ($i < $longueur && $expression[$i] !== $guillemet) {
                if ($expression[$i] === '\\') {
                    $i++; // échappement
                }
                if ($i < $longueur) {
                    $sql .= $expression[$i];
                    $i++;
                }
            }
            $i++; // guillemet fermant
            continue;
        }

        // Concaténation : on ignore tout le fragment PHP jusqu'au « . » suivant.
        if ($char === '.') {
            $i++;
            $profondeur = 0;
            while ($i < $longueur) {
                $c = $expression[$i];
                if ($c === "'" || $c === '"') {
                    // Fragment de chaîne rencontré (fin de l'interpolation).
                    break;
                }
                if ($c === '(') {
                    $profondeur++;
                } elseif ($c === ')') {
                    if ($profondeur === 0) {
                        break;
                    }
                    $profondeur--;
                } elseif ($c === '.' && $profondeur === 0) {
                    $i++;
                    break;
                }
                $i++;
            }
            $sql .= ' ';
            continue;
        }

        $sql .= $char;
        $i++;
    }

    return $sql;
}

/**
 * Extrait les expressions passées aux méthodes d'accès aux données.
 *
 * @return array<int, string>
 */
function extraireRequetes(string $code): array
{
    $requetes = [];
    $motif = '#\bDb::(?:all|one|value|run|exec)\s*\(#i';

    if (preg_match_all($motif, $code, $trouves, PREG_OFFSET_CAPTURE) === false) {
        return [];
    }

    foreach ($trouves[0] as [$entete, $debut]) {
        $i = $debut + strlen($entete); // juste après la parenthèse ouvrante
        $profondeur = 1;
        $longueur = strlen($code);
        $tampon = '';

        while ($i < $longueur && $profondeur > 0) {
            $char = $code[$i];

            if ($char === "'" || $char === '"') {
                $guillemet = $char;
                $tampon .= $char;
                $i++;
                while ($i < $longueur) {
                    if ($code[$i] === '\\') {
                        $tampon .= substr($code, $i, 2);
                        $i += 2;
                        continue;
                    }
                    $tampon .= $code[$i];
                    if ($code[$i] === $guillemet) {
                        $i++;
                        break;
                    }
                    $i++;
                }
                continue;
            }

            if ($char === '(') {
                $profondeur++;
            } elseif ($char === ')') {
                $profondeur--;
                if ($profondeur === 0) {
                    break;
                }
            }

            $tampon .= $char;
            $i++;
        }

        $sql = normaliserExpression($tampon);
        if (preg_match('#\bGROUP\s+BY\b#i', $sql) === 1) {
            $requetes[] = $sql;
        }
    }

    return $requetes;
}

/** Découpe une liste SQL sur les virgules de premier niveau. */
function decouperListe(string $liste): array
{
    $elements = [];
    $profondeur = 0;
    $courant = '';
    $longueur = strlen($liste);

    for ($i = 0; $i < $longueur; $i++) {
        $char = $liste[$i];
        if ($char === '(') {
            $profondeur++;
        } elseif ($char === ')') {
            $profondeur--;
        }
        if ($char === ',' && $profondeur === 0) {
            $elements[] = trim($courant);
            $courant = '';
            continue;
        }
        $courant .= $char;
    }
    if (trim($courant) !== '') {
        $elements[] = trim($courant);
    }

    return $elements;
}

// ------------------------------------------------------------------ Analyse
$fichiers = [];
foreach (['/app/Models', '/app/Services', '/app/Controllers', '/app/Core'] as $dossier) {
    $chemin = $root . $dossier;
    if (!is_dir($chemin)) {
        continue;
    }
    $iterateur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chemin));
    foreach ($iterateur as $fichier) {
        if ($fichier->isFile() && $fichier->getExtension() === 'php') {
            $fichiers[] = $fichier->getPathname();
        }
    }
}
sort($fichiers);

$violations = [];
$requetesControlees = 0;

foreach ($fichiers as $fichier) {
    $code = (string) file_get_contents($fichier);
    $relatif = str_replace($root . '/', '', $fichier);

    foreach (extraireRequetes($code) as $sql) {
        $requetesControlees++;
        $sqlCompact = preg_replace('/\s+/', ' ', $sql) ?? $sql;

        // Colonnes du GROUP BY (avec ou sans préfixe de table).
        preg_match_all('#\bGROUP\s+BY\s+(.+?)(?=\bORDER\s+BY\b|\bHAVING\b|\bLIMIT\b|$)#i', $sqlCompact, $groupes);
        $groupees = [];
        foreach ($groupes[1] as $liste) {
            foreach (decouperListe($liste) as $element) {
                $element = strtolower(trim($element));
                $groupees[$element] = true;
                $parties = explode('.', $element);
                $groupees[end($parties)] = true;
            }
        }

        // Alias d'agrégats : COUNT(...) AS total → « total » est triable.
        $aliasAgreges = [];
        if (preg_match_all('#\b(COUNT|SUM|MIN|MAX|AVG)\s*\(.*?\)\s+AS\s+([a-z_][a-z0-9_]*)#i', $sqlCompact, $alias) > 0) {
            foreach ($alias[2] as $nom) {
                $aliasAgreges[strtolower($nom)] = true;
            }
        }

        preg_match_all('#\bORDER\s+BY\s+(.+?)(?=\bLIMIT\b|$)#i', $sqlCompact, $ordres);
        foreach ($ordres[1] as $listeOrdre) {
            foreach (decouperListe($listeOrdre) as $terme) {
                $colonne = strtolower(trim(preg_replace('#\s+(ASC|DESC)\s*$#i', '', $terme) ?? $terme));
                if ($colonne === '' || str_contains($colonne, '(')) {
                    continue; // expression ou fonction : hors périmètre
                }
                if (isset($aliasAgreges[$colonne], $groupees[$colonne])) {
                    continue;
                }
                if (isset($aliasAgreges[$colonne]) || isset($groupees[$colonne])) {
                    continue;
                }

                $violations[] = sprintf(
                    "%s — ORDER BY %s : colonne ni agrégée ni dans le GROUP BY",
                    $relatif,
                    $terme
                );
            }
        }
    }
}

echo "\nContrôle ONLY_FULL_GROUP_BY (compatibilité MySQL)\n";
echo "-------------------------------------------------\n";
printf("  %d fichier(s), %d requête(s) groupée(s) analysée(s)\n\n", count($fichiers), $requetesControlees);

if ($requetesControlees === 0) {
    echo '  ' . $koLabel . "  Aucune requête groupée trouvée : le contrôle n'a rien vérifié.\n";
    exit(1);
}

if ($violations === []) {
    echo '  ' . $okLabel . "  Tous les ORDER BY de requêtes groupées sont valides pour MySQL.\n";
    exit(0);
}

foreach ($violations as $violation) {
    echo '  ' . $koLabel . '  ' . $violation . "\n";
}
printf("\n  %d violation(s). Correction : MIN(colonne), ou ajouter la colonne au GROUP BY.\n", count($violations));
exit(1);
