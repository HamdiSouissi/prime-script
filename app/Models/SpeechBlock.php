<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Sanitizer;

/**
 * Blocs de speech modulaires.
 *
 * Un bloc est lié (ou non) à un secteur, une étape et un service. Un lien vide
 * signifie « s'applique à tous ». La sélection d'un écran prend d'abord les blocs
 * les plus spécifiques, puis complète avec les blocs génériques.
 */
final class SpeechBlock extends Model
{
    protected static string $table = 'speech_blocks';

    /** Types de blocs et libellés affichés (ordre = ordre du parcours d'appel). */
    public const TYPES = [
        'presentation' => 'Présentation',
        'accroche'     => 'Accroche',
        'decouverte'   => 'Questions de découverte',
        'argumentaire' => 'Argumentaire',
        'preuve'       => 'Preuve / réalisation',
        'tarif'        => 'Tarif & prochaine étape',
        'appel_action' => 'Appel à l\'action',
        'objection'    => 'Réponse à objection',
    ];

    /** Types réellement utilisés dans les écrans du parcours. */
    public const CALL_TYPES = ['presentation', 'accroche', 'decouverte', 'argumentaire', 'preuve', 'tarif', 'appel_action'];

    /** Icône associée à chaque type (clé d'icône SVG interne). */
    public const TYPE_ICONS = [
        'presentation' => 'sparkles',
        'accroche'     => 'bolt',
        'decouverte'   => 'search',
        'argumentaire' => 'target',
        'preuve'       => 'award',
        'tarif'        => 'tag',
        'appel_action' => 'flag',
        'objection'    => 'shield',
    ];

