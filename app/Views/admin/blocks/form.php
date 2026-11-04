<?php
/**
 * Administration — création / modification d'un bloc de speech.
 * Éditeur simple (gras, italique, puces) + aperçu live + aide sur les variables.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\SpeechBlock;
use App\Services\SpeechRenderer;

$block = $block ?? [];
$taxonomy = $taxonomy ?? [];
$errors = $errors ?? [];
$isNew = $isNew ?? true;
$versions = $versions ?? [];
$action = $isNew
    ? View::url('/admin/blocs')
    : View::url('/admin/blocs/' . (int) $block['id'] . '/modifier');
$contentValue = (string) ($block['content'] ?? '');
$type = (string) ($block['type'] ?? 'decouverte');
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('edit', '', 14) ?> <?= $isNew ? 'Création' : 'Modification' ?></span>
        <h1><?= $isNew ? 'Nouveau bloc de speech' : 'Modifier le bloc' ?></h1>
        <p class="page-head__sub">
            <?= $isNew
                ? 'Un bloc = une idée courte. Laissez un ciblage vide pour qu\'il s\'applique à tous les contextes.'
                : 'Chaque enregistrement archive la version précédente : rien ne se perd.' ?>
        </p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">
            <?= Icon::svg('arrow-left') ?> Retour à la liste
        </a>
        <?php if (!$isNew): ?>
            <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/historique')) ?>">
                <?= Icon::svg('history') ?> Historique (<?= count($versions) ?>)
            </a>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="<?= Sanitizer::e($action) ?>" novalidate>
    <?= View::csrf() ?>

    <div class="editor-layout">
        <div class="stack">
            <div class="card">
                <div class="form-grid">
                    <div class="field">
                        <label for="type">Type de bloc <span aria-hidden="true">*</span></label>
                        <select id="type" name="type" required aria-invalid="<?= isset($errors['type']) ? 'true' : 'false' ?>">
                            <?php foreach (SpeechBlock::TYPES as $key => $label): ?>
                                <option value="<?= Sanitizer::e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= Sanitizer::e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field__hint">Détermine l'écran du parcours où le bloc apparaît.</span>
                        <?php if (isset($errors['type'])): ?><span class="field__error"><?= Sanitizer::e($errors['type']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="title">Titre court</label>
                        <input type="text" id="title" name="title" value="<?= Sanitizer::e($block['title'] ?? '') ?>"
                               maxlength="160" placeholder="Ex. : Questions de découverte — les 4 indispensables">
                        <span class="field__hint">Affiché dans la liste et en en-tête du bloc.</span>
                    </div>

                    <div class="field field--full">
                        <div class="row row--between" style="gap:var(--s-2);align-items:center">
                            <label for="content" style="margin:0">Contenu <span aria-hidden="true">*</span></label>
                            <span class="text-xs text-muted-2">Une ligne commençant par « - » devient une puce.</span>
                        </div>

                        <div data-editor>
                            <div class="editor-toolbar" role="toolbar" aria-label="Mise en forme">
                                <button type="button" class="editor-toolbar__btn" data-editor-action="bold" title="Gras (Ctrl+B)" aria-label="Gras"><strong>G</strong></button>
                                <button type="button" class="editor-toolbar__btn" data-editor-action="italic" title="Italique" aria-label="Italique"><em>I</em></button>
                                <button type="button" class="editor-toolbar__btn" data-editor-action="list" title="Liste à puces" aria-label="Liste à puces"><?= Icon::svg('list', '', 16) ?></button>
                                <button type="button" class="editor-toolbar__btn" data-editor-action="paragraph" title="Paragraphe" aria-label="Paragraphe">¶</button>
                                <span style="width:1px;background:var(--line);margin:0 .25rem" aria-hidden="true"></span>
                                <?php foreach (SpeechRenderer::variableHelp() as $key => $description): ?>
                                    <button type="button" class="editor-toolbar__btn" data-insert-variable="{<?= Sanitizer::e($key) ?>}"
                                            title="<?= Sanitizer::e($description) ?>" style="font-size:var(--fs-xxs);font-family:ui-monospace,monospace">
                                        {<?= Sanitizer::e($key) ?>}
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <textarea id="content" name="content" rows="14" required
                                      aria-invalid="<?= isset($errors['content']) ? 'true' : 'false' ?>"
                                      data-preview-source
                                      placeholder="- Point clé à dire&#10;- Deuxième point court&#10;&#10;Une phrase de transition."
                            ><?= Sanitizer::e($contentValue) ?></textarea>
                        </div>
                        <?php if (isset($errors['content'])): ?><span class="field__error"><?= Sanitizer::e($errors['content']) ?></span><?php endif; ?>
                        <span class="field__hint">Mise en forme autorisée : <strong>gras</strong>, <em>italique</em>, puces, paragraphes. Aucun code HTML libre n'est accepté.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="card__title mb-3">Ciblage</h2>
                <p class="text-sm text-muted mb-3" style="margin-top:0">
                    Laissez vide pour « tous ». Plus un bloc est ciblé, plus il est prioritaire sur un bloc générique.
                </p>
                <div class="form-grid">
                    <div class="field">
                        <label for="sector_id">Secteur</label>
                        <select id="sector_id" name="sector_id">
                            <option value="">Tous les secteurs</option>
                            <?php foreach ($taxonomy['sectors'] ?? [] as $id => $name): ?>
                                <option value="<?= (int) $id ?>" <?= (int) ($block['sector_id'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="stage_id">Étape de suivi</label>
                        <select id="stage_id" name="stage_id">
                            <option value="">Toutes les étapes</option>
                            <?php foreach ($taxonomy['stages'] ?? [] as $id => $name): ?>
                                <option value="<?= (int) $id ?>" <?= (int) ($block['stage_id'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="service_id">Service</label>
                        <select id="service_id" name="service_id">
                            <option value="">Tous les services</option>
                            <?php foreach ($taxonomy['services'] ?? [] as $id => $name): ?>
                                <option value="<?= (int) $id ?>" <?= (int) ($block['service_id'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="position">Position d'affichage</label>
                        <input type="number" id="position" name="position" min="0" max="9999"
                               value="<?= (int) ($block['position'] ?? 1) ?>">
                        <span class="field__hint">Plus petit = affiché avant.</span>
                    </div>
                </div>

                <label class="check check--switch mt-3" for="is_active">
                    <span class="switch">
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?= (int) ($block['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <span class="switch__track" aria-hidden="true"></span>
                    </span>
                    <span>
                        <strong>Bloc actif</strong><br>
                        <span class="text-xs text-muted">Visible immédiatement par les commerciaux.</span>
                    </span>
                </label>
            </div>

            <?php if (!$isNew): ?>
                <div class="card">
                    <div class="field" style="margin:0">
                        <label for="change_note">Note de modification <span class="field__optional">(facultatif)</span></label>
                        <input type="text" id="change_note" name="change_note" maxlength="255"
                               placeholder="Ex. : reformulation de l'accroche tarifaire">
                        <span class="field__hint">Conservée dans l'historique pour retrouver facilement une version.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="row row--between">
                    <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">Annuler</a>
                    <div class="row" style="gap:var(--s-2)">
                        <?php if (!$isNew): ?>
                            <button type="submit" formaction="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/dupliquer')) ?>"
                                    class="btn btn--secondary" onclick="this.form.querySelector('[name=change_note]').value=''">
                                <?= Icon::svg('copy') ?> Dupliquer
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn--lg" data-loading-text="Enregistrement…">
                            <?= Icon::svg('save') ?> <?= $isNew ? 'Créer le bloc' : 'Enregistrer' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <aside class="stack" aria-label="Aperçu et aide">
            <div class="card" style="position:sticky;top:calc(var(--header-h) + var(--s-3))">
                <div class="row row--between mb-3" style="align-items:center">
                    <h2 class="card__title" style="font-size:var(--fs-md);margin:0"><?= Icon::svg('eye', '', 18) ?> Aperçu commercial</h2>
                    <span class="badge badge--accent">Temps réel</span>
                </div>
                <div class="preview-frame">
                    <div class="rich" data-preview-output>
                        <p class="text-muted">Le contenu apparaîtra ici.</p>
                    </div>
                </div>
                <p class="text-xs text-muted-2 mt-3" data-preview-note hidden style="margin-bottom:0">
                    Aperçu avec des valeurs d'exemple pour les variables.
                </p>
            </div>

            <div class="card">
                <h2 class="card__title mb-3" style="font-size:var(--fs-md)"><?= Icon::svg('wand', '', 18) ?> Variables disponibles</h2>
                <p class="text-xs text-muted mb-3" style="margin-top:0">
                    Cliquez pour insérer. Une variable vide est automatiquement retirée à l'affichage.
                </p>
                <dl class="help-list">
                    <?php foreach (SpeechRenderer::variableHelp() as $key => $description): ?>
                        <div>
                            <dt><button type="button" data-insert-variable="{<?= Sanitizer::e($key) ?>}" style="border:0;background:none;color:inherit;font:inherit;cursor:pointer;padding:0">{<?= Sanitizer::e($key) ?>}</button></dt>
                            <dd><?= Sanitizer::e($description) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>

            <div class="card" style="background:var(--surface-2)">
                <h2 class="card__title mb-2" style="font-size:var(--fs-md)"><?= Icon::svg('info', '', 18) ?> Bonnes pratiques</h2>
                <ul class="text-sm text-muted" style="margin:0;padding-left:1.1rem">
                    <li>Une idée par puce, 12 mots maximum.</li>
                    <li>Les <strong>phrases à dire</strong> sont plus efficaces que les explications.</li>
                    <li>Testez l'aperçu : c'est ce que le commercial lira pendant l'appel.</li>
                </ul>
            </div>
        </aside>
    </div>
</form>
