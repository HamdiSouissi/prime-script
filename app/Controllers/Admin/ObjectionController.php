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

/**
 * Gestion des objections et de leurs réponses recommandées.
 */
final class ObjectionController extends AdminController
{
    public function index(): void
    {
        $filters = [
            'active'     => $this->queryList('active', ['0', '1'], ''),
            'service_id' => $this->filterId('service_id'),
            'sector_id'  => $this->filterId('sector_id'),
            'q'          => Http::text('q', 80),
        ];

        $objections = Objection::withRelations(
            $filters['active'] !== '' ? $filters['active'] : null,
            $filters['service_id'] !== '' ? $filters['service_id'] : null,
            $filters['sector_id'] !== '' ? $filters['sector_id'] : null,
            $filters['q']
        );

        $this->adminView('admin/objections/index', [
            'title'      => 'Objections',
            'objections' => $objections,
            'filters'    => $filters,
            'taxonomy'   => $this->taxonomyOptions(false),
        ], 'objections');
    }

    public function create(): void
    {
        $this->adminView('admin/objections/form', [
            'title'     => 'Nouvelle objection',
            'objection' => [
                'id'         => 0,
                'question'   => '',
                'answer'     => '',
                'service_id' => null,
                'sector_id'  => null,
                'position'   => Objection::nextPosition(),
                'is_active'  => 1,
            ],
            'taxonomy'  => $this->taxonomyOptions(false),
            'errors'    => $this->takeErrors(),
            'old'       => $this->takeOld(),
            'isNew'     => true,
        ], 'objections');
    }

    public function store(): void
    {
        Http::verifyCsrf();
        $data = $this->validate();
        if ($data === null) {
            $this->redirect('/admin/objections/nouveau');
        }
        Objection::create($data);
        Session::flash('success', 'Objection créée : elle est disponible dans le volet d\'aide pendant l\'appel.');
        $this->redirect('/admin/objections');
    }

    public function edit(string $id): void
    {
        $objection = Objection::find((int) $id);
        if ($objection === null) {
            $this->failure('/admin/objections', 'Cette objection n\'existe plus.');
        }
        $old = $this->takeOld();

        $this->adminView('admin/objections/form', [
            'title'     => 'Modifier l\'objection',
            'objection' => $old !== [] ? array_merge($objection, $old) : $objection,
            'taxonomy'  => $this->taxonomyOptions(false),
            'errors'    => $this->takeErrors(),
            'old'       => [],
            'isNew'     => false,
        ], 'objections');
    }

    public function update(string $id): void
    {
        Http::verifyCsrf();
        $objectionId = (int) $id;
        if (Objection::find($objectionId) === null) {
            $this->failure('/admin/objections', 'Cette objection n\'existe plus.');
        }

        $data = $this->validate('/admin/objections/' . $objectionId . '/modifier');
        if ($data === null) {
            $this->redirect('/admin/objections/' . $objectionId . '/modifier');
        }
        Objection::update($objectionId, $data);
        Session::flash('success', 'Objection enregistrée.');
        $this->redirect('/admin/objections');
    }

    public function toggle(string $id): void
    {
        Http::verifyCsrf();
        $objection = Objection::find((int) $id);
        if ($objection === null) {
            $this->failure('/admin/objections', 'Cette objection n\'existe plus.');
        }
        $newState = (int) $objection['is_active'] === 1 ? 0 : 1;
        Objection::update((int) $objection['id'], ['is_active' => $newState]);
        Session::flash('success', $newState === 1 ? 'Objection activée.' : 'Objection désactivée.');
        Http::back('/admin/objections');
    }

    public function move(string $id): void
    {
        Http::verifyCsrf();
        Objection::move((int) $id, Http::text('direction', 10) === 'up' ? -1 : 1);
        Http::back('/admin/objections');
    }

    public function destroy(string $id): void
    {
        Http::verifyCsrf();
        if (Objection::find((int) $id) === null) {
            $this->failure('/admin/objections', 'Cette objection n\'existe plus.');
        }
        Objection::delete((int) $id);
        Session::flash('success', 'Objection supprimée.');
        $this->redirect('/admin/objections');
    }

    // ------------------------------------------------------------------ Helpers

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
     * @return array<string, mixed>|null
     */
    private function validate(string $backPath = '/admin/objections/nouveau'): ?array
    {
        $question = Http::text('question', 255);
        $answer = Sanitizer::richText(Http::raw('answer'), 4000);
        $serviceId = Sanitizer::id(Http::input('service_id', 0));
        $sectorId = Sanitizer::id(Http::input('sector_id', 0));
        $position = (int) Http::input('position', Objection::nextPosition());
        $isActive = Http::bool('is_active') ? 1 : 0;

        $validator = new Validator([
            'question' => 'La question du prospect',
            'answer'   => 'La réponse recommandée',
        ]);
        $validator
            ->required('question', $question)
            ->maxLength('question', $question, 255)
            ->required('answer', trim(strip_tags($answer)))
            ->check('service_id', $serviceId === 0 || Service::find($serviceId) !== null, 'Service inconnu.')
            ->check('sector_id', $sectorId === 0 || Sector::find($sectorId) !== null, 'Secteur inconnu.')
            ->check('position', $position >= 0 && $position <= 9999, 'Position invalide.');

        if ($validator->fails()) {
            Session::set('_errors', $validator->errors());
            Session::setOld([
                'question'   => $question,
                'answer'     => Http::raw('answer'),
                'service_id' => $serviceId ?: null,
                'sector_id'  => $sectorId ?: null,
                'position'   => $position,
                'is_active'  => $isActive,
            ]);
            return null;
        }

        return [
            'question'   => $question,
            'answer'     => $answer,
            'service_id' => $serviceId ?: null,
            'sector_id'  => $sectorId ?: null,
            'position'   => $position,
            'is_active'  => $isActive,
        ];
    }
}
