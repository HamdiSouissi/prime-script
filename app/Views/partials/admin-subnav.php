<?php
/**
 * Sous-navigation de l'espace d'administration.
 *
 * @var string $activeNav clé de l'entrée active
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$activeNav = $activeNav ?? '';
$items = [
    ['key' => 'dashboard',   'label' => 'Tableau de bord', 'url' => '/admin',              'icon' => 'grid'],
    ['key' => 'blocks',      'label' => 'Blocs de speech',  'url' => '/admin/blocs',        'icon' => 'layers'],
    ['key' => 'objections',  'label' => 'Objections',       'url' => '/admin/objections',   'icon' => 'shield'],
    ['key' => 'sectors',     'label' => 'Secteurs',         'url' => '/admin/secteurs',     'icon' => 'building'],
    ['key' => 'stages',      'label' => 'Étapes',           'url' => '/admin/etapes',       'icon' => 'flag'],
    ['key' => 'services',    'label' => 'Services',         'url' => '/admin/services',     'icon' => 'layers'],
    ['key' => 'users',       'label' => 'Équipe',           'url' => '/admin/utilisateurs', 'icon' => 'users'],
    ['key' => 'calls',       'label' => 'Journal d\'appels','url' => '/admin/appels',       'icon' => 'history'],
];
?>
<nav class="subnav no-print" aria-label="Administration">
    <ul class="subnav__list">
        <?php foreach ($items as $item): ?>
            <li style="margin:0;list-style:none">
                <a class="subnav__link<?= $activeNav === $item['key'] ? ' is-active' : '' ?>"
                   href="<?= Sanitizer::e(View::url($item['url'])) ?>"
                   <?= $activeNav === $item['key'] ? 'aria-current="page"' : '' ?>>
                    <?= Icon::svg($item['icon']) ?>
                    <?= Sanitizer::e($item['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
