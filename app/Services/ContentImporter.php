<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use RuntimeException;

/**
 * Import du contenu prêt à l'emploi (database/content/*.sql).
 *
 * Utilisé par :
 *   - tools/import-content.php  (en ligne de commande, avant la mise en service) ;
 *   - l'administration (Contenus → Restaurer le contenu d'origine).
 *
 * Règles de sécurité :
 *   - jamais de suppression des comptes ni du journal d'appels ;
 *   - chaque élément porte un `code` unique : réimporter met à jour, sans doublon ;
 *   - les blocs créés à la main dans l'admin (sans code) ne sont jamais touchés ;
 *   - l'état précédent est archivé dans l'historique des versions.
 */
final class ContentImporter
{
    private string $directory;

    /** @var array<string, int> */
    private array $sectorIds = [];
    /** @var array<string, int> */
    private array $stageIds = [];
    /** @var array<string, int> */
    private array $serviceIds = [];

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? BASE_PATH . '/database/content';
        $this->loadTaxonomies();
    }

    private function loadTaxonomies(): void
    {
        foreach (Db::all('SELECT id, slug FROM sectors') as $row) {
            $this->sectorIds[(string) $row['slug']] = (int) $row['id'];
        }
        foreach (Db::all('SELECT id, slug FROM stages') as $row) {
            $this->stageIds[(string) $row['slug']] = (int) $row['id'];
        }
        foreach (Db::all('SELECT id, slug FROM services') as $row) {
            $this->serviceIds[(string) $row['slug']] = (int) $row['id'];
        }
    }

    /**
     * Lit tous les fichiers de contenu et vérifie qu'ils sont exploitables.
     *
     * @return array{blocks: array<int, array<string, mixed>>, objections: array<int, array<string, mixed>>}
     */
    public function load(): array
    {
        $files = glob($this->directory . '/*.sql') ?: [];
        sort($files);
        if ($files === []) {
            throw new RuntimeException('Aucun fichier de contenu dans database/content/.');
        }

        $blocks = [];
        $objections = [];
        foreach ($files as $file) {
            $handle = fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '' || $line[0] !== '{') {
                    continue;
                }
                /** @var array<string, mixed>|null $row */
                $row = json_decode($line, true);
                if (!is_array($row)) {
                    throw new RuntimeException('Ligne illisible dans ' . basename($file) . ' : ' . mb_substr($line, 0, 80));
                }
                $isObjection = isset($row['question'], $row['answer']);
                if ($isObjection) {
                    $row['code'] = isset($row['code']) ? (string) $row['code'] : self::codeFromQuestion((string) $row['question']);
                    $row['sector_id'] = $this->resolve('sectors', $row['sector'] ?? null, $this->sectorIds, basename($file), $row['code']);
                    $row['service_id'] = $this->resolve('services', $row['service'] ?? null, $this->serviceIds, basename($file), $row['code']);
                    $objections[] = $row;
                    continue;
                }
                foreach (['code', 'type', 'title', 'content'] as $required) {
                    if (!isset($row[$required]) || trim((string) $row[$required]) === '') {
                        throw new RuntimeException(sprintf(
                            'Bloc incomplet dans %s : « %s » manque.',
                            basename($file),
                            $required
                        ));
                    }
                }
                $row['sector_id'] = $this->resolve('sectors', $row['sector'] ?? null, $this->sectorIds, basename($file), (string) $row['code']);
                $row['stage_id'] = $this->resolve('stages', $row['stage'] ?? null, $this->stageIds, basename($file), (string) $row['code']);
                $row['service_id'] = $this->resolve('services', $row['service'] ?? null, $this->serviceIds, basename($file), (string) $row['code']);
                $blocks[] = $row;
            }
            fclose($handle);
        }

        return ['blocks' => $blocks, 'objections' => $objections];
    }

    /**
     * @param array<string, int> $map
     */
    private function resolve(string $table, mixed $slug, array $map, string $file, string $code): ?int
    {
        if ($slug === null || $slug === '') {
            return null;
        }
        $slug = (string) $slug;
        if (!isset($map[$slug])) {
            throw new RuntimeException(sprintf(
                'Référence inconnue dans %s (%s) : %s « %s » introuvable.',
                $file,
                $code,
                rtrim($table, 's'),
                $slug
            ));
        }
        return $map[$slug];
    }

    /** Code stable dérivé d'une question d'objection. */
    public static function codeFromQuestion(string $question): string
    {
        $slug = mb_strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $question));
        return 'obj-' . mb_substr(trim($slug, '-'), 0, 78);
    }

    /**
     * Importe (ou met à jour) tout le contenu.
     *
     * @return array{blocks_created: int, blocks_updated: int, blocks_unchanged: int, objections_created: int, objections_updated: int, objections_unchanged: int}
     */
    public function import(?int $authorId = null): array
    {
        $data = $this->load();
        $this->ensureCodeColumns();

        if ($authorId === null) {
            $admin = Db::value('SELECT id FROM users WHERE role = ? ORDER BY id ASC LIMIT 1', ['admin']);
            $authorId = $admin !== null ? (int) $admin : null;
        }

        $stats = [
            'blocks_created' => 0, 'blocks_updated' => 0, 'blocks_unchanged' => 0,
            'objections_created' => 0, 'objections_updated' => 0, 'objections_unchanged' => 0,
        ];

        $positions = [];
        foreach ($data['blocks'] as $block) {
            $type = (string) $block['type'];
            $positions[$type] = ($positions[$type] ?? 0) + 10;
            $row = [
                'code'       => (string) $block['code'],
                'type'       => $type,
                'sector_id'  => $block['sector_id'],
                'stage_id'   => $block['stage_id'],
                'service_id' => $block['service_id'],
                'title'      => (string) $block['title'],
                'content'    => (string) $block['content'],
                'position'   => isset($block['position']) ? (int) $block['position'] : $positions[$type],
                'is_active'  => (int) ($block['active'] ?? 1),
                'updated_by' => $authorId,
            ];

            $existing = Db::one('SELECT * FROM speech_blocks WHERE code = ? LIMIT 1', [$row['code']]);
            if ($existing === null) {
                $row['created_by'] = $authorId;
                $newId = Db::insert('speech_blocks', $row);
                $fresh = Db::one('SELECT * FROM speech_blocks WHERE id = ?', [$newId]);
                if ($fresh !== null) {
                    Db::insert('speech_block_versions', [
                        'block_id'    => (int) $newId,
                        'version_no'  => 1,
                        'type'        => (string) $fresh['type'],
                        'sector_id'   => $fresh['sector_id'],
                        'stage_id'    => $fresh['stage_id'],
                        'service_id'  => $fresh['service_id'],
                        'title'       => (string) $fresh['title'],
                        'content'     => (string) $fresh['content'],
                        'position'    => (int) $fresh['position'],
                        'is_active'   => (int) $fresh['is_active'],
                        'change_note' => 'Import du contenu prêt à l\'emploi',
                        'created_by'  => $authorId,
                    ]);
                }
                $stats['blocks_created']++;
                continue;
            }

            if ($this->sameBlock($existing, $row)) {
                $stats['blocks_unchanged']++;
                continue;
            }

            $nextNo = (int) Db::value(
                'SELECT COALESCE(MAX(version_no), 0) + 1 FROM speech_block_versions WHERE block_id = ?',
                [(int) $existing['id']],
                1
            );
            Db::insert('speech_block_versions', [
                'block_id'    => (int) $existing['id'],
                'version_no'  => $nextNo,
                'type'        => (string) $existing['type'],
                'sector_id'   => $existing['sector_id'],
                'stage_id'    => $existing['stage_id'],
                'service_id'  => $existing['service_id'],
                'title'       => (string) $existing['title'],
                'content'     => (string) $existing['content'],
                'position'    => (int) $existing['position'],
                'is_active'   => (int) $existing['is_active'],
                'change_note' => 'Avant réimport du contenu',
                'created_by'  => $authorId,
            ]);
            Db::update('speech_blocks', $row, ['id' => (int) $existing['id']]);
            $stats['blocks_updated']++;
        }

        foreach ($data['objections'] as $index => $objection) {
            $row = [
                'code'       => (string) $objection['code'],
                'question'   => (string) $objection['question'],
                'answer'     => (string) $objection['answer'],
                'service_id' => $objection['service_id'],
                'sector_id'  => $objection['sector_id'],
                'position'   => (int) ($objection['position'] ?? ($index + 1) * 10),
                'is_active'  => (int) ($objection['active'] ?? 1),
            ];
            $existing = Db::one('SELECT * FROM objections WHERE code = ? LIMIT 1', [$row['code']]);
            if ($existing === null) {
                Db::insert('objections', $row);
                $stats['objections_created']++;
                continue;
            }
            if ((string) $existing['answer'] === $row['answer'] && (string) $existing['question'] === $row['question']) {
                $stats['objections_unchanged']++;
                continue;
            }
            Db::update('objections', $row, ['id' => (int) $existing['id']]);
            $stats['objections_updated']++;
        }

        return $stats;
    }

    /** Vrai si le contenu en base est déjà identique (aucune écriture inutile). */
    private function sameBlock(array $existing, array $row): bool
    {
        return (string) $existing['title'] === $row['title']
            && (string) $existing['content'] === $row['content']
            && (string) $existing['type'] === $row['type']
            && (int) $existing['is_active'] === $row['is_active']
            && (int) $existing['position'] === $row['position']
            && (int) ($existing['sector_id'] ?? 0) === (int) ($row['sector_id'] ?? 0)
            && (int) ($existing['stage_id'] ?? 0) === (int) ($row['stage_id'] ?? 0)
            && (int) ($existing['service_id'] ?? 0) === (int) ($row['service_id'] ?? 0);
    }

    /** Crée les colonnes de suivi si la base a été installée avant ce lot. */
    public function ensureCodeColumns(): void
    {
        $driver = \App\Core\Config::get('database.driver', 'sqlite');
        if ($driver === 'sqlite') {
            foreach ([['speech_blocks', 'code'], ['objections', 'code']] as [$table, $column]) {
                $exists = (int) Db::value(
                    "SELECT COUNT(*) FROM pragma_table_info(?) WHERE name = ?",
                    [$table, $column],
                    0
                );
                if ($exists === 0) {
                    Db::run(sprintf('ALTER TABLE %s ADD COLUMN %s TEXT NULL', $table, $column));
                }
            }
            return;
        }

        $definitions = [
            ['speech_blocks', 'code', 'VARCHAR(80) NULL DEFAULT NULL AFTER type'],
            ['objections', 'code', 'VARCHAR(120) NULL DEFAULT NULL AFTER id'],
        ];
        foreach ($definitions as [$table, $column, $definition]) {
            $exists = (int) Db::value(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column],
                0
            );
            if ($exists === 0) {
                Db::run(sprintf('ALTER TABLE %s ADD COLUMN %s', $table, $definition));
            }
        }
    }

    /** Retire uniquement le contenu importé (blocs et objections portant un code). */
    public function purge(): int
    {
        $removed = Db::run('DELETE FROM speech_blocks WHERE code IS NOT NULL')->rowCount();
        Db::run('DELETE FROM objections WHERE code IS NOT NULL');
        return $removed;
    }

    /** Nombre de fichiers de contenu disponibles. */
    public function fileCount(): int
    {
        return count(glob($this->directory . '/*.sql') ?: []);
    }
}