    /** Récupère un bloc avec les noms de son secteur / étape / service. */
    public static function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        return Db::one(
            'SELECT b.*, s.name AS sector_name, st.name AS stage_name, sv.name AS service_name
             FROM speech_blocks b
             LEFT JOIN sectors s   ON s.id  = b.sector_id
             LEFT JOIN stages st   ON st.id = b.stage_id
             LEFT JOIN services sv ON sv.id = b.service_id
             WHERE b.id = ? LIMIT 1',
            [$id]
        );
    }

    /** Libellé d'un type. */
    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? ucfirst($type);
    }

    /**
     * Tous les blocs avec le nom de leur secteur / étape / service.
     *
     * @param array{type?: string, sector_id?: int, stage_id?: int, service_id?: int, active?: string, q?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function search(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['type']) && isset(self::TYPES[$filters['type']])) {
            $where[] = 'b.type = ?';
            $params[] = $filters['type'];
        }
        // Un filtre sur « Générique » (valeur 'none') doit renvoyer les blocs sans lien.
        if (!empty($filters['sector_id']) && $filters['sector_id'] === 'none') {
            $where[] = 'b.sector_id IS NULL';
        } elseif (!empty($filters['sector_id'])) {
            $where[] = 'b.sector_id = ?';
            $params[] = (int) $filters['sector_id'];
        }
        if (!empty($filters['stage_id']) && $filters['stage_id'] === 'none') {
            $where[] = 'b.stage_id IS NULL';
        } elseif (!empty($filters['stage_id'])) {
            $where[] = 'b.stage_id = ?';
            $params[] = (int) $filters['stage_id'];
        }
        if (!empty($filters['service_id']) && $filters['service_id'] === 'none') {
            $where[] = 'b.service_id IS NULL';
        } elseif (!empty($filters['service_id'])) {
            $where[] = 'b.service_id = ?';
            $params[] = (int) $filters['service_id'];
        }
        if (($filters['active'] ?? '') === '1' || ($filters['active'] ?? '') === '0') {
            $where[] = 'b.is_active = ?';
            $params[] = (int) $filters['active'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(b.title LIKE ? OR b.content LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT b.*, s.name AS sector_name, st.name AS stage_name, sv.name AS service_name,
                       u.name AS updated_by_name
                FROM speech_blocks b
                LEFT JOIN sectors s  ON s.id  = b.sector_id
                LEFT JOIN stages st  ON st.id = b.stage_id
                LEFT JOIN services sv ON sv.id = b.service_id
                LEFT JOIN users u    ON u.id  = b.updated_by
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY FIELD_IGNORE(b.type), b.position ASC, b.id ASC';

        // FIELD(...) n'existe pas partout : tri par type en PHP pour rester portable.
        $sql = str_replace('ORDER BY FIELD_IGNORE(b.type), b.position ASC, b.id ASC', 'ORDER BY b.position ASC, b.id ASC', $sql);

        $rows = Db::all($sql, $params);
        $order = array_flip(array_keys(self::TYPES));
        usort($rows, static function (array $a, array $b) use ($order): int {
            $oa = $order[$a['type']] ?? 99;
            $ob = $order[$b['type']] ?? 99;
            return [$oa, (int) $a['position'], (int) $a['id']] <=> [$ob, (int) $b['position'], (int) $b['id']];
        });
        return $rows;
    }

    /**
     * Blocs applicables à un contexte donné, du plus spécifique au plus générique.
     *
     * Score de spécificité : somme des liens renseignés qui correspondent au contexte
     * (secteur = 4, service = 2, étape = 1) ; les blocs vraiment génériques arrivent en dernier.
     *
     * @param array<int, string> $types
     * @return array<int, array<string, mixed>>
     */
    public static function forContext(array $types, ?int $sectorId, ?int $stageId, ?int $serviceId = null, bool $allowGenericBelowSpecific = true): array
    {
        $types = array_values(array_filter($types, static fn (string $t): bool => isset(self::TYPES[$t])));
        if ($types === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($types), '?'));
        $sql = "SELECT b.*, s.name AS sector_name, st.name AS stage_name, sv.name AS service_name
                FROM speech_blocks b
                LEFT JOIN sectors s   ON s.id  = b.sector_id
                LEFT JOIN stages st   ON st.id = b.stage_id
                LEFT JOIN services sv ON sv.id = b.service_id
                WHERE b.is_active = 1
                  AND b.type IN ($placeholders)
                  AND (b.sector_id IS NULL  OR b.sector_id  = ?)
                  AND (b.stage_id IS NULL   OR b.stage_id   = ?)
                  AND (b.service_id IS NULL OR b.service_id = ?)
                ORDER BY b.position ASC, b.id ASC";

        $params = array_merge($types, [$sectorId ?? 0, $stageId ?? 0, $serviceId ?? 0]);
        $rows = Db::all($sql, $params);

        // Regroupement par type en conservant l'ordre des types demandé.
        $grouped = [];
        foreach ($rows as $row) {
            $type = (string) $row['type'];
            $specificity = 0;
            if ($row['sector_id'] !== null && (int) $row['sector_id'] === $sectorId) {
                $specificity += 4;
            }
            if ($row['service_id'] !== null && (int) $row['service_id'] === $serviceId) {
                $specificity += 2;
            }
            if ($row['stage_id'] !== null && (int) $row['stage_id'] === $stageId) {
                $specificity += 1;
            }
            $row['_specificity'] = $specificity;
            $grouped[$type][] = $row;
        }

        $selection = [];
        foreach ($types as $type) {
            $candidates = $grouped[$type] ?? [];
            if ($candidates === []) {
                continue;
            }
            usort($candidates, static fn (array $a, array $b): int =>
                [(int) $b['_specificity'], (int) $a['position'], (int) $a['id']]
                <=> [(int) $a['_specificity'], (int) $b['position'], (int) $b['id']]
            );

            $specific = array_values(array_filter($candidates, static fn (array $r): bool => (int) $r['_specificity'] > 0));
            $generic  = array_values(array_filter($candidates, static fn (array $r): bool => (int) $r['_specificity'] === 0));

            if ($specific === []) {
                $selection[$type] = $generic;
            } elseif ($allowGenericBelowSpecific) {
                $selection[$type] = array_merge($specific, $generic);
            } else {
                $selection[$type] = $specific;
            }
        }
        return $selection;
    }

    /**
     * Blocs d'un type donné, triés, consultables par l'administration.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function byType(string $type, bool $onlyActive = false): array
    {
        return Db::all(
            'SELECT * FROM speech_blocks WHERE type = ?' . ($onlyActive ? ' AND is_active = 1' : '') . ' ORDER BY position ASC, id ASC',
            [$type]
        );
    }

    /** Enregistre une version dans l'historique (avant modification ou à la création). */
    public static function snapshot(array $block, ?int $userId, string $note = ''): void
    {
        $version = (int) Db::value(
            'SELECT COALESCE(MAX(version_no), 0) + 1 FROM speech_block_versions WHERE block_id = ?',
            [(int) $block['id']],
            1
        );
        Db::insert('speech_block_versions', [
            'block_id'    => (int) $block['id'],
            'version_no'  => $version,
            'type'        => (string) $block['type'],
            'sector_id'   => $block['sector_id'] !== null ? (int) $block['sector_id'] : null,
            'stage_id'    => $block['stage_id'] !== null ? (int) $block['stage_id'] : null,
            'service_id'  => $block['service_id'] !== null ? (int) $block['service_id'] : null,
            'title'       => (string) $block['title'],
            'content'     => (string) $block['content'],
            'position'    => (int) $block['position'],
            'is_active'   => (int) $block['is_active'],
            'change_note' => Sanitizer::limit($note, 255),
            'created_by'  => $userId,
        ]);
    }

    /**
     * Historique des versions d'un bloc (la plus récente d'abord).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function versions(int $blockId): array
    {
        return Db::all(
            'SELECT v.*, u.name AS author_name
             FROM speech_block_versions v
             LEFT JOIN users u ON u.id = v.created_by
             WHERE v.block_id = ?
             ORDER BY v.version_no DESC',
            [$blockId]
        );
    }

    /** Récupère une version précise d'un bloc. */
    public static function version(int $versionId, int $blockId): ?array
    {
        return Db::one(
            'SELECT * FROM speech_block_versions WHERE id = ? AND block_id = ?',
            [$versionId, $blockId]
        );
    }

    /** Duplique un bloc (titre suffixé), en conservant les liens. */
    public static function duplicate(int $id, ?int $userId): ?int
    {
        $block = self::find($id);
        if ($block === null) {
            return null;
        }
        $copy = [
            'type'       => $block['type'],
            'sector_id'  => $block['sector_id'],
            'stage_id'   => $block['stage_id'],
            'service_id' => $block['service_id'],
            'title'      => Sanitizer::limit((string) $block['title'] . ' (copie)', 160),
            'content'    => $block['content'],
            'position'   => self::nextPosition(),
            'is_active'  => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
        $newId = self::create($copy);
        $new = self::find($newId);
        if ($new !== null) {
            self::snapshot($new, $userId, 'Copie de « ' . $block['title'] . ' »');
        }
        return $newId;
    }
}
