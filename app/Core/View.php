<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Helpers de vue utilisés dans les gabarits.
 */
final class View
{
    /** Échappement HTML raccourci : e($valeur). */
    public static function e(mixed $value): string
    {
        return Sanitizer::e($value);
    }

    /** URL absolue : url('/login') → https://site/prime-script/login. */
    public static function url(string $path = '/'): string
    {
        return Http::url($path);
    }

    /** URL d'un asset public avec cache-busting léger. */
    public static function asset(string $path): string
    {
        return Http::url('assets/' . ltrim($path, '/'));
    }

    /** Icône SVG interne (liste blanche dans App\Core\Icon). */
    public static function icon(string $name, string $class = ''): string
    {
        return Icon::svg($name, $class);
    }

    /** Jeton CSRF caché à insérer dans chaque formulaire POST. */
    public static function csrf(): string
    {
        return '<input type="hidden" name="_token" value="' . self::e(Session::csrfToken()) . '">';
    }

    /**
     * Formulaire d'action compact (boutons Monter/Descendre, basculer, supprimer…).
     *
     * @param string $action  chemin interne (« /admin/blocs/12/basculer »)
     * @param array<string, string> $fields champs cachés supplémentaires
     * @param string $label   texte du bouton
     * @param string $class   classes du bouton
     * @param string $confirm message de confirmation (vide = pas de confirmation)
     */
    public static function actionForm(
        string $action,
        array $fields,
        string $label,
        string $class = 'btn btn--ghost btn--sm',
        string $confirm = '',
        string $title = ''
    ): string {
        $html = '<form method="post" action="' . self::e(self::url($action)) . '"';
        if ($confirm !== '') {
            $html .= ' data-confirm="' . self::e($confirm) . '"';
        }
        $html .= '>';
        $html .= self::csrf();
        foreach ($fields as $name => $value) {
            $html .= '<input type="hidden" name="' . self::e($name) . '" value="' . self::e($value) . '">';
        }
        $html .= '<button type="submit" class="' . self::e($class) . '"' . ($title !== '' ? ' title="' . self::e($title) . '"' : '') . '>';
        $html .= $label;
        $html .= '</button></form>';
        return $html;
    }

    /** Classe « active » si le chemin courant correspond. */
    public static function active(string $path, bool $exact = false): string
    {
        $current = Http::path();
        $match = $exact ? $current === $path : str_starts_with($current, $path);
        return $match ? ' is-active' : '';
    }

    /** Date lisible en français (4 nov. 2026). */
    public static function dateFr(?string $date, bool $withTime = false): string
    {
        if (!$date) {
            return '—';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return '—';
        }
        $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
        $text = (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
        if ($withTime) {
            $text .= ' à ' . date('H:i', $ts);
        }
        return $text;
    }

    /** « il y a 3 min », « hier », « 12/10 » — lisible d'un coup d'œil. */
    public static function ago(?string $date): string
    {
        if (!$date) {
            return '—';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return '—';
        }
        $delta = time() - $ts;
        if ($delta < 60) {
            return "à l'instant";
        }
        if ($delta < 3600) {
            $m = (int) floor($delta / 60);
            return 'il y a ' . $m . ' min';
        }
        if ($delta < 86400) {
            $h = (int) floor($delta / 3600);
            return 'il y a ' . $h . ' h';
        }
        if ($delta < 172800) {
            return 'hier';
        }
        if ($delta < 604800) {
            $d = (int) floor($delta / 86400);
            return 'il y a ' . $d . ' jours';
        }
        return self::dateFr(date('Y-m-d', $ts));
    }

    /** Initiales d'un nom (« Hamdi Souissi » → HS). */
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        if ($parts === []) {
            return '?';
        }
        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1, 'UTF-8') : '';
        return mb_strtoupper($first . $last, 'UTF-8');
    }

    /** Tronque un texte brut pour un résumé. */
    public static function excerpt(string $html, int $length = 120): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        return Sanitizer::limit($text, $length) . (mb_strlen($text, 'UTF-8') > $length ? '…' : '');
    }

    /** Prix « 790 € » ou « Sur devis ». */
    public static function price(?int $price): string
    {
        return $price === null ? 'Sur devis' : Sanitizer::formatPrice($price);
    }
}
