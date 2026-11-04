<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Sanitizer;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Objection;
use App\Models\Sector;
use App\Models\Service;
use App\Models\SpeechBlock;
use App\Models\Stage;
use App\Services\SpeechRenderer;

/**
 * Gestion des blocs de speech : liste filtrable, création, édition, aperçu,
 * duplication, activation, ordre et historique des versions.
 */
final class BlockController extends AdminController
{
    /** Liste filtrable. */
    public function index(): void
    {
        $filters = [
            'type'       => $this->queryList('type', array_keys(SpeechBlock::TYPES), ''),
            'sector_id'  => $this->filterId('sector_id'),
            'stage_id'   => $this->filterId('stage_id'),
            'service_id' => $this->filterId('service_id'),
            'active'     => $this->queryList('active', ['0', '1'], ''),
            'q'          => Http::text('q', 80),
        ];

        $blocks = SpeechBlock::search($filters);

        $this->adminView('admin/blocks/index', [
            'title'    => 'Blocs de speech',
            'blocks'   => $blocks,
            'filters'  => $filters,
            'taxonomy' => $this->taxonomyOptions(true),
            'stats'    => [
                'total'  => count($blocks),
                'active' => count(array_filter($blocks, static fn (array $b): bool => (int) $b['is_active'] === 1)),
            ],
        ], 'blocks');
    }

    /** Formulaire de création. */
    public function create(): void
    {
        $this->adminView('admin/blocks/form', [
            'title'    => 'Nouveau bloc',
            'block'    => [
                'id'         => 0,
                'type'       => (string) Http::query('type', 'decouverte'),
                'sector_id'  => null,
                'stage_id'   => null,
                'service_id' => null,
                'title'      => '',
                'content'    => '',
                'position'   => SpeechBlock::nextPosition(),
                'is_active'  => 1,
            ],
            'taxonomy' => $this->taxonomyOptions(true),
            'errors'   => $this->takeErrors(),
            'old'      => $this->takeOld(),
            'isNew'    => true,
        ], 'blocks');
    }

    /** Enregistre un nouveau bloc (+ version initiale). */
    public function store(): void
    {
        Http::verifyCsrf();
        $data = $this->validate();

        if ($data === null) {
            $this->redirect('/admin/blocs/nouveau');
        }

        /** @var array<string, mixed> $data */
        $data['created_by'] = (int) Auth::user()['id'];
        $data['updated_by'] = (int) Auth::user()['id'];
        $id = SpeechBlock::create($data);

        $block = SpeechBlock::find($id);
        if ($block !== null) {
            SpeechBlock::snapshot($block, (int) Auth::user()['id'], 'Création');
        }

        Session::flash('success', 'Bloc créé. Il est visible immédiatement côté commercial.');
        $this->redirect('/admin/blocs');
    }

    /** Formulaire de modification. */
    public function edit(string $id): void
    {
        $block = SpeechBlock::find((int) $id);
        if ($block === null) {
            $this->failure('/admin/blocs', 'Ce bloc n\'existe plus.');
        }

        $old = $this->takeOld();
        if ($old !== []) {
            $block = array_merge($block, $old);
        }

        $this->adminView('admin/blocks/form', [
            'title'    => 'Modifier le bloc',
            'block'    => $block,
            'taxonomy' => $this->taxonomyOptions(false),
            'errors'   => $this->takeErrors(),
            'old'      => [],
            'isNew'    => false,
            'versions' => SpeechBlock::versions((int) $block['id']),
        ], 'blocks');
    }

    /** Met à jour un bloc (version précédente archivée). */
    public function update(string $id): void
    {
        Http::verifyCsrf();
        $blockId = (int) $id;
        $block = SpeechBlock::find($blockId);
        if ($block === null) {
            $this->failure('/admin/blocs', 'Ce bloc n\'existe plus.');
        }

        $note = Http::text('change_note', 255);
        $data = $this->validate('/admin/blocs/' . $blockId . '/modifier');

        if ($data === null) {
            $this->redirect('/admin/blocs/' . $blockId . '/modifier');
        }

        /** @var array<string, mixed> $data */
        $data['updated_by'] = (int) Auth::user()['id'];

        // Historise l'état actuel avant écrasement.
        SpeechBlock::snapshot($block, (int) Auth::user()['id'], $note !== '' ? $note : 'Modification');

        SpeechBlock::update($blockId, $data);

        Session::flash('success', 'Bloc enregistré. La nouvelle version est en ligne.');
        $this->redirect('/admin/blocs/' . $blockId . '/modifier');
    }

