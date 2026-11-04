<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * Objections fréquentes et réponses recommandées.
 * Une objection peut être générale, ou liée à un service et/ou un secteur.
 */
final class Objection extends Model
{
    protected static string $table = 'objections';

    /**
     * Objections actives pour un contexte, les plus spécifiques d'abord.
     *
     * @param array<int, string> $typeFilter
     * @return array<int, array<string, mixed>>
     */
    public static function forContext(?int $sectorId = null, ?int $serviceId = null): array
    {
        $rows = Db::all(
            'SELECT o.*, s.name AS sector_name, sv.name AS service_name
             FROM objections o
             LEFT JOIN sectors s   ON s.id  = o.sector_id
             LEFT JOIN services sv ON sv.id = o.service_id
             WHERE o.is_active = 1
               AND (o.sector_id IS NULL  OR o.sector_id  = ?)
               AND (o.service_id IS NULL OR o.service_id = ?)
             ORDER BY o.position ASC, o.id ASC',
            [$sectorId ?? 0, $serviceId ?? 0]
        );

        foreach ($rows as &$row) {
            $score = 0;
            if ($row['sector_id'] !== null && (int) $row['sector_id'] === $sectorId) {
                $score += 2;
            }
            if ($row['service_id'] !== null && (int) $row['service_id'] === $serviceId) {
                $score += 1;
            }
            $row['_specificity'] = $score;
            $row['_scope'] = match (true) {
                $score === 3 => 'service + secteur',
                $score === 2 => 'secteur',
                $score === 1 => 'service',
                default      => 'générale',
            };
        }
        unset($row);

        usort($rows, static fn (array $a, array $b): int =>
            [(int) $b['_specificity'], (int) $a['position'], (int) $a['id']]
            <=> [(int) $a['_specificity'], (int) $b['position'], (int) $b['id']]
        );
        return $rows;
    }

    /**
     * Liste complète pour l'administration.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function withRelations(?string $active = null, ?string $serviceId = null, ?string $sectorId = null, string $q = ''): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($active === '1' || $active === '0') {
            $where[] = 'o.is_active = ?';
            $params[] = (int) $active;
        }
        if ($serviceId === 'none') {
            $where[] = 'o.service_id IS NULL';
        } elseif (!empty($serviceId)) {
            $where[] = 'o.service_id = ?';
            $params[] = (int) $serviceId;
        }
        if ($sectorId === 'none') {
            $where[] = 'o.sector_id IS NULL';
        } elseif (!empty($sectorId)) {
            $where[] = 'o.sector_id = ?';
            $params[] = (int) $sectorId;
        }
        if ($q !== '') {
            $where[] = '(o.question LIKE ? OR o.answer LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        return Db::all(
            'SELECT o.*, s.name AS sector_name, sv.name AS service_name
             FROM objections o
             LEFT JOIN sectors s   ON s.id  = o.sector_id
             LEFT JOIN services sv ON sv.id = o.service_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY o.position ASC, o.id ASC',
            $params
        );
    }
}
