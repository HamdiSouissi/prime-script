<?php
/**
 * Liste des objections (rendue dans le volet, en HTML seul ou via fetch).
 *
 * @var array<int, array<string, mixed>> $objections
 */

use App\Core\Icon;
use App\Core\Sanitizer;

$objections = $objections ?? [];
$groups = ['service + secteur' => [], 'secteur' => [], 'service' => [], 'générale' => []];
foreach ($objections as $objection) {
    $scope = (string) ($objection['_scope'] ?? 'générale');
    $groups[$scope][] = $objection;
}
?>
<?php if ($objections === []): ?>
    <div class="alert alert--info">
        <?= Icon::svg('info') ?>
        <div class="alert__body">
            <strong>Aucune objection disponible.</strong><br>
            L'administrateur n'a pas encore publié d'objections pour ce contexte.
        </div>
    </div>
<?php else: ?>
    <p class="text-xs text-muted-2 mt-0" style="margin-bottom:var(--s-3)">
        Cliquez sur une objection pour afficher la réponse recommandée.
    </p>

    <?php foreach ($groups as $scope => $items): ?>
        <?php if ($items === []): continue; endif; ?>
        <div class="objection-list" style="margin-bottom:var(--s-4)">
            <?php if ($scope !== 'générale'): ?>
                <p class="text-xs text-muted-2 mb-2" style="margin-top:0;text-transform:uppercase;letter-spacing:.08em;font-weight:600">
                    <?= Sanitizer::e($scope === 'service + secteur' ? 'Objections spécifiques' : 'Objections liées au ' . $scope) ?>
                </p>
            <?php endif; ?>

            <?php foreach ($items as $objection):
                $answerId = 'objection-' . (int) $objection['id'];
                ?>
                <div class="objection" data-objection>
                    <button type="button" class="objection__button" data-accordion-button
                            aria-expanded="false" aria-controls="<?= Sanitizer::e($answerId) ?>">
                        <?= Icon::svg('message') ?>
                        <span class="grow"><?= Sanitizer::e($objection['question']) ?></span>
                        <svg class="ico objection__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="objection__answer" id="<?= Sanitizer::e($answerId) ?>">
                        <div class="rich rich--tight mt-2">
                            <?= $objection['answer_html'] ?? Sanitizer::e(strip_tags((string) $objection['answer'])) ?>
                        </div>
                        <?php if (!empty($objection['_scope']) && $objection['_scope'] !== 'générale'): ?>
                            <p class="objection__scope mb-0 mt-2" style="margin-bottom:0">Contexte : <?= Sanitizer::e((string) $objection['_scope']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <p class="text-xs text-muted-2" data-objection-empty hidden style="margin-top:var(--s-4);text-align:center">
        Aucune objection ne correspond à cette recherche.
    </p>
<?php endif; ?>
