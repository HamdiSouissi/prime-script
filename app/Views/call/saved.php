<?php
/**
 * Confirmation après enregistrement d'un appel : retour immédiat vers un nouvel appel.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$call = $call ?? null;
?>
<div class="shell-narrow" style="width:100%;margin:0 auto;max-width:640px">
    <div class="card">
        <div class="success-hero">
            <span class="success-hero__icon"><?= Icon::svg('check-circle') ?></span>
            <h1>Appel enregistré</h1>
            <p>Merci ! Le suivi est noté pour toute l'équipe.</p>

            <?php if ($call !== null): ?>
                <div style="text-align:left;margin-top:var(--s-4)">
                    <dl class="summary">
                        <div><dt>Résultat</dt><dd><span class="badge badge--<?= Sanitizer::e($tone ?? 'muted') ?>"><?= Sanitizer::e($result ?? '') ?></span></dd></div>
                        <?php if (!empty($call['entreprise_client'])): ?>
                            <div><dt>Entreprise</dt><dd><?= Sanitizer::e($call['entreprise_client']) ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($call['callback_date'])): ?>
                            <div><dt>Rappel prévu</dt><dd><?= Sanitizer::e(View::dateFr((string) $call['callback_date'])) ?></dd></div>
                        <?php endif; ?>
                        <div><dt>Heure</dt><dd><?= Sanitizer::e(View::dateFr((string) $call['created_at'], true)) ?></dd></div>
                    </dl>
                </div>
            <?php endif; ?>

            <div class="btn-row" style="justify-content:center;margin-top:var(--s-6)">
                <a class="btn btn--lg" href="<?= Sanitizer::e(View::url('/')) ?>">
                    <?= Icon::svg('phone') ?> Nouvel appel
                </a>
                <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/historique')) ?>">
                    <?= Icon::svg('clock') ?> Voir mes appels
                </a>
            </div>
        </div>
    </div>
</div>
