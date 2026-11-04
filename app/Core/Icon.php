<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Icônes SVG internes (sprite public/assets/img/icons.svg).
 *
 * Aucune bibliothèque d'icônes externe : les noms sont validés contre une liste blanche,
 * ce qui empêche toute injection de balise via la base de données.
 */
final class Icon
{
    /** Icônes disponibles (identiques aux <g id="i-…"> du sprite). */
    private const ALLOWED = [
        'home', 'phone', 'phone-out', 'phone-off', 'calendar', 'check', 'check-circle', 'x', 'x-circle',
        'alert', 'info', 'arrow-left', 'arrow-right', 'chevron-down', 'chevron-right', 'chevron-left',
        'chevron-up', 'arrow-down', 'arrow-up', 'plus', 'minus', 'edit', 'trash', 'copy', 'eye', 'save',
        'search', 'filter', 'download', 'upload', 'user', 'users', 'lock', 'log-out', 'settings', 'grid',
        'layers', 'message', 'shield', 'sparkles', 'bolt', 'target', 'award', 'tag', 'flag', 'globe',
        'monitor', 'smartphone', 'smartphone-app', 'bot', 'cpu', 'store', 'chart', 'trending', 'history',
        'clock', 'briefcase', 'building', 'hammer', 'utensils', 'handshake', 'rocket', 'star', 'heart',
        'sun', 'moon', 'menu', 'close', 'refresh', 'restore', 'grip', 'link', 'list', 'file', 'mail',
        'wand', 'zoom', 'bell', 'play', 'pause', 'database', 'book', 'key', 'clipboard', 'pie',
    ];

    /** Icônes proposées pour les secteurs. */
    public const SECTOR_ICONS = ['building', 'hammer', 'utensils', 'handshake', 'briefcase', 'store', 'home', 'globe'];

    /** Icônes proposées pour les services. */
    public const SERVICE_ICONS = ['monitor', 'smartphone', 'smartphone-app', 'bot', 'cpu', 'store', 'globe', 'sparkles', 'chart', 'wand'];

    /** Vérifie qu'un nom d'icône est autorisé. */
    public static function isAllowed(string $name): bool
    {
        return in_array($name, self::ALLOWED, true);
    }

    /**
     * Balise <svg> complète, prête à être insérée dans le HTML.
     *
     * @param string $name  nom d'icône (liste blanche)
     * @param string $class classes CSS additionnelles
     * @param int    $size  taille en pixels (attribut width/height)
     */
    public static function svg(string $name, string $class = '', int $size = 20): string
    {
        $name = self::isAllowed($name) ? $name : 'info';
        $classes = trim('ico ' . $class);
        // Rendu volontairement compact : le sprite porte la géométrie, le CSS porte la taille.
        // Chemin absolu (et non URL complète) pour que <use> fonctionne à toute profondeur.
        $style = $size === 20 ? '' : sprintf(' style="width:%1$dpx;height:%1$dpx"', $size);
        return sprintf(
            '<svg class="%s" aria-hidden="true" focusable="false"%s><use href="%s#i-%s"></use></svg>',
            Sanitizer::e($classes),
            $style,
            Sanitizer::e(self::spritePath()),
            Sanitizer::e($name)
        );
    }

    /** Chemin du sprite, relatif à la racine du site (ex. /assets/img/icons.svg). */
    private static function spritePath(): string
    {
        $base = parse_url(Http::baseUrl(), PHP_URL_PATH);
        $base = is_string($base) ? rtrim($base, '/') : '';
        return $base . '/assets/img/icons.svg';
    }

    /** Nom du fichier sprite (utilisé par le JS). */
    public static function spriteUrl(): string
    {
        return Http::url('assets/img/icons.svg');
    }
}
