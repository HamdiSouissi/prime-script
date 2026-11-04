<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * Services commercialisés (Site web, Application web/mobile, Automatisation IA, Chatbot IA).
 */
final class Service extends Model
{
    protected static string $table = 'services';

    /**
     * Services actifs, dans l'ordre.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        return Db::all('SELECT * FROM services WHERE is_active = 1 ORDER BY position ASC, name ASC');
    }

    /**
     * Services actifs indexés par identifiant.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeById(): array
    {
        $out = [];
        foreach (self::active() as $service) {
            $out[(int) $service['id']] = $service;
        }
        return $out;
    }

    /** Indique si un service actif existe. */
    public static function existsActive(int $id): bool
    {
        return $id > 0 && (int) Db::value(
            'SELECT COUNT(*) FROM services WHERE id = ? AND is_active = 1',
            [$id],
            0
        ) === 1;
    }
}
