<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Sector;
use App\Models\Service;
use App\Models\SpeechBlock;
use App\Models\Stage;

/**
 * Base des contrôleurs d'administration : accès déjà vérifié par le routeur (middleware admin),
 * et données communes aux sous-navigations.
 */
abstract class AdminController extends Controller
{
    /**
     * Affiche une vue d'administration avec la sous-navigation « Contenus ».
     *
     * @param array<string, mixed> $data
     */
    protected function adminView(string $view, array $data = [], string $activeNav = ''): void
    {
        $data['activeNav'] = $activeNav;
        $data['subnav'] = $activeNav;
        $this->view($view, $data);
    }

    /**
     * Listes des taxonomies pour les formulaires (sélecteurs de liens).
     *
     * @return array<string, array<int, string>>
     */
    protected function taxonomyOptions(bool $onlyActive = false): array
    {
        return [
            'sectors'  => Sector::options('name', $onlyActive),
            'stages'   => Stage::options('name', $onlyActive),
            'services' => Service::options('name', $onlyActive),
            'types'    => SpeechBlock::TYPES,
        ];
    }
}
