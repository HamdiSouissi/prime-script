<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * Statistiques du tableau de bord administrateur.
 * Toutes les requêtes sont préparées ; aucune donnée utilisateur n'est concaténée.
 */
final class Stats
{
    /** Compteurs de période (jour / semaine / mois). */
    public static function counters(): array
    {
        $row = Db::one(
            "SELECT
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS week,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS month,
                COUNT(*) AS total,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND result = 'rdv' THEN 1 ELSE 0 END) AS rdv_month,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND result = 'devis' THEN 1 ELSE 0 END) AS devis_month
             FROM calls"
        ) ?? [];

        $month = (int) ($row['month'] ?? 0);
        $positive = (int) ($row['rdv_month'] ?? 0) + (int) ($row['devis_month'] ?? 0);

        return [
            'today'         => (int) ($row['today'] ?? 0),
            'week'          => (int) ($row['week'] ?? 0),
            'month'         => (int) ($row['month'] ?? 0),
            'total'         => (int) ($row['total'] ?? 0),
            'rdv_month'     => (int) ($row['rdv_month'] ?? 0),
            'devis_month'   => (int) ($row['devis_month'] ?? 0),
            'conversion'    => $month > 0 ? (int) round($positive / $month * 100) : 0,
            'rdv_rate'      => $month > 0 ? (int) round(((int) ($row['rdv_month'] ?? 0)) / $month * 100) : 0,
        ];
    }

    /**
     * Répartition des résultats sur une période (30 jours par défaut).
     *
     * @return array<int, array{result: string, label: string, total: int, tone: string}>
     */
    public static function byResult(int $days = 30): array
    {
        $rows = Db::all(
            'SELECT result, COUNT(*) AS total FROM calls
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, min(365, $days)) . ' DAY)
             GROUP BY result'
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['result']] = (int) $row['total'];
        }
        $out = [];
        $total = array_sum($counts);
        foreach (Call::results() as $key => $label) {
            $value = $counts[$key] ?? 0;
            $out[] = [
                'result'   => $key,
                'label'    => $label,
                'total'    => $value,
                'tone'     => Call::resultTone($key),
                'share'    => $total > 0 ? (int) round($value / $total * 100) : 0,
            ];
        }
        return $out;
    }

    /**
     * Appels par commercial (30 jours).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byUser(int $days = 30): array
    {
        return Db::all(
            'SELECT u.id, u.name, u.role, u.is_active,
                    COUNT(c.id) AS total,
                    SUM(CASE WHEN c.result = \'rdv\' THEN 1 ELSE 0 END) AS rdv,
                    SUM(CASE WHEN c.result = \'devis\' THEN 1 ELSE 0 END) AS devis
             FROM users u
             LEFT JOIN calls c ON c.user_id = u.id
                  AND c.created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, min(365, $days)) . ' DAY)
             GROUP BY u.id, u.name, u.role, u.is_active
             ORDER BY total DESC, u.name ASC'
        );
    }

    /**
     * Services les plus utilisés.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byService(int $days = 30): array
    {
        // « s.position » est agrégé (MIN) et non nu : MySQL 5.7+ active
        // ONLY_FULL_GROUP_BY par défaut et rejetait « ORDER BY s.position »,
        // ce qui faisait échouer tout le tableau de bord en production.
        return Db::all(
            'SELECT s.id, s.name, s.icon, s.price_from, MIN(s.position) AS position, COUNT(c.id) AS total
             FROM services s
             LEFT JOIN calls c ON c.service_id = s.id
                  AND c.created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, min(365, $days)) . ' DAY)
             GROUP BY s.id, s.name, s.icon, s.price_from
             ORDER BY total DESC, position ASC'
        );
    }

    /**
     * Secteurs les plus utilisés.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function bySector(int $days = 30): array
    {
        // Même précaution que byService() : MIN(s.position) puis tri sur l'alias.
        return Db::all(
            'SELECT s.id, s.name, s.icon, MIN(s.position) AS position, COUNT(c.id) AS total
             FROM sectors s
             LEFT JOIN calls c ON c.sector_id = s.id
                  AND c.created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, min(365, $days)) . ' DAY)
             GROUP BY s.id, s.name, s.icon
             ORDER BY total DESC, position ASC'
        );
    }

    /**
     * Appels des 7 derniers jours, jour par jour (graphe en colonnes).
     *
     * @return array<int, array{date: string, label: string, total: int}>
     */
    public static function lastSevenDays(): array
    {
        $rows = Db::all(
            'SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM calls
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)'
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['day']] = (int) $row['total'];
        }

        $days = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' day'));
            $weekday = (int) date('w', strtotime($date));
            $out[] = [
                'date'  => $date,
                'label' => $days[$weekday],
                'short' => date('d/m', strtotime($date)),
                'total' => $counts[$date] ?? 0,
            ];
        }
        return $out;
    }

    /** Nombre de contenus (blocs, objections, taxonomies). */
    public static function contentCounters(): array
    {
        return [
            'blocks'        => (int) Db::value('SELECT COUNT(*) FROM speech_blocks', [], 0),
            'blocks_active' => (int) Db::value('SELECT COUNT(*) FROM speech_blocks WHERE is_active = 1', [], 0),
            'objections'    => (int) Db::value('SELECT COUNT(*) FROM objections', [], 0),
            'sectors'       => (int) Db::value('SELECT COUNT(*) FROM sectors', [], 0),
            'stages'        => (int) Db::value('SELECT COUNT(*) FROM stages', [], 0),
            'services'      => (int) Db::value('SELECT COUNT(*) FROM services', [], 0),
            'users'         => (int) Db::value('SELECT COUNT(*) FROM users WHERE is_active = 1', [], 0),
        ];
    }

    /**
     * Blocs par type (pour repérer les trous de contenu).
     *
     * @return array<string, array{total: int, active: int, generic: int}>
     */
    public static function blocksByType(): array
    {
        $rows = Db::all(
            'SELECT type,
                    COUNT(*) AS total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN sector_id IS NULL AND stage_id IS NULL AND service_id IS NULL THEN 1 ELSE 0 END) AS generic
             FROM speech_blocks GROUP BY type'
        );
        $out = [];
        foreach (SpeechBlock::TYPES as $type => $label) {
            $out[$type] = ['total' => 0, 'active' => 0, 'generic' => 0];
        }
        foreach ($rows as $row) {
            $out[(string) $row['type']] = [
                'total'   => (int) $row['total'],
                'active'  => (int) $row['active'],
                'generic' => (int) $row['generic'],
            ];
        }
        return $out;
    }
}
