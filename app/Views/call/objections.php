<?php
/**
 * Repli sans JavaScript : page complète des objections.
 * Le volet latéral (fetch) est le mode normal, cette page garantit l'accès dans tous les cas.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$objections = $objections ?? [];
$context = $context ?? [];
?>
<?= App\Core\App::render('partials/stepper', ['step' => 4]) ?>
<?= App\Core\App::render('partials/call-context', ['context' => $context]) ?>

<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('shield', '', 14) ?> Aide à l'appel</span>
        <h1>Objections et réponses</h1>
        <p class="page-head__sub">Répondez court, puis reposez une question. Une objection n'est pas un refus.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/appel/speech')) ?>">
            <?= Icon::svg('arrow-left') ?> Retour au speech
        </a>
    </div>
</div>

<div class="card card--pad-lg" style="max-width:820px">
    <?= App\Core\App::render('call/partials/objections', ['objections' => $objections]) ?>
</div>
