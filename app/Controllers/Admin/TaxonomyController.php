<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Http;
use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Sector;
use App\Models\Service;
use App\Models\Stage;

/**
 * Gestion des secteurs, des étapes de suivi et des services.
 * Les trois listes partagent le même écran d'édition (ajout, modification inline, ordre, activation).
 */
final class TaxonomyController extends AdminController
{
    /** Configuration des trois taxonomies. */
    private const KINDS = [
        'secteurs' => [
            'model'   => Sector::class,
            'label'   => 'Secteurs',
            'single'  => 'secteur',
            'nav'     => 'sectors',
            'view'    => 'admin/taxonomy/sectors',
            'options' => 'SECTOR_ICONS',
        ],
        'etapes' => [
            'model'   => Stage::class,
            'label'   => 'Étapes de suivi',
            'single'  => 'étape',
            'nav'     => 'stages',
            'view'    => 'admin/taxonomy/stages',
            'options' => null,
        ],
        'services' => [
            'model'   => Service::class,
            'label'   => 'Services',
            'single'  => 'service',
            'nav'     => 'services',
            'view'    => 'admin/taxonomy/services',
            'options' => 'SERVICE_ICONS',
        ],
    ];

    /** Liste des secteurs. */
    public function sectors(): void
    {
        $this->listKind('secteurs');
    }

    /** Liste des étapes. */
    public function stages(): void
    {
        $this->listKind('etapes');
    }

    /** Liste des services. */
    public function services(): void
    {
        $this->listKind('services');
    }

    /** Ajout. */
    public function store(string $kind): void
    {
        Http::verifyCsrf();
        $config = $this->kind($kind);
        $data = $this->validate($kind);

        if ($data === null) {
            $this->redirect('/admin/' . $kind);
        }
        /** @var array<string, mixed> $data */
        $model = $config['model'];
        $model::create($data + ['position' => $model::nextPosition()]);

        Session::flash('success', ucfirst($config['single']) . ' ajouté.');
        $this->redirect('/admin/' . $kind);
    }

    /** Modification. */
    public function update(string $kind, string $id): void
    {
        Http::verifyCsrf();
        $config = $this->kind($kind);
        $model = $config['model'];
        $item = $model::find((int) $id);
        if ($item === null) {
            $this->failure('/admin/' . $kind, 'Cet élément n\'existe plus.');
        }

        $data = $this->validate($kind, (int) $item['id']);
        if ($data === null) {
            $this->redirect('/admin/' . $kind);
        }
        $model::update((int) $item['id'], $data);
        Session::flash('success', ucfirst($config['single']) . ' mis à jour.');
        $this->redirect('/admin/' . $kind);
    }

    /** Activation / désactivation. */
    public function toggle(string $kind, string $id): void
    {
        Http::verifyCsrf();
        $config = $this->kind($kind);
        $model = $config['model'];
        $item = $model::find((int) $id);
        if ($item === null) {
            $this->failure('/admin/' . $kind, 'Cet élément n\'existe plus.');
        }

        // Un secteur / service doit rester actif pour que le parcours reste utilisable.
        if ((int) $item['is_active'] === 1 && $model::count('is_active = 1') <= 1) {
            $this->failure('/admin/' . $kind, 'Au moins un élément actif est nécessaire pour le parcours d\'appel.');
        }

        $newState = (int) $item['is_active'] === 1 ? 0 : 1;
        $model::update((int) $item['id'], ['is_active' => $newState]);
        Session::flash('success', ucfirst($config['single']) . ($newState === 1 ? ' réactivé.' : ' désactivé (masqué côté commercial).'));
        $this->redirect('/admin/' . $kind);
    }

    /** Réordonnancement. */
    public function move(string $kind, string $id): void
    {
        Http::verifyCsrf();
        $config = $this->kind($kind);
        $model = $config['model'];
        $model::move((int) $id, Http::text('direction', 10) === 'up' ? -1 : 1);
        $this->redirect('/admin/' . $kind);
    }

    /** Suppression (refusée si l'élément est utilisé par des appels ou du contenu). */
    public function destroy(string $kind, string $id): void
    {
        Http::verifyCsrf();
        $config = $this->kind($kind);
        $model = $config['model'];
        $item = $model::find((int) $id);
        if ($item === null) {
            $this->failure('/admin/' . $kind, 'Cet élément n\'existe plus.');
        }

        if ($model::count('is_active = 1') <= 1 && (int) $item['is_active'] === 1) {
            $this->failure('/admin/' . $kind, 'Impossible : il doit rester au moins un élément actif.');
        }

        $usage = $this->usage($kind, (int) $item['id']);
        if ($usage > 0) {
            $this->failure(
                '/admin/' . $kind,
                sprintf(
                    'Suppression impossible : ce %s est utilisé dans %d contenu(s) ou appel(s). Désactivez-le à la place.',
                    $config['single'],
                    $usage
                )
            );
        }

        $model::delete((int) $item['id']);
        Session::flash('success', ucfirst($config['single']) . ' supprimé.');
        $this->redirect('/admin/' . $kind);
    }

