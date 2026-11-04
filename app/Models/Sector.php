<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * Secteurs d'activité du prospect (Architectes, Artisans, Restauration, Services B2B…).
 */
final class Sector extends Model
{
    protected static string $table = 'sectors';

    /**
     * Secteurs actifs, dans l'ordre d'affichage.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        return Db::all('SELECT * FROM sectors WHERE is_active = 1 ORDER BY position ASC, name ASC');
    }

    /**
     * Secteurs actifs indexés par identifiant.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeById(): array
    {
        $out = [];
        foreach (self::active() as $sector) {
            $out[(int) $sector['id']] = $sector;
        }
        return $out;
    }
}
