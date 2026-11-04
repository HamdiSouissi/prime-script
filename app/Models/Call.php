<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Db;

/**
 * Journal des appels commerciaux.
 */
final class Call extends Model
{
    protected static string $table = 'calls';

    /** Résultats possibles : clé => libellé. */
    public static function results(): array
    {
        $out = [];
        foreach ((array) Config::get('calls.results', []) as $key => $meta) {
            $out[(string) $key] = (string) ($meta['label'] ?? $key);
        }
        return $out;
    }

    /** Clés autorisées. */
    public static function resultKeys(): array
    {
        return array_keys(self::results());
    }

    /** Libellé d'un résultat. */
    public static function resultLabel(?string $key): string
    {
        return self::results()[(string) $key] ?? '—';
    }

    /** Ton visuel d'un résultat (success, info, warn, danger, muted). */
    public static function resultTone(?string $key): string
    {
        return (string) Config::get('calls.results.' . (string) $key . '.tone', 'muted');
    }

    /**
     * Enregistre un appel.
     *
     * @param array<string, mixed> $data
     */
    public static function record(array $data): int
    {
        return self::create([
            'user_id'           => (int) $data['user_id'],
            'sector_id'         => $data['sector_id'] ?: null,
            'stage_id'          => $data['stage_id'] ?: null,
            'service_id'        => $data['service_id'] ?: null,
            'entreprise_client' => (string) ($data['entreprise_client'] ?? ''),
            'contact'           => (string) ($data['contact'] ?? ''),
            'result'            => (string) $data['result'],
            'note'              => (string) ($data['note'] ?? ''),
            'callback_date'     => $data['callback_date'] ?: null,
        ]);
    }

    /**
     * Historique filtrable (commercial : uniquement ses appels).
     *
     * @param array{user_id?: int, result?: string, service_id?: string, sector_id?: string, from?: string, to?: string, q?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function search(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        [$where, $params] = self::buildFilters($filters);
        $sql = 'SELECT c.*, u.name AS user_name, s.name AS sector_name, st.name AS stage_name, sv.name AS service_name
                FROM calls c
                JOIN users u       ON u.id  = c.user_id
                LEFT JOIN sectors s ON s.id = c.sector_id
                LEFT JOIN stages st ON st.id = c.stage_id
                LEFT JOIN services sv ON sv.id = c.service_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY c.created_at DESC, c.id DESC
                LIMIT ' . max(1, min(1000, $limit)) . ' OFFSET ' . max(0, $offset);
        return Db::all($sql, $params);
    }

    /**
     * Nombre total de résultats pour un filtre (pagination).
     *
     * @param array<string, mixed> $filters
     */
    public static function countSearch(array $filters = []): int
    {
        [$where, $params] = self::buildFilters($filters);
        return (int) Db::value(
            'SELECT COUNT(*) FROM calls c JOIN users u ON u.id = c.user_id WHERE ' . implode(' AND ', $where),
            $params,
            0
        );
    }

    /**
     * Construit la clause WHERE des filtres.
     *
     * @param array<string, mixed> $filters
     * @return array{0: string[], 1: array<int, mixed>}
     */
    private static function buildFilters(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'c.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }
        if (!empty($filters['result']) && in_array($filters['result'], self::resultKeys(), true)) {
            $where[] = 'c.result = ?';
            $params[] = $filters['result'];
        }
        if (!empty($filters['service_id'])) {
            $where[] = 'c.service_id = ?';
            $params[] = (int) $filters['service_id'];
        }
        if (!empty($filters['sector_id'])) {
            $where[] = 'c.sector_id = ?';
            $params[] = (int) $filters['sector_id'];
        }
        $from = \App\Core\Sanitizer::date($filters['from'] ?? null);
        if ($from !== null) {
            $where[] = 'c.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        $to = \App\Core\Sanitizer::date($filters['to'] ?? null);
        if ($to !== null) {
            $where[] = 'c.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        if (!empty($filters['q'])) {
            $where[] = '(c.entreprise_client LIKE ? OR c.contact LIKE ? OR c.note LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        return [$where, $params];
    }

    /**
     * Appels récents de l'utilisateur (tableau de bord commercial).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recentForUser(int $userId, int $limit = 5): array
    {
        return Db::all(
            'SELECT c.*, s.name AS sector_name, sv.name AS service_name
             FROM calls c
             LEFT JOIN sectors s ON s.id = c.sector_id
             LEFT JOIN services sv ON sv.id = c.service_id
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC, c.id DESC
             LIMIT ' . max(1, min(50, $limit)),
            [$userId]
        );
    }

    /**
     * Statistiques personnelles (tableau de bord commercial).
     *
     * @return array{today: int, week: int, month: int, rdv_month: int, devis_month: int}
     */
    public static function statsForUser(int $userId): array
    {
        $row = Db::one(
            "SELECT
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS week,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS month,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND result = 'rdv' THEN 1 ELSE 0 END) AS rdv_month,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND result = 'devis' THEN 1 ELSE 0 END) AS devis_month
             FROM calls WHERE user_id = ?",
            [$userId]
        ) ?? [];

        return [
            'today'      => (int) ($row['today'] ?? 0),
            'week'       => (int) ($row['week'] ?? 0),
            'month'      => (int) ($row['month'] ?? 0),
            'rdv_month'  => (int) ($row['rdv_month'] ?? 0),
            'devis_month'=> (int) ($row['devis_month'] ?? 0),
        ];
    }

    /**
     * Rappels à venir / en retard pour un commercial.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function dueCallbacks(?int $userId = null, int $limit = 5): array
    {
        $params = [];
        $userWhere = '';
        if ($userId !== null) {
            $userWhere = ' AND c.user_id = ?';
            $params[] = $userId;
        }
        return Db::all(
            "SELECT c.*, s.name AS sector_name, sv.name AS service_name, u.name AS user_name
             FROM calls c
             LEFT JOIN sectors s ON s.id = c.sector_id
             LEFT JOIN services sv ON sv.id = c.service_id
             JOIN users u ON u.id = c.user_id
             WHERE c.callback_date IS NOT NULL
               AND c.callback_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               AND c.result IN ('rappeler', 'devis')
               $userWhere
             ORDER BY c.callback_date ASC, c.id DESC
             LIMIT " . max(1, min(50, $limit)),
            $params
        );
    }
}
