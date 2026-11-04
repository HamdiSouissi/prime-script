<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Sanitizer;

/**
 * Rend un bloc de speech prêt à être lu à voix haute :
 * - remplace les variables {entreprise_client}, {contact}, {secteur}, {prenom_commercial}, {service} ;
 * - convertit un contenu tapé au kilomètre (lignes « - ») en vraies puces ;
 * - ne laisse passer que le HTML enrichi autorisé.
 */
final class SpeechRenderer
{
    /**
     * @param array<string, string> $variables
     */
    public static function render(string $content, array $variables = []): string
    {
        $html = $content;

        // Contenu saisi en texte brut (aucun bloc HTML) : on transforme les lignes
        // en puces et paragraphes. Un simple <br> ou <strong> ne suffit pas à
        // considérer le texte comme déjà structuré : sinon les « - » resteraient
        // des tirets à l'écran.
        if (!preg_match('/<(p|ul|ol|li)\b/i', $html)) {
            $html = Sanitizer::plainToRich($html);
        } else {
            // Contenu mixte (paragraphes HTML + lignes « - ») : les tirets
            // deviennent de vraies puces au lieu de rester des tirets.
            $html = self::dashLinesToHtml($html);
        }

        $html = self::replaceVariables($html, $variables);
        $html = Sanitizer::richText($html, 20000);

        return trim(self::frenchTypo($html));
    }

