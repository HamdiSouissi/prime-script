<?php
/**
 * Guide « Bien démarrer » — méthode, dialogue d'exemple, glossaire, modèles de mail.
 *
 * @var array<string, mixed> $guide
 * @var array<int, array<string, string>> $mailTemplates
 * @var array<string, string> $variables
 * @var bool $hasContext
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$guide = $guide ?? [];
$mailTemplates = $mailTemplates ?? [];
$hasContext = $hasContext ?? false;
$rules = $guide['rules'] ?? [];
$steps = $guide['steps'] ?? [];
$mistakes = $guide['mistakes'] ?? [];
$dialogue = $guide['dialogue'] ?? [];
$glossary = $guide['glossary'] ?? [];
$reflexes = $guide['reflexes'] ?? [];
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('book', '', 14) ?> Guide</span>
        <h1><?= Sanitizer::e((string) ($guide['title'] ?? 'Bien démarrer')) ?></h1>
        <p class="page-head__sub"><?= Sanitizer::e((string) ($guide['subtitle'] ?? '')) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/guide/modeles')) ?>">
            <?= Icon::svg('mail') ?> Modèles de mail
        </a>
        <a class="btn" href="<?= Sanitizer::e(View::url('/')) ?>">
            <?= Icon::svg('phone') ?> Nouvel appel
        </a>
    </div>
</div>

<nav class="guide-toc card mb-4" aria-label="Sommaire du guide">
    <ul>
        <?php foreach ([
            'regles'     => 'Règles d\'or',
            'structure'  => 'Structure d\'un appel',
            'erreurs'    => 'Erreurs à éviter',
            'dialogue'   => 'Exemple de dialogue',
            'glossaire'  => 'Glossaire',
            'modeles'    => 'Modèles de mail',
        ] as $anchor => $label): ?>
            <li><a href="#<?= Sanitizer::e($anchor) ?>"><?= Sanitizer::e($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- ===== Règles d'or -->
<section class="mb-6" id="regles" aria-labelledby="regles-title">
    <div class="section-label">
        <h2 id="regles-title"><?= Sanitizer::e((string) ($rules['title'] ?? 'Les règles d\'or')) ?></h2>
        <span class="section-label__hint"><?= Sanitizer::e((string) ($rules['intro'] ?? '')) ?></span>
    </div>
    <div class="guide-grid">
        <?php foreach ($rules['items'] ?? [] as $rule): ?>
            <article class="card guide-card">
                <span class="guide-card__icon"><?= Icon::svg((string) ($rule['icon'] ?? 'check')) ?></span>
                <h3><?= Sanitizer::e((string) ($rule['title'] ?? '')) ?></h3>
                <p><?= Sanitizer::e((string) ($rule['text'] ?? '')) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- ===== Structure -->
<section class="mb-6" id="structure" aria-labelledby="structure-title">
    <div class="section-label">
        <h2 id="structure-title"><?= Sanitizer::e((string) ($steps['title'] ?? '')) ?></h2>
        <span class="section-label__hint"><?= Sanitizer::e((string) ($steps['intro'] ?? '')) ?></span>
    </div>
    <ol class="guide-steps">
        <?php foreach ($steps['items'] ?? [] as $step): ?>
            <li class="card guide-step">
                <span class="guide-step__num" aria-hidden="true"><?= Sanitizer::e((string) ($step['num'] ?? '')) ?></span>
                <div class="guide-step__body">
                    <h3>
                        <?= Sanitizer::e((string) ($step['title'] ?? '')) ?>
                        <span class="badge badge--muted"><?= Sanitizer::e((string) ($step['time'] ?? '')) ?></span>
                    </h3>
                    <p><?= Sanitizer::e((string) ($step['text'] ?? '')) ?></p>
                    <?php if (!empty($step['tip'])): ?>
                        <p class="guide-tip"><em>Conseil : <?= Sanitizer::e((string) $step['tip']) ?></em></p>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<!-- ===== Erreurs -->
<section class="mb-6" id="erreurs" aria-labelledby="erreurs-title">
    <div class="section-label">
        <h2 id="erreurs-title"><?= Sanitizer::e((string) ($mistakes['title'] ?? '')) ?></h2>
        <span class="section-label__hint"><?= Sanitizer::e((string) ($mistakes['intro'] ?? '')) ?></span>
    </div>
    <div class="card">
        <ul class="guide-mistakes">
            <?php foreach ($mistakes['items'] ?? [] as $mistake): ?>
                <li>
                    <?= Icon::svg('x-circle', 'guide-mistakes__icon') ?>
                    <span><?= Sanitizer::e((string) $mistake) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- ===== Dialogue -->
<section class="mb-6" id="dialogue" aria-labelledby="dialogue-title">
    <div class="section-label">
        <h2 id="dialogue-title"><?= Sanitizer::e((string) ($dialogue['title'] ?? '')) ?></h2>
        <span class="section-label__hint"><?= Sanitizer::e((string) ($dialogue['intro'] ?? '')) ?></span>
    </div>
    <div class="card">
        <div class="dialogue">
            <?php foreach ($dialogue['lines'] ?? [] as $line): ?>
                <?php $isCommercial = ($line['who'] ?? '') === 'commercial'; ?>
                <div class="dialogue__line dialogue__line--<?= $isCommercial ? 'me' : 'them' ?>">
                    <span class="dialogue__who"><?= $isCommercial ? 'Vous' : 'Le prospect' ?></span>
                    <p><?= Sanitizer::e((string) ($line['text'] ?? '')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($dialogue['notes'])): ?>
            <hr class="guide-rule">
            <ul class="guide-notes">
                <?php foreach ($dialogue['notes'] as $note): ?>
                    <li><?= Sanitizer::e((string) $note) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<!-- ===== Glossaire -->
<section class="mb-6" id="glossaire" aria-labelledby="glossaire-title">
    <div class="section-label">
        <h2 id="glossaire-title"><?= Sanitizer::e((string) ($glossary['title'] ?? '')) ?></h2>
        <span class="section-label__hint"><?= Sanitizer::e((string) ($glossary['intro'] ?? '')) ?></span>
    </div>
    <dl class="guide-glossary card">
        <?php foreach ($glossary['items'] ?? [] as $entry): ?>
            <div class="guide-glossary__item">
                <dt><?= Sanitizer::e((string) ($entry['term'] ?? '')) ?></dt>
                <dd><?= Sanitizer::e((string) ($entry['plain'] ?? '')) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>

<!-- ===== Modèles de mail -->
<section class="mb-6" id="modeles" aria-labelledby="modeles-title">
    <div class="section-label">
        <h2 id="modeles-title">Modèles de mail de suivi</h2>
        <span class="section-label__hint">
            <?= $hasContext ? 'Remplis avec l\'appel en cours' : 'Exemples : les noms seront remplacés pendant un appel' ?>
        </span>
    </div>

    <div class="stack stack--lg">
        <?php foreach ($mailTemplates as $index => $template): ?>
            <article class="card">
                <div class="row row--between" style="gap:var(--s-2);align-items:center;flex-wrap:wrap">
                    <div>
                        <span class="badge badge--accent"><?= Sanitizer::e($template['when']) ?></span>
                        <h3 style="margin:var(--s-2) 0 0;font-size:var(--fs-md)"><?= Sanitizer::e($template['subject']) ?></h3>
                    </div>
                    <button type="button" class="btn btn--secondary btn--sm" data-copy-target="#mail-<?= $index ?>">
                        <?= Icon::svg('copy') ?> Copier le mail
                    </button>
                </div>
                <pre class="mail-preview" id="mail-<?= $index ?>"><?= Sanitizer::e($template['body']) ?></pre>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- ===== Réflexes -->
<?php if (!empty($reflexes['items'])): ?>
<section class="card" aria-labelledby="reflexes-title">
    <h2 id="reflexes-title" class="card__title"><?= Sanitizer::e((string) $reflexes['title']) ?></h2>
    <ol class="guide-notes">
        <?php foreach ($reflexes['items'] as $item): ?>
            <li><?= Sanitizer::e((string) $item) ?></li>
        <?php endforeach; ?>
    </ol>
</section>
<?php endif; ?>