    /** Aperçu tel que le verra le commercial (réponse JSON pour le volet d'aperçu). */
    public function preview(string $id): void
    {
        $block = SpeechBlock::find((int) $id);
        if ($block === null) {
            Http::json(['ok' => false, 'message' => 'Bloc introuvable.'], 404);
        }

        // Variables d'exemple : l'aperçu montre un cas concret, pas des accolades.
        $variables = [
            'entreprise_client' => 'Atelier Lumen',
            'contact'           => 'Mme Duval',
            'secteur'           => (string) ($block['sector_name'] ?? 'Architectes'),
            'prenom_commercial' => Auth::firstName(),
            'service'           => (string) ($block['service_name'] ?? 'Site web'),
        ];
        $html = SpeechRenderer::render((string) $block['content'], $variables);

        if (Http::wantsJson()) {
            Http::json([
                'ok'    => true,
                'html'  => $html,
                'title' => (string) $block['title'],
                'type'  => SpeechBlock::typeLabel((string) $block['type']),
            ]);
        }

        $this->adminView('admin/blocks/preview', [
            'title' => 'Aperçu du bloc',
            'block' => $block,
            'html'  => $html,
        ], 'blocks');
    }

    /** Duplique un bloc (inactif par défaut). */
    public function duplicate(string $id): void
    {
        Http::verifyCsrf();
        $newId = SpeechBlock::duplicate((int) $id, (int) Auth::user()['id']);
        if ($newId === null) {
            $this->failure('/admin/blocs', 'Ce bloc n\'existe plus.');
        }
        Session::flash('success', 'Bloc dupliqué. La copie est désactivée : activez-la après vérification.');
        $this->redirect('/admin/blocs/' . $newId . '/modifier');
    }

    /** Active / désactive (bascule immédiate). */
    public function toggle(string $id): void
    {
        Http::verifyCsrf();
        $block = SpeechBlock::find((int) $id);
        if ($block === null) {
            $this->failure('/admin/blocs', 'Ce bloc n\'existe plus.');
        }
        $newState = (int) $block['is_active'] === 1 ? 0 : 1;
        SpeechBlock::update((int) $block['id'], [
            'is_active'  => $newState,
            'updated_by' => (int) Auth::user()['id'],
        ]);

        if (Http::wantsJson()) {
            Http::json(['ok' => true, 'is_active' => $newState]);
        }
        Session::flash('success', $newState === 1 ? 'Bloc activé.' : 'Bloc désactivé (masqué côté commercial).');
        Http::back('/admin/blocs');
    }

    /** Monter / descendre dans l'ordre. */
    public function move(string $id): void
    {
        Http::verifyCsrf();
        $direction = Http::text('direction', 10) === 'up' ? -1 : 1;
        SpeechBlock::move((int) $id, $direction);
        Http::back('/admin/blocs');
    }

    /** Supprime un bloc (confirmation côté client). */
    public function destroy(string $id): void
    {
        Http::verifyCsrf();
        if (SpeechBlock::find((int) $id) === null) {
            $this->failure('/admin/blocs', 'Ce bloc n\'existe plus.');
        }
        SpeechBlock::delete((int) $id);
        Session::flash('success', 'Bloc supprimé définitivement.');
        $this->redirect('/admin/blocs');
    }

    /** Historique des versions. */
    public function versions(string $id): void
    {
        $block = SpeechBlock::find((int) $id);
        if ($block === null) {
            $this->failure('/admin/blocs', 'Ce bloc n\'existe plus.');
        }

        $this->adminView('admin/blocks/versions', [
            'title'    => 'Historique du bloc',
            'block'    => $block,
            'versions' => SpeechBlock::versions((int) $block['id']),
        ], 'blocks');
    }

