<?php
/**
 * Écran 4 — Speech du service, en blocs repliables.
 *
 * @var array<string, mixed>              $context
 * @var array<int, array<string, mixed>>  $services
 * @var array<string, array<int, array<string, mixed>>> $sections
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\SpeechBlock;

$context = $context ?? [];
$services = $services ?? [];
$sections = $sections ?? [];
$currentService = $context['service'] ?? null;
$totalBlocks = 0;
foreach ($sections as $items) {
    $totalBlocks += count($items);
}
?>
<?= App\Core\App::render('partials/stepper', ['step' => 4]) ?>
<?= App\Core\App::render('partials/call-context', ['context' => $context]) ?>

<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('target', '', 14) ?> Étape 4 · Speech</span>
        <h1><?= Sanitizer::e($currentService['name'] ?? 'Speech') ?></h1>
        <p class="page-head__sub">
            <?= Sanitizer::e($context['stage_name'] ?? '') ?> · <?= Sanitizer::e($context['sector_name'] ?? '') ?>
            — les 7 blocs du discours, dans l'ordre.
        </p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--lg" href="<?= Sanitizer::e(View::url('/appel/terminer')) ?>">
            <?= Icon::svg('check') ?> Terminer l'appel
        </a>
    </div>
</div>

<?php if ($services !== []): ?>
    <nav class="service-switcher mb-4" aria-label="Changer de service" data-service-switcher>
        <?php foreach ($services as $service):
            $isActive = (int) $service['id'] === (int) ($context['service_id'] ?? 0);
            ?>
            <a class="service-pill<?= $isActive ? ' is-active' : '' ?>" href="<?= Sanitizer::e(View::url('/appel/speech?service=' . (int) $service['id'])) ?>"
               <?= $isActive ? 'aria-current="true"' : '' ?>>
                <?= Icon::svg((string) $service['icon']) ?>
                <?= Sanitizer::e($service['name']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<div class="speech-layout">
    <div class="speech-main">
        <?php if ($totalBlocks === 0): ?>
            <div class="card empty">
                <span class="empty__icon"><?= Icon::svg('layers') ?></span>
                <h3>Aucun contenu publié pour ce service</h3>
                <p>Ajoutez des blocs « découverte », « argumentaire », « preuve », « tarif » ou « appel à l'action » pour ce service, ce secteur ou cette étape.</p>
                <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/appel/services')) ?>">Changer de service</a>
            </div>
        <?php endif; ?>

        <?php foreach (SpeechBlock::CALL_TYPES as $index => $type):
            $blocks = $sections[$type] ?? [];
            $label = SpeechBlock::typeLabel($type);
            $icon = SpeechBlock::TYPE_ICONS[$type] ?? 'info';
            $count = count($blocks);
            ?>
            <?php if ($count === 0 && in_array($type, ['presentation', 'accroche'], true)): continue; endif; ?>
            <section class="speech-block" data-speech-block <?= $index === 0 ? 'data-open' : '' ?> aria-labelledby="block-<?= Sanitizer::e($type) ?>">
                <button type="button" class="speech-block__toggle" data-speech-toggle
                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="panel-<?= Sanitizer::e($type) ?>">
                    <span class="speech-block__icon"><?= Icon::svg($icon) ?></span>
                    <span class="speech-block__label grow">
                        <span class="speech-block__type"><?= Sanitizer::e($label) ?></span>
                        <span class="speech-block__title">
                            <?= Sanitizer::e($blocks[0]['title'] ?? '') ?>
                        </span>
                    </span>
                    <span class="speech-block__badge badge badge--muted"><?= $count ?> bloc<?= $count > 1 ? 's' : '' ?></span>
                    <svg class="ico speech-block__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div class="speech-block__panel" id="panel-<?= Sanitizer::e($type) ?>">
                    <?php if ($count === 0): ?>
                        <div class="speech-empty">Aucun bloc publié pour « <?= Sanitizer::e($label) ?> » dans ce contexte.</div>
                    <?php else: ?>
                        <div class="stack stack--lg">
                            <?php foreach ($blocks as $block): ?>
                                <?php if (!empty($block['title']) && $count > 1): ?>
                                    <p class="text-xs text-muted-2 mb-0" style="text-transform:uppercase;letter-spacing:.08em;font-weight:600">
                                        <?= Sanitizer::e($block['title']) ?>
                                        <?php if (!empty($block['sector_name']) || !empty($block['service_name'])): ?>
                                            <span class="text-muted-2" style="text-transform:none;letter-spacing:0;font-weight:400">
                                                · <?= Sanitizer::e(implode(' · ', array_filter([(string) ($block['sector_name'] ?? ''), (string) ($block['service_name'] ?? '')]))) ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <div class="rich"><?= $block['html'] ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="card" style="display:flex;flex-wrap:wrap;gap:var(--s-3);align-items:center;justify-content:space-between">
            <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/appel/services')) ?>">
                <?= Icon::svg('arrow-left') ?> Changer de service
            </a>
            <a class="btn btn--lg" href="<?= Sanitizer::e(View::url('/appel/terminer')) ?>">
                <?= Icon::svg('flag') ?> Terminer l'appel
            </a>
        </div>
    </div>

    <aside class="side-panel" aria-label="Actions pendant l'appel">
        <div class="side-card">
            <h2 class="side-card__title"><?= Icon::svg('shield') ?> Objections fréquentes</h2>
            <?php if (($objections ?? []) === []): ?>
                <p class="text-sm text-muted" style="margin:0">Aucune objection publiée pour ce contexte.</p>
            <?php else: ?>
                <div class="objection-list">
                    <?php foreach (array_slice($objections ?? [], 0, 4) as $objection): ?>
                        <div class="objection" data-objection>
                            <button type="button" class="objection__button" data-accordion-button
                                    aria-expanded="false" aria-controls="side-objection-<?= (int) $objection['id'] ?>">
                                <?= Icon::svg('message') ?>
                                <span class="grow"><?= Sanitizer::e($objection['question']) ?></span>
                                <svg class="ico objection__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div class="objection__answer" id="side-objection-<?= (int) $objection['id'] ?>">
                                <div class="rich rich--tight mt-2"><?= $objection['answer_html'] ?? '' ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--soft btn--sm mt-3" data-drawer-open style="width:100%">
                    <?= Icon::svg('list') ?> Toutes les objections (<?= count($objections) ?>)
                </button>
            <?php endif; ?>
        </div>

        <div class="side-card">
            <h2 class="side-card__title"><?= Icon::svg('flag') ?> Prochaine étape</h2>
            <p class="text-sm text-muted">
                Terminez l'appel dès que vous avez une réponse claire : c'est plus rapide et plus fiable.
            </p>
            <div class="quick-actions">
                <a class="btn btn--success" href="<?= Sanitizer::e(View::url('/appel/terminer')) ?>">
                    <?= Icon::svg('check') ?> Terminer l'appel
                </a>
                <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/appel/presentation')) ?>">
                    <?= Icon::svg('arrow-left') ?> Revoir la présentation
                </a>
            </div>
        </div>

        <?php if (!empty($context['entreprise_client']) || !empty($context['contact'])): ?>
            <div class="side-card" style="background:var(--surface-2)">
                <h2 class="side-card__title"><?= Icon::svg('briefcase') ?> Prospect</h2>
                <dl class="summary">
                    <?php if (!empty($context['entreprise_client'])): ?>
                        <div><dt>Entreprise</dt><dd><?= Sanitizer::e($context['entreprise_client']) ?></dd></div>
                    <?php endif; ?>
                    <?php if (!empty($context['contact'])): ?>
                        <div><dt>Contact</dt><dd><?= Sanitizer::e($context['contact']) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?= App\Core\App::render('partials/objections-drawer', ['objections' => $objections ?? []]) ?>
