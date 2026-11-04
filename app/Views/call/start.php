<?php
/**
 * Écran 1 — Nouvel appel : contexte du prospect.
 * Une seule action principale : « Commencer ».
 */

use App\Core\Auth;
use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$errors = $errors ?? [];
$old = $old ?? [];
$selectedSector = (int) ($old['sector_id'] ?? 0);
$selectedStage = (int) ($old['stage_id'] ?? ($defaultStage['id'] ?? 0));
$entreprise = (string) ($old['entreprise_client'] ?? '');
$contact = (string) ($old['contact'] ?? '');
$firstName = Auth::firstName();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
?>
<div class="start-screen">
    <div>
        <section class="start-hero" aria-labelledby="start-title">
            <span class="eyebrow"><?= Icon::svg('phone', '', 14) ?> Nouvel appel</span>
            <h1 id="start-title"><?= Sanitizer::e($greeting) ?> <?= Sanitizer::e($firstName) ?>.</h1>
            <p>Choisissez le contexte du prospect : Prime Script affiche ensuite la bonne présentation, les bonnes accroches et le bon argumentaire.</p>
            <div class="start-hero__stats">
                <span><strong><?= (int) ($stats['today'] ?? 0) ?></strong> appel<?= (int) ($stats['today'] ?? 0) > 1 ? 's' : '' ?> aujourd'hui</span>
                <span><strong><?= (int) ($stats['rdv_month'] ?? 0) ?></strong> RDV ce mois</span>
                <span><strong><?= (int) ($stats['devis_month'] ?? 0) ?></strong> devis ce mois</span>
            </div>
        </section>

        <form method="post" action="<?= Sanitizer::e(View::url('/appel/contexte')) ?>" novalidate>
            <?= View::csrf() ?>

            <div class="section-label">
                <h2 id="secteur-label"><span aria-hidden="true">1.</span> Secteur d'activité du prospect</h2>
                <span class="section-label__hint">Obligatoire</span>
            </div>

            <fieldset class="choice-grid" data-choice-group aria-labelledby="secteur-label" style="border:0;padding:0;margin:0">
                <legend class="visually-hidden">Secteur d'activité</legend>
                <?php if ($sectors === []): ?>
                    <div class="empty" style="grid-column:1/-1">
                        <p>Aucun secteur actif. Contactez votre administrateur.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($sectors as $sector):
                    $id = (int) $sector['id'];
                    $isChecked = $selectedSector === $id;
                    ?>
                    <label class="choice<?= $isChecked ? ' is-selected' : '' ?>">
                        <input type="radio" name="sector_id" value="<?= $id ?>" <?= $isChecked ? 'checked' : '' ?> required>
                        <span class="choice__icon"><?= Icon::svg((string) $sector['icon']) ?></span>
                        <span class="choice__title"><?= Sanitizer::e($sector['name']) ?></span>
                        <?php if ($sector['description'] !== ''): ?>
                            <span class="choice__sub"><?= Sanitizer::e($sector['description']) ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <?php if (isset($errors['sector_id'])): ?>
                <p class="field__error mt-2" role="alert"><?= Sanitizer::e($errors['sector_id']) ?></p>
            <?php endif; ?>

            <div class="section-label">
                <h2 id="etape-label"><span aria-hidden="true">2.</span> Étape de suivi</h2>
                <span class="section-label__hint">Obligatoire</span>
            </div>

            <fieldset class="choice-grid choice-grid--2" data-choice-group aria-labelledby="etape-label" style="border:0;padding:0;margin:0">
                <legend class="visually-hidden">Étape de suivi</legend>
                <?php foreach ($stages as $stage):
                    $id = (int) $stage['id'];
                    $isChecked = $selectedStage === $id;
                    ?>
                    <label class="choice choice--compact<?= $isChecked ? ' is-selected' : '' ?>">
                        <input type="radio" name="stage_id" value="<?= $id ?>" <?= $isChecked ? 'checked' : '' ?> required>
                        <span class="choice__icon"><?= Icon::svg('flag') ?></span>
                        <span class="grow">
                            <span class="choice__title" style="display:block"><?= Sanitizer::e($stage['name']) ?></span>
                            <?php if ($stage['description'] !== ''): ?>
                                <span class="choice__sub" style="display:block"><?= Sanitizer::e($stage['description']) ?></span>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <?php if (isset($errors['stage_id'])): ?>
                <p class="field__error mt-2" role="alert"><?= Sanitizer::e($errors['stage_id']) ?></p>
            <?php endif; ?>

            <details class="disclosure mt-4" <?= ($entreprise !== '' || $contact !== '') ? 'open' : '' ?>>
                <summary>
                    <span>Personnaliser l'appel <span class="field__optional">(facultatif)</span></span>
                </summary>
                <div class="form-grid">
                    <div class="field">
                        <label for="entreprise_client">Nom de l'entreprise</label>
                        <input type="text" id="entreprise_client" name="entreprise_client" value="<?= Sanitizer::e($entreprise) ?>"
                               placeholder="Atelier Lumen" maxlength="160" autocomplete="off">
                        <span class="field__hint">Utilisé dans les phrases : « Bonjour, je suis bien chez Atelier Lumen ? »</span>
                    </div>
                    <div class="field">
                        <label for="contact">Nom du contact</label>
                        <input type="text" id="contact" name="contact" value="<?= Sanitizer::e($contact) ?>"
                               placeholder="Mme Duval" maxlength="120" autocomplete="off">
                        <span class="field__hint">Utilisé dans les phrases : « Bonjour Mme Duval ».</span>
                    </div>
                </div>
            </details>

            <?php if (!empty($context)): ?>
                <div class="alert alert--info mt-4">
                    <?= Icon::svg('info') ?>
                    <div class="alert__body">
                        Un appel est déjà en cours avec <strong><?= Sanitizer::e((string) ($context['sector_name'] ?? '')) ?></strong>
                        (<?= Sanitizer::e((string) ($context['stage_name'] ?? '')) ?>). Commencer un nouvel appel remplacera ce contexte.
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6">
                <button type="submit" class="btn btn--lg btn--xl">
                    Commencer <span aria-hidden="true">→</span>
                </button>
                <p class="text-xs text-muted-2 text-center mt-2" style="margin-bottom:0">
                    3 clics jusqu'au speech : présentation → service → speech.
                </p>
            </div>
        </form>
    </div>

    <aside class="side-panel" aria-label="Repères du jour">
        <div class="side-card">
            <h2 class="side-card__title"><?= Icon::svg('chart') ?> Vos repères</h2>
            <div class="stack stack--sm">
                <div class="meter">
                    <div class="meter__top"><span>Appels (7 jours)</span><span class="meter__value"><?= (int) ($stats['week'] ?? 0) ?></span></div>
                    <div class="meter__bar"><div class="meter__fill" style="width:<?= min(100, (int) ($stats['week'] ?? 0) * 10) ?>%"></div></div>
                </div>
                <div class="meter">
                    <div class="meter__top"><span>Appels (30 jours)</span><span class="meter__value"><?= (int) ($stats['month'] ?? 0) ?></span></div>
                    <div class="meter__bar"><div class="meter__fill" style="width:<?= min(100, (int) ($stats['month'] ?? 0) * 4) ?>%"></div></div>
                </div>
            </div>
            <p class="text-xs text-muted-2 mt-3" style="margin-bottom:0">
                Taux de positifs (RDV + devis) : <strong><?= (int) ($stats['month'] ?? 0) > 0 ? round((((int) $stats['rdv_month'] + (int) $stats['devis_month']) / (int) $stats['month']) * 100) : 0 ?> %</strong> sur 30 jours
            </p>
        </div>

        <?php if ($recent !== []): ?>
            <div class="side-card">
                <h2 class="side-card__title"><?= Icon::svg('clock') ?> Vos derniers appels</h2>
                <ul class="stack stack--sm" style="list-style:none;padding:0;margin:0">
                    <?php foreach ($recent as $call): ?>
                        <li style="margin:0;display:flex;gap:var(--s-2);align-items:flex-start;font-size:var(--fs-xs)">
                            <span class="badge badge--<?= Sanitizer::e(App\Models\Call::resultTone((string) $call['result'])) ?>" style="flex:none">
                                <?= Sanitizer::e(App\Models\Call::resultLabel((string) $call['result'])) ?>
                            </span>
                            <span class="grow text-muted" style="min-width:0">
                                <?= Sanitizer::e($call['entreprise_client'] !== '' ? $call['entreprise_client'] : 'Prospect') ?>
                                <span class="text-muted-2">· <?= Sanitizer::e(View::ago((string) $call['created_at'])) ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn--sm btn--ghost mt-3" href="<?= Sanitizer::e(View::url('/historique')) ?>" style="width:100%">
                    Voir tous mes appels <?= Icon::svg('arrow-right') ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="side-card" style="background:var(--surface-2)">
            <h2 class="side-card__title"><?= Icon::svg('sparkles') ?> Le bon réflexe</h2>
            <p class="text-sm text-muted" style="margin:0">
                Dites d'abord <strong>pourquoi vous appelez</strong>, puis posez une question ouverte.
                Le prospect parle plus que vous : c'est bon signe.
            </p>
        </div>
    </aside>
</div>

<?= App\Core\App::render('partials/objections-drawer', ['objections' => $objections ?? []]) ?>