    /**
     * Convertit les lignes commençant par « - », « * » ou « • » en liste à puces,
     * même quand le contenu contient déjà des paragraphes HTML.
     *
     * Utile pour les contenus rédigés vite dans l'admin ou importés depuis des
     * scripts : le commercial ne doit jamais voir un tiret en début de ligne.
     */
    public static function dashLinesToHtml(string $html): string
    {
        if (!preg_match('/^[ \t]*[-*•][ \t]+\S/mu', $html)) {
            return $html;
        }
        // Une liste déjà balisée n'est pas retouchée.
        if (preg_match('/<ul\b/i', $html)) {
            return $html;
        }

        $out = [];
        $inList = false;
        foreach (preg_split('/\R/u', $html) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($inList) {
                    $out[] = '</ul>';
                    $inList = false;
                }
                continue;
            }
            if (preg_match('/^[-*•][ \t]*(.+)$/u', $line, $m)) {
                if (!$inList) {
                    $out[] = '<ul>';
                    $inList = true;
                }
                $out[] = '<li>' . $m[1] . '</li>';
                continue;
            }
            if ($inList) {
                $out[] = '</ul>';
                $inList = false;
            }
            $out[] = preg_match('/^<(p|ul|ol|li|h4|h5|em|strong)\b/i', $line)
                ? $line
                : '<p>' . $line . '</p>';
        }
        if ($inList) {
            $out[] = '</ul>';
        }
        return implode("\n", $out);
    }

    /**
     * Typographie française sur le texte seul (jamais dans les balises) :
     * espace insécable avant « ; : ! ? » et à l'intérieur des guillemets.
     */
    public static function frenchTypo(string $html): string
    {
        $nbsp = "\u{00A0}";
        $parts = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];

        foreach ($parts as $index => $part) {
            if ($part === '' || str_starts_with($part, '<')) {
                continue;
            }
            $part = preg_replace('/[ \t]*([;:!?])/u', $nbsp . '$1', $part) ?? $part;
            $part = preg_replace('/«[ \t]*/u', '«' . $nbsp, $part) ?? $part;
            $part = preg_replace('/[ \t]*»/u', $nbsp . '»', $part) ?? $part;
            $parts[$index] = $part;
        }

        return implode('', $parts);
    }

    /** Remplace les variables, en gérant proprement les valeurs vides. */
    public static function replaceVariables(string $html, array $variables): string
    {
        $marker = "\x1F"; // marque temporaire (caractère de contrôle jamais présent dans un contenu)
        $values = [];
        foreach ($variables as $key => $value) {
            $value = trim((string) $value);
            $values['{' . trim($key, '{}') . '}'] = $value === '' ? $marker : $value;
        }

        // Toute variable inconnue est traitée comme vide (jamais « {xxx} » à l'écran).
        $html = preg_replace_callback(
            '/\{([a-z_]+)\}/iu',
            static fn (array $m): string => $values['{' . $m[1] . '}'] ?? $marker,
            $html
        ) ?? $html;

        // 1) Les groupes vides construits pour les cas facultatifs disparaissent :
        //    « (chez {entreprise_client}) », « (avec {contact}) », « ({service}) »…
        $html = preg_replace(
            '/\s*[(\[][^()\[\].!?;]{0,24}?' . $marker . '[^()\[\]]{0,8}?[)\]]/u',
            '',
            $html
        ) ?? $html;
        // 2) Une énumération orpheline disparaît : « et {vide} », « ou {vide} », « avec {vide} ».
        $html = preg_replace('/\s+(?:et|ou|puis|avec|pour)\s+' . $marker . '/iu', '', $html) ?? $html;
        $html = preg_replace('/' . $marker . '\s+(?:et|ou|puis)\s+/iu', '', $html) ?? $html;
        // 3) Le reste : la marque est simplement retirée.
        $html = str_replace($marker, '', $html);

        // 4) Un nom absent ne laisse pas de ponctuation orpheline derrière lui :
        //    « Bonjour {contact}, » → « Bonjour, » ; « {contact}, deux minutes ? » → « Deux minutes ? »
        $html = preg_replace('/(^|>)\s*' . $marker . '\s*,\s*/u', '$1', $html) ?? $html;
        $html = preg_replace('/(?<![^>])[ \t]*,?\s*' . $marker . '\s*,\s*/u', ', ', $html) ?? $html;
        $html = preg_replace('/(^|>)\s*' . $marker . '\s*([.!?])/u', '$1', $html) ?? $html;

        // 5) Ponctuation restée orpheline en début de phrase ou entre deux signes :
        //    « Bonjour, , deux minutes » ; « au sujet de . » ; « de Prime Vision., deux ».
        $html = preg_replace('/[ \t]*[,;:]\s*([.!?])/u', '$1', $html) ?? $html;
        $html = preg_replace('/\.\s*,/u', '.', $html) ?? $html;
        $html = preg_replace('/>\s*,/u', '>', $html) ?? $html;
        $html = preg_replace('/(^|>)\s*[,;:]\s*/u', '$1', $html) ?? $html;
        $html = preg_replace('/\s+([,.;:!?])/u', '$1', $html) ?? $html;

        // 6) Nettoyage final des espaces laissés par les suppressions.
        for ($i = 0; $i < 2; $i++) {
            $html = preg_replace('/[ \t]{2,}/u', ' ', $html) ?? $html;
            $html = preg_replace('/\s+([,.;:!?])/u', '$1', $html) ?? $html;
            $html = preg_replace('/([,;:])\s*\1/u', '$1', $html) ?? $html;
            $html = preg_replace('/\s+([^\s<>]*>)/u', ' $1', $html) ?? $html;
        }

        // 7) Une phrase qui commence par une minuscule après suppression prend une majuscule.
        //    Deux passes distinctes : le début d'un nœud de texte (après une balise)
        //    et le début d'une phrase à l'intérieur d'un même nœud.
        $upper = static fn (array $m): string => $m[1] . $m[2] . mb_strtoupper($m[3], 'UTF-8');
        $html = preg_replace_callback('/(^|>)(\s*)([a-zàâäéèêëîïôöùûüç])/u', $upper, $html) ?? $html;
        $html = preg_replace_callback('/([.!?]\s+)(\s*)([a-zàâäéèêëîïôöùûüç])/u', $upper, $html) ?? $html;

        return trim($html);
    }

    /**
     * Variables disponibles pour un contexte + aide affichée dans l'admin.
     *
     * @return array<string, string> clé => description
     */
    public static function variableHelp(): array
    {
        return [
            'entreprise_client' => "Nom de l'entreprise du prospect (saisi au début de l'appel).",
            'contact'           => 'Nom du contact chez le prospect.',
            'secteur'           => "Secteur d'activité sélectionné (ex. Architectes).",
            'prenom_commercial' => 'Prénom du commercial connecté.',
            'service'           => 'Service en cours de présentation (ex. Site web).',
            'quand'             => 'Date ou moment convenu (ex. jeudi 14 h). Vide pendant un premier appel.',
        ];
    }

    /**
     * Construit le jeu de variables du parcours d'appel.
     *
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public static function variablesFor(array $context): array
    {
        return [
            'entreprise_client' => (string) ($context['entreprise_client'] ?? ''),
            'contact'           => (string) ($context['contact'] ?? ''),
            'secteur'           => (string) ($context['sector_name'] ?? ''),
            'prenom_commercial' => (string) ($context['commercial_name'] ?? ''),
            'service'           => (string) ($context['service_name'] ?? ''),
            'quand'             => (string) ($context['quand'] ?? ''),
        ];
    }

    /**
     * Découpe un contenu en « bullets » courtes pour l'affichage d'un coup d'œil.
     * Utilisé pour les accroches et les réponses aux objections (mode « carte à lire »).
     *
     * @return array<int, string> liste de fragments HTML (chaque fragment = une puce)
     */
    public static function toBullets(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return [];
        }
        $bullets = [];

        // 1) Listes existantes.
        if (preg_match_all('#<li\b[^>]*>(.*?)</li>#is', $html, $matches)) {
            foreach ($matches[1] as $item) {
                $text = trim(strip_tags($item, '<strong><em>'));
                if ($text !== '') {
                    $bullets[] = $text;
                }
            }
            if ($bullets !== []) {
                return $bullets;
            }
        }

        // 2) Paragraphes / lignes.
        $parts = preg_split('#</p>|<br\s*/?>#i', $html) ?: [];
        foreach ($parts as $part) {
            $text = trim(strip_tags($part, '<strong><em>'));
            if ($text !== '') {
                $bullets[] = $text;
            }
        }
        return $bullets;
    }
}
