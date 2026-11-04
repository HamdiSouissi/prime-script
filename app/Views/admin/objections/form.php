<?php
/**
 * Administration — création / modification d'une objection.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$objection = $objection ?? [];
$taxonomy = $taxonomy ?? [];
$errors = $errors ?? [];
$isNew = $isNew ?? true;
$action = $isNew ? View::url('/admin/objections') : View::url('/admin/objections/' . (int) $objection['id'] . '/modifier');
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('shield', '', 14) ?> <?= $isNew ? 'Création' : 'Modification' ?></span>
        <h1><?= $isNew ? 'Nouvelle objection' : 'Modifier l\'objection' ?></h1>
        <p class="page-head__sub">La question telle que le prospect la pose, puis la réponse en puces courtes.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/admin/objections')) ?>">
            <?= Icon::svg('arrow-left') ?> Retour
        </a>
    </div>
</div>

<form method="post" action="<?= Sanitizer::e($action) ?>" novalidate style="max-width:900px">
    <?= View::csrf() ?>

    <div class="card mb-3">
        <div class="field">
            <label for="question">Objection du prospect <span aria-hidden="true">*</span></label>
            <input type="text" id="question" name="question" value="<?= Sanitizer::e($objection['question'] ?? '') ?>"
                   maxlength="255" required placeholder="Ex. : C'est trop cher"
                   aria-invalid="<?= isset($errors['question']) ? 'true' : 'false' ?>">
            <span class="field__hint">Écrivez-la comme le prospect la dit, pas comme vous voudriez qu'il la dise.</span>
            <?php if (isset($errors['question'])): ?><span class="field__error"><?= Sanitizer::e($errors['question']) ?></span><?php endif; ?>
        </div>

        <div class="field">
            <label for="answer">Réponse recommandée <span aria-hidden="true">*</span></label>
            <div data-editor>
                <div class="editor-toolbar" role="toolbar" aria-label="Mise en forme">
                    <button type="button" class="editor-toolbar__btn" data-editor-action="bold" title="Gras" aria-label="Gras"><strong>G</strong></button>
                    <button type="button" class="editor-toolbar__btn" data-editor-action="italic" title="Italique" aria-label="Italique"><em>I</em></button>
                    <button type="button" class="editor-toolbar__btn" data-editor-action="list" title="Puces" aria-label="Liste à puces"><?= Icon::svg('list', '', 16) ?></button>
                </div>
                <textarea id="answer" name="answer" rows="8" required data-preview-source
                          aria-invalid="<?= isset($errors['answer']) ? 'true' : 'false' ?>"
                          placeholder="- Je comprends, c'est un vrai sujet.&#10;- On commence à 790 € HT, pas 5 000 €.&#10;- Le devis est sans engagement."><?= Sanitizer::e((string) ($objection['answer'] ?? '')) ?></textarea>
            </div>
            <span class="field__hint">2 à 4 puces. La première phrase sert à accuser réception, la dernière à relancer.</span>
            <?php if (isset($errors['answer'])): ?><span class="field__error"><?= Sanitizer::e($errors['answer']) ?></span><?php endif; ?>
        </div>
    </div>

    <div class="card mb-3">
        <h2 class="card__title mb-3" style="font-size:var(--fs-md)">Contexte d'apparition</h2>
        <div class="form-grid">
            <div class="field">
                <label for="service_id">Liée à un service</label>
                <select id="service_id" name="service_id">
                    <option value="">Tous les services</option>
                    <?php foreach ($taxonomy['services'] ?? [] as $id => $name): ?>
                        <option value="<?= (int) $id ?>" <?= (int) ($objection['service_id'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="sector_id">Liée à un secteur</label>
                <select id="sector_id" name="sector_id">
                    <option value="">Tous les secteurs</option>
                    <?php foreach ($taxonomy['sectors'] ?? [] as $id => $name): ?>
                        <option value="<?= (int) $id ?>" <?= (int) ($objection['sector_id'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="position">Position</label>
                <input type="number" id="position" name="position" min="0" max="9999" value="<?= (int) ($objection['position'] ?? 1) ?>">
            </div>
            <div class="field">
                <label class="check check--switch" for="is_active" style="margin-top:1.6rem">
                    <span class="switch">
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?= (int) ($objection['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <span class="switch__track" aria-hidden="true"></span>
                    </span>
                    <span>Objection active</span>
                </label>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="row row--between">
            <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/admin/objections')) ?>">Annuler</a>
            <button type="submit" class="btn btn--lg" data-loading-text="Enregistrement…">
                <?= Icon::svg('save') ?> <?= $isNew ? 'Créer l\'objection' : 'Enregistrer' ?>
            </button>
        </div>
    </div>
</form>
