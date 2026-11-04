<?php
/**
 * Barre d'étapes du parcours d'appel (toujours visible).
 *
 * @var int $step étape courante (1 à 5)
 *
 * Les étapes déjà franchies sont cliquables et conservent le contexte.
 */

use App\Core\Sanitizer;
use App\Core\View;

$step = (int) ($step ?? 1);
$items = [
    1 => ['label' => 'Contexte', 'url' => '/'],
    2 => ['label' => 'Présentation', 'url' => '/appel/presentation'],
    3 => ['label' => 'Service', 'url' => '/appel/services'],
    4 => ['label' => 'Speech', 'url' => '/appel/speech'],
    5 => ['label' => 'Fin d\'appel', 'url' => '/appel/terminer'],
];
?>
<nav aria-label="Étapes de l'appel">
    <ol class="stepper">
        <?php foreach ($items as $num => $item):
            $isDone = $num < $step;
            $isCurrent = $num === $step;
            $class = 'stepper__item' . ($isDone ? ' is-done' : '') . ($isCurrent ? ' is-current' : '');
            ?>
            <li class="<?= $class ?>" <?= $isCurrent ? 'aria-current="step"' : '' ?>>
                <?php if ($isDone): ?>
                    <a href="<?= Sanitizer::e(View::url($item['url'])) ?>" class="stepper__num" style="text-decoration:none" aria-label="Revenir à l'étape <?= Sanitizer::e($item['label']) ?>">
                        <?= App\Core\Icon::svg('check', '', 14) ?>
                    </a>
                    <a href="<?= Sanitizer::e(View::url($item['url'])) ?>" class="stepper__label" style="text-decoration:none;color:inherit"><?= Sanitizer::e($item['label']) ?></a>
                <?php else: ?>
                    <span class="stepper__num" aria-hidden="true"><?= $num ?></span>
                    <span class="stepper__label"><?= Sanitizer::e($item['label']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
