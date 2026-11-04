<?php
/**
 * Écran 2 — Présentation de Prime Vision Solutions + accroches (2 à 4 cartes à lire à voix haute).
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$context = $context ?? [];
$presentations = $presentations ?? [];
$hooks = $hooks ?? [];
$gatekeepers = $gatekeepers ?? [];
$voicemails = $voicemails ?? [];
?>
<?= App\Core\App::render('partials/stepper', ['step' => 2]) ?>
<?= App\Core\App::render('partials/call-context', ['context' => $context]) ?>

<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('sparkles', '', 14) ?> Étape 2 · Présentation</span>
        <h1>Qui vous êtes, et pourquoi vous appelez</h1>
        <p class="page-head__sub">Lisez la présentation, puis choisissez <strong>une</strong> accroche. Parlez lentement, souriez : ça s'entend.</p>
    </div>
</div>

<div class="pitch-layout">
    <section class="pitch-card pitch-card--accent" aria-labelledby="presentation-title">
        <h2 id="presentation-title" class="card__title mb-3">Votre présentation</h2>

        <?php if ($presentations === []): ?>
            <div class="speech-empty">
                Aucune présentation n'est publiée pour ce contexte.<br>
                <span class="text-xs">L'administrateur peut en ajouter dans « Contenus → Blocs de speech ».</span>
            </div>
        <?php else: ?>
            <div class="stack">
                <?php foreach ($presentations as $presentation): ?>
                    <article class="rich rich--lg">
                        <p class="text-xs text-muted-2" style="margin-bottom:var(--s-2)">
                            <?= Icon::svg('book', '', 14) ?> <?= Sanitizer::e($presentation['title'] !== '' ? $presentation['title'] : 'Présentation') ?>
                        </p>
                        <?= $presentation['html'] ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card__foot" style="justify-content:space-between">
            <span class="text-xs text-muted-2"><?= Icon::svg('info', '', 14) ?> Adapté au secteur et à l'étape choisis.</span>
            <a class="btn btn--ghost btn--sm" href="<?= Sanitizer::e(View::url('/appel/objections')) ?>">
                Objections <?= Icon::svg('arrow-right') ?>
            </a>
        </div>
    </section>

    <section aria-labelledby="hooks-title">
        <div class="section-label">
            <h2 id="hooks-title">Choisissez votre accroche</h2>
            <span class="section-label__hint">Lisez-la telle quelle</span>
        </div>

        <?php if ($hooks === []): ?>
            <div class="speech-empty">
                Aucune accroche publiée pour ce contexte.<br>
                <span class="text-xs">Vous pouvez passer directement à la proposition de service.</span>
            </div>
        <?php endif; ?>

        <div class="hook-list" data-hook-list>
            <?php foreach ($hooks as $index => $hook): ?>
                <article class="hook" data-hook>
                    <div class="hook__head">
                        <span class="hook__num" aria-hidden="true"><?= $index + 1 ?></span>
                        <span class="badge badge--accent"><?= Sanitizer::e($hook['title'] !== '' ? $hook['title'] : 'Accroche ' . ($index + 1)) ?></span>
                        <?php if (!empty($hook['stage_name'])): ?>
                            <span class="badge badge--info"><?= Sanitizer::e((string) $hook['stage_name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($hook['sector_name']) || !empty($hook['_generic'])): ?>
                            <span class="badge badge--muted" style="margin-left:auto">
                                <?= Sanitizer::e(!empty($hook['_generic']) ? 'Tous secteurs' : (string) $hook['sector_name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="hook__text rich"><?= $hook['html'] ?></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php if ($gatekeepers !== [] || $voicemails !== []): ?>
<div class="pitch-layout mt-4">
    <?php if ($gatekeepers !== []): ?>
        <section class="card" aria-labelledby="barrage-title" data-open>
            <div class="section-label" style="margin-bottom:var(--s-3)">
                <h2 id="barrage-title"><?= Icon::svg('key', '', 16) ?> Passer le standard</h2>
                <span class="section-label__hint"><?= count($gatekeepers) ?> formulation<?= count($gatekeepers) > 1 ? 's' : '' ?></span>
            </div>
            <p class="text-sm text-muted" style="margin-top:0">
                Vous n'êtes pas encore avec le dirigeant : restez simple et honnête, on vous passe plus facilement.
            </p>
            <div class="stack stack--lg">
                <?php foreach ($gatekeepers as $index => $line): ?>
                    <article>
                        <?php if ($line['title'] !== ''): ?>
                            <p class="text-xs text-muted-2" style="text-transform:uppercase;letter-spacing:.08em;font-weight:600;margin-bottom:var(--s-2)">
                                <?= Sanitizer::e(($index + 1) . '. ' . $line['title']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="rich"><?= $line['html'] ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($voicemails !== []): ?>
        <section class="card" aria-labelledby="repondeur-title">
            <div class="section-label" style="margin-bottom:var(--s-3)">
                <h2 id="repondeur-title"><?= Icon::svg('phone-off', '', 16) ?> Répondeur</h2>
                <span class="section-label__hint">20 secondes maximum</span>
            </div>
            <p class="text-sm text-muted" style="margin-top:0">
                Parlez lentement, laissez votre numéro une seule fois, puis rappelez à un autre moment.
            </p>
            <div class="stack stack--lg">
                <?php foreach ($voicemails as $index => $message): ?>
                    <article>
                        <?php if ($message['title'] !== ''): ?>
                            <p class="text-xs text-muted-2" style="text-transform:uppercase;letter-spacing:.08em;font-weight:600;margin-bottom:var(--s-2)">
                                <?= Sanitizer::e($message['title']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="rich"><?= $message['html'] ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card mt-4" style="display:flex;flex-wrap:wrap;gap:var(--s-3);align-items:center;justify-content:space-between">
    <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/')) ?>">
        <?= Icon::svg('arrow-left') ?> Retour au contexte
    </a>
    <a class="btn btn--lg" href="<?= Sanitizer::e(View::url('/appel/services')) ?>">
        Passer à la proposition <?= Icon::svg('arrow-right') ?>
    </a>
</div>

<?= App\Core\App::render('partials/objections-drawer', ['objections' => $objections ?? []]) ?>