    /** Restaure une version précédente (l'état courant est archivé au préalable). */
    public function restore(string $id): void
    {
        Http::verifyCsrf();
        $block = SpeechBlock::find((int) $id);
        if ($block === null) {
            $this->failure('/admin/blocs', 'Ce bloc n\'existe plus.');
        }
        $versionId = Http::id('version_id');
        $version = SpeechBlock::version($versionId, (int) $block['id']);
        if ($version === null) {
            $this->failure('/admin/blocs/' . (int) $block['id'] . '/historique', 'Cette version est introuvable.');
        }

        // Archive l'état courant avant restauration.
        SpeechBlock::snapshot($block, (int) Auth::user()['id'], 'Avant restauration de la version ' . (int) $version['version_no']);

        SpeechBlock::update((int) $block['id'], [
            'type'       => (string) $version['type'],
            'sector_id'  => $version['sector_id'] !== null ? (int) $version['sector_id'] : null,
            'stage_id'   => $version['stage_id'] !== null ? (int) $version['stage_id'] : null,
            'service_id' => $version['service_id'] !== null ? (int) $version['service_id'] : null,
            'title'      => (string) $version['title'],
            'content'    => (string) $version['content'],
            'is_active'  => (int) $version['is_active'],
            'updated_by' => (int) Auth::user()['id'],
        ]);

        Session::flash('success', 'Version ' . (int) $version['version_no'] . ' restaurée. Le contenu est en ligne.');
        $this->redirect('/admin/blocs/' . (int) $block['id'] . '/modifier');
    }

    // ------------------------------------------------------------------ Helpers

    /** Filtre par identifiant ou « none » (blocs génériques). */
    private function filterId(string $key): string
    {
        $value = (string) Http::query($key, '');
        if ($value === 'none') {
            return 'none';
        }
        $id = Sanitizer::id($value);
        return $id > 0 ? (string) $id : '';
    }

    /**
     * Valide et normalise les données d'un bloc.
     *
     * @return array<string, mixed>|null null si des erreurs ont été renvoyées
     */
    private function validate(?string $backPath = null): ?array
    {
        $type = (string) Http::input('type', '');
        $title = Http::text('title', 160);
        $content = Sanitizer::richText(Http::raw('content'), 8000);
        $sectorId = Sanitizer::id(Http::input('sector_id', 0));
        $stageId = Sanitizer::id(Http::input('stage_id', 0));
        $serviceId = Sanitizer::id(Http::input('service_id', 0));
        $position = (int) Http::input('position', SpeechBlock::nextPosition());
        $isActive = Http::bool('is_active') ? 1 : 0;

        $validator = new Validator([
            'type'       => 'Le type de bloc',
            'title'      => 'Le titre',
            'content'    => 'Le contenu',
            'sector_id'  => 'Le secteur',
            'stage_id'   => 'L\'étape',
            'service_id' => 'Le service',
        ]);
        $validator
            ->required('type', $type)
            ->inList('type', $type, array_keys(SpeechBlock::TYPES))
            ->maxLength('title', $title, 160)
            ->required('content', trim(strip_tags($content)))
            ->minLength('content', trim(strip_tags($content)), 10)
            ->optionalId('sector_id', $sectorId)
            ->optionalId('stage_id', $stageId)
            ->optionalId('service_id', $serviceId)
            ->check('sector_id', $sectorId === 0 || Sector::find($sectorId) !== null, 'Secteur inconnu.')
            ->check('stage_id', $stageId === 0 || Stage::find($stageId) !== null, 'Étape inconnue.')
            ->check('service_id', $serviceId === 0 || Service::find($serviceId) !== null, 'Service inconnu.')
            ->check('position', $position >= 0 && $position <= 9999, 'La position doit être comprise entre 0 et 9999.');

        if ($validator->fails()) {
            Session::set('_errors', $validator->errors());
            Session::setOld([
                'type'       => $type,
                'title'      => $title,
                'content'    => Http::raw('content'),
                'sector_id'  => $sectorId ?: null,
                'stage_id'   => $stageId ?: null,
                'service_id' => $serviceId ?: null,
                'position'   => $position,
                'is_active'  => $isActive,
            ]);
            return null;
        }

        return [
            'type'       => $type,
            'sector_id'  => $sectorId ?: null,
            'stage_id'   => $stageId ?: null,
            'service_id' => $serviceId ?: null,
            'title'      => $title,
            'content'    => $content,
            'position'   => $position,
            'is_active'  => $isActive,
        ];
    }
}
