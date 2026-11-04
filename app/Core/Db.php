<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Accès à la base de données.
 *
 * - Production : MySQL / MariaDB via PDO (requêtes préparées uniquement).
 * - Démonstration locale : SQLite (même API, traduite automatiquement).
 *
 * Le code applicatif écrit du SQL « MySQL » portable :
 *   SELECT ... , CURDATE(), DATE_SUB(NOW(), INTERVAL 7 DAY), YEARWEEK(...), COUNT(DISTINCT ...)
 * et Db traduit ces expressions pour SQLite (voir dialect()).
 */
final class Db
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';
    private static string $prefix = '';

    /** Retourne la connexion PDO (singleton). */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = (array) Config::get('database', []);
        self::$driver = (string) ($db['driver'] ?? 'mysql');
        self::$prefix = (string) ($db['prefix'] ?? '');

        try {
            if (self::$driver === 'sqlite') {
                $path = (string) ($db['sqlite_path'] ?? BASE_PATH . '/storage/prime-script.sqlite');
                $pdo = new PDO('sqlite:' . $path, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $db['host'] ?? 'localhost',
                    (int) ($db['port'] ?? 3306),
                    $db['name'] ?? '',
                    $db['charset'] ?? 'utf8mb4'
                );
                $pdo = new PDO($dsn, (string) ($db['user'] ?? ''), (string) ($db['password'] ?? ''), [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            }
        } catch (PDOException $e) {
            self::connectionFailed($e);
        }

        self::$pdo = $pdo;
        return self::$pdo;
    }

    /** Erreur de connexion : message générique côté visiteur, détail dans les logs. */
    private static function connectionFailed(PDOException $e): never
    {
        error_log('[Prime Script] Connexion base de données impossible : ' . $e->getMessage());
        http_response_code(500);
        if (Config::isDev()) {
            echo '<h1>Base de données inaccessible</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        } else {
            echo '<h1>Service momentanément indisponible</h1><p>La base de données ne répond pas. Merci de réessayer dans quelques instants.</p>';
        }
        exit;
    }

    /** Ajoute le préfixe de table éventuel. */
    public static function table(string $name): string
    {
        self::pdo();
        return self::$prefix . $name;
    }

    /**
     * Exécute une requête préparée et retourne le statement.
     *
     * @param array<int|string, mixed> $params
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $pdo = self::pdo();
        $statement = $pdo->prepare(self::dialect($sql));
        foreach ($params as $key => $value) {
            $param = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $statement->bindValue($param, $value, $type);
        }
        $statement->execute();
        return $statement;
    }

    /**
     * Toutes les lignes d'une requête.
     *
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * Première ligne ou null.
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Première colonne de la première ligne. */
    public static function value(string $sql, array $params = [], mixed $default = null): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? $default : $value;
    }

    /** Exécute une écriture et retourne le nombre de lignes affectées. */
    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    /** Dernier identifiant inséré. */
    public static function lastId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Insertion à partir d'un tableau associatif (colonnes => valeurs).
     *
     * @param array<string, mixed> $data
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::table($table),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        self::run($sql, $data);
        return self::lastId();
    }

    /**
     * Mise à jour à partir d'un tableau associatif, filtrée par une clause WHERE simple.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public static function update(string $table, array $data, array $where): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = $column . ' = :s_' . $column;
        }
        $conditions = [];
        foreach (array_keys($where) as $column) {
            $conditions[] = $column . ' = :w_' . $column;
        }
        $params = [];
        foreach ($data as $column => $value) {
            $params['s_' . $column] = $value;
        }
        foreach ($where as $column => $value) {
            $params['w_' . $column] = $value;
        }
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            self::table($table),
            implode(', ', $set),
            implode(' AND ', $conditions)
        );
        return self::exec($sql, $params);
    }

    /** TRUE si une transaction est en cours. */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Indique si la base est en SQLite (mode démonstration). */
    public static function isSqlite(): bool
    {
        self::pdo();
        return self::$driver === 'sqlite';
    }

    /**
     * Date au format Y-m-d, portable MySQL/SQLite.
     */
    public static function today(): string
    {
        return date('Y-m-d');
    }

    /**
     * Traduction des expressions MySQL vers SQLite.
     * Les expressions traduites sont écrites par nos soins dans les modèles
     * (aucune donnée utilisateur n'est injectée dans le SQL).
     */
    private static function dialect(string $sql): string
    {
        if (self::$driver !== 'sqlite') {
            return $sql;
        }

        // 1) DATE_SUB / DATE_ADD avec arguments imbriqués : traité en premier,
        //    avant la traduction de NOW() / CURDATE().
        $sql = preg_replace_callback(
            '#DATE_(SUB|ADD)\(\s*(.*?)\s*,\s*INTERVAL\s+(\d+)\s+(MINUTE|HOUR|DAY|MONTH)\s*\)#i',
            static function (array $m): string {
                $sign = strtoupper($m[1]) === 'SUB' ? '-' : '+';
                $inner = $m[2];
                $inner = strtr($inner, [
                    'NOW()'     => "datetime('now','localtime')",
                    'CURDATE()' => "date('now','localtime')",
                ]);
                if (preg_match('#^[a-z_.]+$#i', $inner) === 1) {
                    // Colonne : les dates sont stockées en 'Y-m-d H:i:s' → datetime() les comprend.
                    $inner = "datetime(" . $inner . ")";
                }
                return sprintf("datetime(%s, '%s%d %s')", $inner, $sign, (int) $m[3], strtolower($m[4]));
            },
            $sql
        ) ?? $sql;

        // 2) Fonctions simples.
        $sql = strtr($sql, [
            'NOW()'        => "datetime('now','localtime')",
            'CURDATE()'    => "date('now','localtime')",
            'CURRENT_DATE' => "date('now','localtime')",
        ]);

        // 3) YEARWEEK(colonne, 1) → numéro de semaine (approximation suffisante pour les graphes).
        $sql = preg_replace_callback(
            '#YEARWEEK\(\s*([a-z_.]+)\s*,\s*1\s*\)#i',
            static fn (array $m): string => "strftime('%Y-%W', " . $m[1] . ')',
            $sql
        ) ?? $sql;

        return $sql;
    }
}
