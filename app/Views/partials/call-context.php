<?php
/**
 * Bandeau du contexte d'appel (secteur, étape, prospect) + actions permanentes.
 *
 * @var array<string, mixed> $context
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$context = $context ?? [];
$sectorName = (string) ($context['sector_name'] ?? '');
$stageName = (string) ($context['stage_name'] ?? '');
$stageColor = (string) ($context['stage']['color'] ?? 'blue');
$entreprise = (string) ($context['entreprise_client'] ?? '');
$contact = (string) ($context['contact'] ?? '');
?>
<div class="call-context" role="group" aria-label="Contexte de l'appel en cours">
    <span class="call-context__item">
        <?= Icon::svg('building') ?>
        <span>Secteur : <strong><?= Sanitizer::e($sectorName ?: '—') ?></strong></span>
    </span>
    <span class="call-context__sep" aria-hidden="true"></span>
    <span class="call-context__item">
        <?= Icon::svg('flag') ?>
        <span>Étape : <strong><?= Sanitizer::e($stageName ?: '—') ?></strong></span>
    </span>
    <?php if ($entreprise !== '' || $contact !== ''): ?>
        <span class="call-context__sep" aria-hidden="true"></span>
        <span class="call-context__item">
            <?= Icon::svg('briefcase') ?>
            <strong><?= Sanitizer::e($entreprise !== '' ? $entreprise : $contact) ?></strong>
        </span>
        <?php if ($entreprise !== '' && $contact !== ''): ?>
            <span class="call-context__item text-muted-2">· <?= Sanitizer::e($contact) ?></span>
        <?php endif; ?>
    <?php endif; ?>

    <span class="call-context__actions">
        <a class="btn btn--sm btn--ghost" href="<?= Sanitizer::e(View::url('/')) ?>">
            <?= Icon::svg('arrow-left') ?> <span class="nowrap">Nouvel appel</span>
        </a>
        <a class="btn btn--sm btn--secondary" href="<?= Sanitizer::e(View::url('/appel/terminer')) ?>">
            <?= Icon::svg('check') ?> <span class="nowrap">Terminer l'appel</span>
        </a>
    </span>
</div>
