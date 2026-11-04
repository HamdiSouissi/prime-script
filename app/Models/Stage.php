<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * Étapes de suivi commercial (Premier appel, Relance, Closing).
 */
final class Stage extends Model
{
    protected static string $table = 'stages';

    /**
     * Étapes actives, dans l'ordre.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        return Db::all('SELECT * FROM stages WHERE is_active = 1 ORDER BY position ASC, name ASC');
    }

    /**
     * Étapes actives indexées par identifiant.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeById(): array
    {
        $out = [];
        foreach (self::active() as $stage) {
            $out[(int) $stage['id']] = $stage;
        }
        return $out;
    }

    /** Étape par défaut (la première active). */
    public static function default(): ?array
    {
        return Db::one('SELECT * FROM stages WHERE is_active = 1 ORDER BY position ASC, id ASC LIMIT 1');
    }
}