    // ------------------------------------------------------------------ Helpers

    /** Affiche la liste d'une taxonomie. */
    private function listKind(string $kind): void
    {
        $config = $this->kind($kind);
        $model = $config['model'];

        $this->adminView($config['view'], [
            'title'    => $config['label'],
            'items'    => $model::all(),
            'single'   => $config['single'],
            'kind'     => $kind,
            'icons'    => $config['options'] ? constant(Icon::class . '::' . $config['options']) : null,
            'errors'   => $this->takeErrors(),
            'old'      => $this->takeOld(),
        ], $config['nav']);
    }

    /**
     * Valide les champs communs (nom, description, icône, couleur, prix).
     *
     * @return array<string, mixed>|null
     */
    private function validate(string $kind, ?int $ignoreId = null): ?array
    {
        $config = $this->kind($kind);
        $model = $config['model'];

        $name = Http::text('name', 120);
        $description = Http::text('description', 255);
        $icon = Http::text('icon', 40);
        $price = Http::text('price_from', 20);

        $validator = new Validator(['name' => 'Le nom', 'description' => 'La description']);
        $validator->required('name', $name)->maxLength('name', $name, 120)->maxLength('description', $description, 255);

        if ($config['options'] === 'SECTOR_ICONS' && !in_array($icon, Icon::SECTOR_ICONS, true)) {
            $icon = 'building';
        }
        if ($config['options'] === 'SERVICE_ICONS' && !in_array($icon, Icon::SERVICE_ICONS, true)) {
            $icon = 'globe';
        }

        $priceFrom = null;
        if ($kind === 'services') {
            $validator->optionalPrice('price_from', $price);
            $priceFrom = $price === '' ? null : Sanitizer::price($price);
        }

        if ($validator->fails()) {
            Session::set('_errors', $validator->errors());
            Session::setOld([
                'name'        => $name,
                'description' => $description,
                'icon'        => $icon,
                'price_from'  => $price,
            ]);
            return null;
        }

        // Slug unique (utilisé dans les URL et l'export).
        $baseSlug = $this->slug($name);
        $slug = $baseSlug;
        $suffix = 2;
        while ($this->slugExists($model, $slug, $ignoreId)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
            if ($suffix > 50) {
                break;
            }
        }

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
        ];
        if ($config['options'] !== null) {
            $data['icon'] = $icon;
        }
        if ($kind === 'services') {
            $data['price_from'] = $priceFrom;
        }

        return $data;
    }

    /** Slug URL simple. */
    private function slug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = strtr($slug, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        return $slug !== '' ? substr($slug, 0, 100) : 'element';
    }

    /** Vrai si le slug est déjà pris. */
    private function slugExists(string $model, string $slug, ?int $ignoreId): bool
    {
        $row = \App\Core\Db::one(
            sprintf('SELECT id FROM %s WHERE slug = ? LIMIT 1', \App\Core\Db::table($model::tableName())),
            [$slug]
        );
        if ($row === null) {
            return false;
        }
        return $ignoreId === null || (int) $row['id'] !== $ignoreId;
    }

    /** Nombre d'utilisations (contenus, objections, appels). */
    private function usage(string $kind, int $id): int
    {
        $column = match ($kind) {
            'secteurs' => 'sector_id',
            'etapes'   => 'stage_id',
            'services' => 'service_id',
            default    => 'sector_id',
        };
        $tables = $kind === 'etapes'
            ? [['speech_blocks', 'stage_id'], ['calls', 'stage_id']]
            : [
                ['speech_blocks', $column],
                ['objections', $column],
                ['calls', $column],
            ];

        $total = 0;
        foreach ($tables as [$table, $col]) {
            $total += (int) \App\Core\Db::value(
                sprintf('SELECT COUNT(*) FROM %s WHERE %s = ?', \App\Core\Db::table($table), $col),
                [$id],
                0
            );
        }
        return $total;
    }

    /**
     * @return array<string, mixed>
     */
    private function kind(string $kind): array
    {
        if (!isset(self::KINDS[$kind])) {
            Http::redirect('/admin');
        }
        return self::KINDS[$kind];
    }
}
