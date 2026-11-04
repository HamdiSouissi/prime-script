<?php
/**
 * Écran 5 — Fin d'appel : moins de 20 secondes à remplir.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\Service;

$context = $context ?? [];
$results = $results ?? [];
$services = $services ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$chosenResult = (string) ($old['result'] ?? '');
$chosenService = (int) ($old['service_id'] ?? ($context['service_id'] ?? 0));
$note = (string) ($old['note'] ?? '');
$callback = (string) ($old['callback_date'] ?? '');
$conclusions = $conclusions ?? [];
// Associe chaque phrase de conclusion à son résultat (code « fin-rdv », « fin-devis »…).
$scripts = [];
foreach ($conclusions as $conclusion) {
    $code = (string) ($conclusion['code'] ?? '');
    if (str_starts_with($code, 'fin-')) {
        $scripts[substr($code, 4)] = $conclusion;
    }
}
$descriptions = [
    'rdv'              => 'Un créneau est fixé',
    'devis'            => 'Devis écrit à envoyer',
    'rappeler'         => 'Rappel à programmer',
    'pas_interesse'    => 'Sans suite',
    'injoignable'      => 'Pas de réponse',
    'ne_plus_rappeler' => 'Opposition · ne plus contacter',
];
$dots = [
    'rdv'              => 'var(--ok)',
    'devis'            => 'var(--info)',
    'rappeler'         => 'var(--warn)',
    'pas_interesse'    => 'var(--muted-2)',
    'injoignable'      => 'var(--err)',
    'ne_plus_rappeler' => 'var(--err)',
];
?>
<?= App\Core\App::render('partials/stepper', ['step' => 5]) ?>
<?= App\Core\App::render('partials/call-context', ['context' => $context]) ?>

<div class="shell-narrow" style="width:100%;margin:0;max-width:820px">
    <div class="page-head">
        <div class="page-head__text">
            <span class="eyebrow"><?= Icon::svg('flag', '', 14) ?> Étape 5 · Fin d'appel</span>
            <h1>Comment s'est passé l'appel ?</h1>
            <p class="page-head__sub">Trois informations suffisent. Vous revenez ensuite à un nouvel appel.</p>
        </div>
    </div>

    <form method="post" action="<?= Sanitizer::e(View::url('/appel/terminer')) ?>" data-call-end-form novalidate>
        <?= View::csrf() ?>

        <div data-form-error class="alert alert--error mb-3" role="alert" tabindex="-1" hidden></div>

        <div class="card mb-3">
            <fieldset style="border:0;padding:0;margin:0">
                <legend class="card__title" style="margin-bottom:var(--s-3);font-size:var(--fs-md)">Résultat de l'appel</legend>
                <div class="result-grid" data-choice-group>
                    <?php foreach ($results as $key => $label): ?>
                        <label class="result-option<?= $chosenResult === $key ? ' is-selected' : '' ?>">
                            <input type="radio" name="result" value="<?= Sanitizer::e($key) ?>" <?= $chosenResult === $key ? 'checked' : '' ?> required>
                            <span class="result-option__dot" style="background:<?= Sanitizer::e($dots[$key] ?? 'var(--muted-2)') ?>"></span>
                            <span><?= Sanitizer::e($label) ?></span>
                            <small><?= Sanitizer::e($descriptions[$key] ?? '') ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['result'])): ?>
                    <p class="field__error mt-2" role="alert"><?= Sanitizer::e($errors['result']) ?></p>
                <?php endif; ?>
                <p class="field__hint mt-3" style="margin-bottom:0">
                    <?= Icon::svg('shield', '', 14) ?>
                    Si la personne demande à ne plus être rappelée, choisissez
                    <strong>Ne plus rappeler</strong> : l'entreprise est marquée dans le journal et sort des rappels.
                </p>
            </fieldset>
        </div>

        <?php if ($scripts !== []): ?>
        <div class="card mb-3" data-conclusion-card>
            <div class="row row--between" style="gap:var(--s-2);align-items:flex-start;flex-wrap:wrap">
                <h2 class="card__title" style="margin:0;font-size:var(--fs-md)">
                    <?= Icon::svg('message', '', 16) ?> Quoi dire pour conclure
                </h2>
                <span class="badge badge--muted" data-conclusion-hint>S'affiche selon le résultat choisi</span>
            </div>
            <?php foreach ($scripts as $key => $script): ?>
                <div class="conclusion-script rich mt-3" data-conclusion="<?= Sanitizer::e($key) ?>" hidden data-conclusion-key="<?= Sanitizer::e($key) ?>">
                    <p class="text-xs text-muted-2" style="text-transform:uppercase;letter-spacing:.08em;font-weight:600;margin-bottom:var(--s-2)">
                        <?= Sanitizer::e((string) ($script['title'] ?? '')) ?>
                    </p>
                    <?= $script['html'] ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="form-grid">
                <div class="field">
                    <label for="service_id">Service évoqué</label>
                    <select id="service_id" name="service_id">
                        <?php foreach ($services as $service): ?>
                            <option value="<?= (int) $service['id'] ?>" <?= $chosenService === (int) $service['id'] ? 'selected' : '' ?>>
                                <?= Sanitizer::e($service['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="field__hint">Pré-rempli avec le service du speech.</span>
                </div>

                <div class="field" data-callback-field>
                    <label for="callback_date">Date de rappel <span class="field__optional">(si à rappeler)</span></label>
                    <input type="date" id="callback_date" name="callback_date" value="<?= Sanitizer::e($callback) ?>" data-callback-date>
                    <span class="field__hint">Le rappel apparaîtra dans « Mes appels ».</span>
                </div>

                <div class="field field--full">
                    <label for="note">Note courte <span class="field__optional">(facultatif)</span></label>
                    <input type="text" id="note" name="note" value="<?= Sanitizer::e($note) ?>" maxlength="600"
                           placeholder="Ex. : budget serré, rappeler après les congés, intéressé par le chatbot">
                    <span class="field__hint">Une phrase suffit : c'est pour vous et l'équipe.</span>
                    <?php if (isset($errors['note'])): ?>
                        <span class="field__error"><?= Sanitizer::e($errors['note']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="row row--between">
                <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/appel/speech')) ?>">
                    <?= Icon::svg('arrow-left') ?> Retour au speech
                </a>
                <button type="submit" class="btn btn--lg" data-call-end-submit data-loading-text="Enregistrement…">
                    <?= Icon::svg('check') ?> Enregistrer l'appel
                </button>
            </div>
        </div>
    </form>

    <div class="alert alert--info mt-4">
        <?= Icon::svg('info') ?>
        <div class="alert__body">
            <strong>Enregistré pour :</strong>
            <?= Sanitizer::e((string) ($context['sector_name'] ?? '—')) ?> ·
            <?= Sanitizer::e((string) ($context['stage_name'] ?? '—')) ?>
            <?php if (!empty($context['entreprise_client'])): ?>
                · <?= Sanitizer::e($context['entreprise_client']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= App\Core\App::render('partials/objections-drawer', ['objections' => $objections ?? []]) ?>
