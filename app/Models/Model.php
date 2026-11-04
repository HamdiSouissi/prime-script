<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * Modèle de base : opérations CRUD simples sur une table.
 * Toutes les requêtes passent par des requêtes préparées (Db).
 */
abstract class Model
{
    /** Nom de la table (sans préfixe). */
    protected static string $table = '';

    /** Clé primaire. */
    protected static string $primaryKey = 'id';

    /** Retourne une ligne par son identifiant. */
    public static function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        return Db::one(
            sprintf('SELECT * FROM `%s` WHERE %s = ?', Db::table(static::$table), static::$primaryKey),
            [$id]
        );
    }

    /**
     * Toutes les lignes.
     *
     * @param string $orderBy clause ORDER BY sûre (écrite dans le code, jamais depuis l'utilisateur)
     * @return array<int, array<string, mixed>>
     */
    public static function all(string $orderBy = 'position ASC, id ASC'): array
    {
        return Db::all(sprintf('SELECT * FROM `%s` ORDER BY %s', Db::table(static::$table), $orderBy));
    }

    /** Nom de la table (sans préfixe), pour les requêtes personnalisées. */
    public static function tableName(): string
    {
        return static::$table;
    }

    /** Nombre de lignes. */
    public static function count(string $where = '1', array $params = []): int
    {
        return (int) Db::value(
            sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', Db::table(static::$table), $where),
            $params,
            0
        );
    }

    /**
     * Crée une ligne et retourne son identifiant.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return Db::insert(static::$table, $data);
    }

    /**
     * Met à jour une ligne.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): int
    {
        return Db::update(static::$table, $data, [static::$primaryKey => $id]);
    }

    /** Supprime une ligne. */
    public static function delete(int $id): int
    {
        return Db::exec(
            sprintf('DELETE FROM `%s` WHERE %s = ?', Db::table(static::$table), static::$primaryKey),
            [$id]
        );
    }

    /**
     * Tableau ordonné id => libellé (utile pour les filtres et les <select>).
     *
     * @param string $label colonne à afficher
     * @return array<int, string>
     */
    public static function options(string $label = 'name', bool $onlyActive = true): array
    {
        // Le tri par « position » n'a de sens que pour les tables ordonnées (users n'en a pas).
        $order = static::$table === 'users' ? '%2$s ASC' : 'position ASC, %2$s ASC';
        $sql = sprintf(
            'SELECT id, %2$s FROM `%1$s` %3$s ORDER BY ' . $order,
            Db::table(static::$table),
            $label,
            $onlyActive && static::$table !== 'users' ? 'WHERE is_active = 1' : ''
        );
        $out = [];
        foreach (Db::all($sql) as $row) {
            $out[(int) $row['id']] = (string) $row[$label];
        }
        return $out;
    }

    /** Déplace une ligne d'une position (boutons monter/descendre). */
    public static function move(int $id, int $direction): void
    {
        $current = static::find($id);
        if ($current === null) {
            return;
        }
        $sql = $direction < 0
            ? 'SELECT id, position FROM `%s` WHERE position < ? ORDER BY position DESC, id DESC LIMIT 1'
            : 'SELECT id, position FROM `%s` WHERE position > ? ORDER BY position ASC, id ASC LIMIT 1';
        $neighbor = Db::one(sprintf($sql, Db::table(static::$table)), [(int) $current['position']]);
        if ($neighbor === null) {
            return;
        }
        Db::exec(
            sprintf('UPDATE `%s` SET position = ? WHERE id = ?', Db::table(static::$table)),
            [(int) $neighbor['position'], (int) $current['id']]
        );
        Db::exec(
            sprintf('UPDATE `%s` SET position = ? WHERE id = ?', Db::table(static::$table)),
            [(int) $current['position'], (int) $neighbor['id']]
        );
    }

    /** Prochaine position libre (fin de liste). */
    public static function nextPosition(): int
    {
        return (int) Db::value(sprintf('SELECT COALESCE(MAX(position), 0) + 1 FROM `%s`', Db::table(static::$table)), [], 1);
    }
}
