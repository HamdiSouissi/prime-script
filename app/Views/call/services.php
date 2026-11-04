<?php
/**
 * Écran 3 — Choix du service (4 grandes cartes).
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$context = $context ?? [];
$services = $services ?? [];
?>
<?= App\Core\App::render('partials/stepper', ['step' => 3]) ?>
<?= App\Core\App::render('partials/call-context', ['context' => $context]) ?>

<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('layers', '', 14) ?> Étape 3 · Service</span>
        <h1>De quoi le prospect a-t-il besoin ?</h1>
        <p class="page-head__sub">Cliquez sur le service qui correspond à son besoin. Vous pourrez en changer à tout moment, sans perdre le contexte.</p>
    </div>
</div>

<?php if ($services === []): ?>
    <div class="card empty">
        <span class="empty__icon"><?= Icon::svg('layers') ?></span>
        <h3>Aucun service actif</h3>
        <p>L'administrateur doit activer au moins un service pour continuer.</p>
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/')) ?>">Retour au contexte</a>
    </div>
<?php else: ?>
    <div class="service-grid">
        <?php foreach ($services as $service):
            $isCurrent = (int) ($context['service_id'] ?? 0) === (int) $service['id'];
            ?>
            <a class="service-card<?= $isCurrent ? ' is-current' : '' ?>" href="<?= Sanitizer::e(View::url('/appel/speech?service=' . (int) $service['id'])) ?>">
                <span class="service-card__icon"><?= Icon::svg((string) $service['icon']) ?></span>
                <span class="service-card__title"><?= Sanitizer::e($service['name']) ?></span>
                <p class="service-card__desc"><?= Sanitizer::e($service['description']) ?></p>
                <span class="service-card__foot">
                    <span class="service-card__price">
                        <?php if ($service['price_from'] !== null): ?>
                            À partir de <?= Sanitizer::e(number_format((int) $service['price_from'], 0, ',', ' ')) ?> € <small>HT · sur devis écrit</small>
                        <?php else: ?>
                            Sur devis <small>HT</small>
                        <?php endif; ?>
                    </span>
                    <span class="service-card__go" aria-hidden="true"><?= Icon::svg('arrow-right') ?></span>
                </span>
                <?php if ($isCurrent): ?>
                    <span class="badge badge--accent" style="position:absolute;top:1rem;right:1rem">En cours</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card mt-4" style="display:flex;flex-wrap:wrap;gap:var(--s-3);align-items:center;justify-content:space-between">
    <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/appel/presentation')) ?>">
        <?= Icon::svg('arrow-left') ?> Revenir à la présentation
    </a>
    <span class="text-xs text-muted-2">Un doute ? Posez la question : « Qu'est-ce qui vous prend le plus de temps aujourd'hui ? »</span>
</div>

<?= App\Core\App::render('partials/objections-drawer', ['objections' => $objections ?? []]) ?>
