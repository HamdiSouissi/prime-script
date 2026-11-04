<?php
/**
 * Administration — étapes de suivi commercial.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$items = $items ?? [];
$errors = $errors ?? [];
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('flag', '', 14) ?> Contenus</span>
        <h1>Étapes de suivi</h1>
        <p class="page-head__sub"><?= count($items) ?> étape<?= count($items) > 1 ? 's' : '' ?> · le commercial choisit l'étape au début de chaque appel.</p>
    </div>
</div>

<div class="admin-grid admin-grid--2" style="align-items:start">
    <section aria-labelledby="list-title">
        <div class="section-label" style="margin-top:0">
            <h2 id="list-title">Liste et ordre</h2>
        </div>

        <div class="taxonomy-list">
            <?php foreach ($items as $item):
                $isActive = (int) $item['is_active'] === 1;
                ?>
                <article class="taxonomy-row<?= $isActive ? '' : ' is-inactive' ?>">
                    <span class="taxonomy-row__icon"><?= Icon::svg('flag') ?></span>
                    <div class="taxonomy-row__body">
                        <span class="taxonomy-row__title"><?= Sanitizer::e($item['name']) ?> <?= $isActive ? '' : '<span class="badge badge--muted">Inactif</span>' ?></span>
                        <span class="taxonomy-row__meta"><?= Sanitizer::e($item['description']) ?></span>
                    </div>
                    <div class="taxonomy-row__actions">
                        <div class="order-btns">
                            <form method="post" action="<?= Sanitizer::e(View::url('/admin/taxonomie/etapes/' . (int) $item['id'] . '/deplacer')) ?>">
                                <?= View::csrf() ?><input type="hidden" name="direction" value="up">
                                <button class="btn btn--ghost btn--sm" aria-label="Monter"><?= Icon::svg('chevron-up', '', 16) ?></button>
                            </form>
                            <form method="post" action="<?= Sanitizer::e(View::url('/admin/taxonomie/etapes/' . (int) $item['id'] . '/deplacer')) ?>">
                                <?= View::csrf() ?><input type="hidden" name="direction" value="down">
                                <button class="btn btn--ghost btn--sm" aria-label="Descendre"><?= Icon::svg('chevron-down', '', 16) ?></button>
                            </form>
                        </div>
                        <form method="post" action="<?= Sanitizer::e(View::url('/admin/taxonomie/etapes/' . (int) $item['id'] . '/basculer')) ?>">
                            <?= View::csrf() ?>
                            <button class="btn btn--sm <?= $isActive ? 'btn--soft' : 'btn--ghost' ?>"><?= $isActive ? 'Désactiver' : 'Activer' ?></button>
                        </form>
                        <form method="post" action="<?= Sanitizer::e(View::url('/admin/taxonomie/etapes/' . (int) $item['id'] . '/supprimer')) ?>"
                              data-confirm="Supprimer l'étape « <?= Sanitizer::e($item['name']) ?> » ?">
                            <?= View::csrf() ?>
                            <button class="btn btn--ghost btn--sm" style="color:var(--err)" aria-label="Supprimer"><?= Icon::svg('trash', '', 16) ?></button>
                        </form>
                    </div>
                </article>

                <details class="disclosure" style="margin-top:-.25rem">
                    <summary>Modifier « <?= Sanitizer::e($item['name']) ?> »</summary>
                    <div>
                        <form method="post" action="<?= Sanitizer::e(View::url('/admin/taxonomie/etapes/' . (int) $item['id'])) ?>">
                            <?= View::csrf() ?>
                            <div class="inline-form" style="grid-template-columns:1fr 1.6fr auto">
                                <div class="field">
                                    <label for="name-<?= (int) $item['id'] ?>">Nom</label>
                                    <input type="text" id="name-<?= (int) $item['id'] ?>" name="name" value="<?= Sanitizer::e($item['name']) ?>" maxlength="120" required>
                                </div>
                                <div class="field">
                                    <label for="desc-<?= (int) $item['id'] ?>">Description</label>
                                    <input type="text" id="desc-<?= (int) $item['id'] ?>" name="description" value="<?= Sanitizer::e($item['description']) ?>" maxlength="255">
                                </div>
                                <button type="submit" class="btn btn--sm"><?= Icon::svg('save') ?> Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card" aria-labelledby="new-title">
        <h2 class="card__title" id="new-title"><?= Icon::svg('plus', '', 18) ?> Ajouter une étape</h2>
        <p class="card__sub mb-3">Ex. : « Deuxième relance », « Signature ».</p>

        <form method="post" action="<?= Sanitizer::e(View::url('/admin/taxonomie/etapes')) ?>">
            <?= View::csrf() ?>
            <div class="field">
                <label for="new-name">Nom de l'étape <span aria-hidden="true">*</span></label>
                <input type="text" id="new-name" name="name" maxlength="120" required placeholder="Ex. : Deuxième relance">
                <?php if (isset($errors['name'])): ?><span class="field__error"><?= Sanitizer::e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="new-desc">Description courte</label>
                <input type="text" id="new-desc" name="description" maxlength="255" placeholder="Contexte de cet appel">
            </div>
            <button type="submit" class="btn btn--block"><?= Icon::svg('plus') ?> Ajouter l'étape</button>
        </form>
    </section>
</div>
