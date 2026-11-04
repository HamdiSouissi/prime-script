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

        // Contenu saisi en texte brut (pas de balise) : on transforme les lignes en puces/paragraphes.
        if (!preg_match('/<(p|ul|li|strong|br)\b/i', $html)) {
            $html = Sanitizer::plainToRich($html);
        }

        $html = self::replaceVariables($html, $variables);
        $html = Sanitizer::richText($html, 20000);

        return trim(self::frenchTypo($html));
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

        // 1) Un groupe entre parenthèses entièrement vide disparaît : « ( ) » → rien.
        $html = preg_replace('/\s*\(\s*(?:' . $marker . '\s*)*\)/u', '', $html) ?? $html;
        // 2) Une énumération orpheline disparaît : « et {vide} », « ou {vide} ».
        $html = preg_replace('/\s+(?:et|ou|puis)\s+' . $marker . '/iu', '', $html) ?? $html;
        $html = preg_replace('/' . $marker . '\s+(?:et|ou|puis)\s+/iu', '', $html) ?? $html;
        // 3) Le reste : la marque est simplement retirée.
        $html = str_replace($marker, '', $html);

        // 4) Nettoyage final de la ponctuation et des espaces laissés par les suppressions.
        for ($i = 0; $i < 2; $i++) {
            $html = preg_replace('/[ \t]{2,}/u', ' ', $html) ?? $html;
            $html = preg_replace('/\s+([,.;:!?])/u', '$1', $html) ?? $html;
            $html = preg_replace('/([,;:])\s*\1/u', '$1', $html) ?? $html;
            $html = preg_replace('/\s+([^\s<>]*>)/u', ' $1', $html) ?? $html;
        }

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
