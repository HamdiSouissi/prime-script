<?php
/**
 * Volet latéral « Objections » + bouton flottant.
 * Accessible sur tous les écrans du parcours d'appel.
 *
 * @var array<int, array<string, mixed>> $objections
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$objections = $objections ?? [];
$count = count($objections);
?>
<button type="button" class="fab no-print" data-drawer-open aria-haspopup="dialog" aria-controls="drawer-objections" title="Objections et réponses recommandées (raccourci O)">
    <?= Icon::svg('shield') ?>
    <span>Objections</span>
    <?php if ($count > 0): ?>
        <span class="fab__count" aria-hidden="true"><?= $count ?></span>
    <?php endif; ?>
</button>

<div class="drawer" id="drawer-objections" data-drawer
     data-endpoint="<?= Sanitizer::e(View::url('/appel/objections')) ?>"
     role="dialog" aria-modal="true" aria-labelledby="drawer-objections-title" aria-hidden="true">
    <button type="button" class="drawer__overlay" data-drawer-close tabindex="-1" aria-label="Fermer le panneau des objections"></button>
    <div class="drawer__panel" tabindex="-1">
        <div class="drawer__head">
            <span class="speech-block__icon" style="width:2.4rem;height:2.4rem"><?= Icon::svg('shield') ?></span>
            <div class="grow">
                <h2 id="drawer-objections-title">Objections</h2>
                <p>Réponses courtes, prêtes à dire.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--icon" data-drawer-close aria-label="Fermer">
                <?= Icon::svg('close') ?>
            </button>
        </div>

        <div class="drawer__tools">
            <label class="visually-hidden" for="objection-search">Rechercher une objection</label>
            <input type="search" id="objection-search" data-objection-search placeholder="Rechercher : trop cher, déjà un site…" autocomplete="off">
        </div>

        <div class="drawer__body" data-drawer-body aria-live="polite">
            <?= App\Core\App::render('call/partials/objections', ['objections' => $objections]) ?>
        </div>
    </div>
</div>
