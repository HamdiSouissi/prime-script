<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Nettoyage, validation et échappement des données.
 */
final class Sanitizer
{
    /** Échappe une valeur pour l'affichage HTML (protection XSS). */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Texte brut : supprime les espaces superflus et les caractères de contrôle. */
    public static function text(mixed $value, int $maxLength = 255): string
    {
        $value = (string) $value;
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = trim(preg_replace('/[ \t]+/u', ' ', $value) ?? '');
        return self::limit($value, $maxLength);
    }

    /** Texte multiligne : conserve les retours à la ligne, nettoie le reste. */
    public static function textarea(mixed $value, int $maxLength = 8000): string
    {
        $value = (string) $value;
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?? '';
        return self::limit(trim($value), $maxLength);
    }

    /** Tronque proprement une chaîne (UTF-8). */
    public static function limit(string $value, int $maxLength): string
    {
        if ($maxLength <= 0) {
            return $value;
        }
        if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $maxLength) {
            return rtrim(mb_substr($value, 0, $maxLength, 'UTF-8'));
        }
        if (!function_exists('mb_strlen') && strlen($value) > $maxLength) {
            return rtrim(substr($value, 0, $maxLength));
        }
        return $value;
    }

    /** Adresse e-mail normalisée, ou null si invalide. */
    public static function email(mixed $value): ?string
    {
        $email = strtolower(trim((string) $value));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? self::limit($email, 190) : null;
    }

    /** Identifiant entier positif (0 si invalide). */
    public static function id(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return ($id === false || $id < 1) ? 0 : (int) $id;
    }

    /** Date Y-m-d valide, ou null. */
    public static function date(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y) ? $value : null;
    }

    /** Montant en euros (« 790 », « 790,50 », « 2 900 € ») → entier positif ou null. */
    public static function price(mixed $value): ?int
    {
        $raw = str_replace([' ', "\u{202F}", "\u{00A0}", '€', 'EUR'], '', (string) $value);
        $raw = str_replace(',', '.', $raw);
        if ($raw === '' || !is_numeric($raw)) {
            return null;
        }
        $price = (int) round((float) $raw);
        return $price >= 0 ? $price : null;
    }

    /** Formate un prix « 790 € ». */
    public static function formatPrice(?int $price): string
    {
        return $price === null ? '' : number_format($price, 0, ',', ' ') . ' €';
    }

    /**
     * Normalise un numéro de téléphone français (facultatif).
     */
    public static function phone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return implode(' ', str_split($digits, 2));
        }
        if (strlen($digits) === 9) {
            $digits = '0' . $digits;
            return implode(' ', str_split($digits, 2));
        }
        return self::limit((string) $value, 30);
    }

    /**
     * Nettoie du HTML enrichi très simple (gras, italique, listes à puces, sauts de ligne).
     * Toute autre balise est supprimée, les attributs sont retirés.
     * C'est l'unique fonction à utiliser avant de stocker un contenu de bloc de speech.
     */
    public static function richText(mixed $value, int $maxLength = 8000): string
    {
        $html = self::textarea($value, $maxLength);
        if ($html === '') {
            return '';
        }

        // Balises autorisées (aucun attribut conservé).
        $allowed = '<strong><b><em><i><ul><li><p><br><h4><h5>';

        // Supprime les blocs script/style et leur contenu avant tout traitement.
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        // Neutralise tout attribut / gestionnaire d'événement.
        $html = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';
        $html = preg_replace('#\s+(href|src|style|class|id)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';

        $clean = strip_tags($html, $allowed);
        // Réécrit les balises équivalentes pour un balisage homogène.
        $clean = preg_replace('#<(b|strong)\b[^>]*>#i', '<strong>', $clean) ?? $clean;
        $clean = preg_replace('#</(b|strong)\s*>#i', '</strong>', $clean) ?? $clean;
        $clean = preg_replace('#<(i|em)\b[^>]*>#i', '<em>', $clean) ?? $clean;
        $clean = preg_replace('#</(i|em)\s*>#i', '</em>', $clean) ?? $clean;
        $clean = preg_replace('#<(h4|h5)\b[^>]*>#i', '<h4>', $clean) ?? $clean;
        $clean = preg_replace('#</(h4|h5)\s*>#i', '</h4>', $clean) ?? $clean;
        $clean = preg_replace('#<p\b[^>]*>#i', '<p>', $clean) ?? $clean;
        $clean = preg_replace('#<br\s*/?>#i', '<br>', $clean) ?? $clean;
        $clean = preg_replace('#<ul\b[^>]*>#i', '<ul>', $clean) ?? $clean;
        $clean = preg_replace('#<li\b[^>]*>#i', '<li>', $clean) ?? $clean;

        // Ferme les balises laissées ouvertes (contenu collé depuis un traitement de texte).
        return self::balanceTags(trim($clean));
    }

    /**
     * Rééquilibre les balises autorisées : referme les balises laissées ouvertes,
     * ignore les fermetures orphelines et ferme automatiquement un <li> avant le suivant.
     */
    private static function balanceTags(string $html): string
    {
        $allowed = ['strong', 'em', 'ul', 'li', 'p', 'h4'];
        $stack = [];
        $out = '';
        $offset = 0;

        preg_match_all('#</?(strong|em|ul|li|p|h4)\b[^>]*>#i', $html, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $i => [$tagText, $position]) {
            $out .= substr($html, $offset, $position - $offset);
            $offset = $position + strlen($tagText);

            $name = strtolower($matches[1][$i][0]);
            if (!in_array($name, $allowed, true)) {
                continue;
            }

            if (!str_starts_with($tagText, '</')) {
                if ($name === 'li' && end($stack) === 'li') {
                    $out .= '</li>';
                    array_pop($stack);
                }
                $stack[] = $name;
                $out .= $tagText;
                continue;
            }

            if (!in_array($name, $stack, true)) {
                continue; // fermeture orpheline ignorée
            }
            while ($stack !== [] && end($stack) !== $name) {
                $out .= '</' . array_pop($stack) . '>';
            }
            array_pop($stack);
            $out .= '</' . $name . '>';
        }

        $out .= substr($html, $offset);
        while ($stack !== []) {
            $out .= '</' . array_pop($stack) . '>';
        }
        return $out;
    }

    /** Convertit du texte brut (saisie simple) en HTML enrichi : une ligne = un paragraphe, « - » = puce. */
    public static function plainToRich(string $text): string
    {
        $text = self::textarea($text);
        if ($text === '') {
            return '';
        }
        $out = [];
        $inList = false;
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($inList) {
                    $out[] = '</ul>';
                    $inList = false;
                }
                continue;
            }
            if (preg_match('/^[-*•]\s*(.+)$/u', $line, $m)) {
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
            $out[] = '<p>' . $line . '</p>';
        }
        if ($inList) {
            $out[] = '</ul>';
        }
        return implode("\n", $out);
    }
}
