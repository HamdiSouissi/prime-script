<?php
/**
 * Tableau de bord administrateur.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\Call;
use App\Models\SpeechBlock;

$counters = $counters ?? [];
$results = $results ?? [];
$byUser = $byUser ?? [];
$byService = $byService ?? [];
$bySector = $bySector ?? [];
$week = $week ?? [];
$content = $content ?? [];
$blocksByType = $blocksByType ?? [];
$recentCalls = $recentCalls ?? [];
$callbacks = $callbacks ?? [];
$days = (int) ($days ?? 14);
$maxWeek = max(1, ...array_map(static fn (array $d): int => (int) $d['total'], $week ?: [['total' => 1]]));
$toneColors = [
    'success' => 'var(--ok)',
    'info'    => 'var(--info)',
    'warn'    => 'var(--warn)',
    'muted'   => 'var(--muted-2)',
    'danger'  => 'var(--err)',
];
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('grid', '', 14) ?> Administration</span>
        <h1>Tableau de bord</h1>
        <p class="page-head__sub">Vue d'ensemble des <?= $days ?> derniers jours · <span class="text-muted-2">mis à jour à <?= Sanitizer::e(date('H:i')) ?></span></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/admin/appels/export.csv')) ?>">
            <?= Icon::svg('download') ?> Export CSV
        </a>
        <a class="btn" href="<?= Sanitizer::e(View::url('/admin/blocs/nouveau')) ?>">
            <?= Icon::svg('plus') ?> Nouveau bloc
        </a>
    </div>
</div>

<div class="stats mb-4">
    <div class="stat">
        <p class="stat__label">Aujourd'hui</p>
        <p class="stat__value"><?= (int) ($counters['today'] ?? 0) ?></p>
        <p class="stat__hint">appels enregistrés</p>
    </div>
    <div class="stat">
        <p class="stat__label">7 jours</p>
        <p class="stat__value"><?= (int) ($counters['week'] ?? 0) ?></p>
        <p class="stat__hint">appels</p>
    </div>
    <div class="stat">
        <p class="stat__label">30 jours</p>
        <p class="stat__value"><?= (int) ($counters['month'] ?? 0) ?></p>
        <p class="stat__hint">appels</p>
    </div>
    <div class="stat">
        <p class="stat__label">RDV (30 j)</p>
        <p class="stat__value text-ok"><?= (int) ($counters['rdv_month'] ?? 0) ?></p>
        <p class="stat__hint"><?= (int) ($counters['rdv_rate'] ?? 0) ?> % des appels</p>
    </div>
    <div class="stat">
        <p class="stat__label">Devis (30 j)</p>
        <p class="stat__value" style="color:var(--info)"><?= (int) ($counters['devis_month'] ?? 0) ?></p>
        <p class="stat__hint">demandes de devis</p>
    </div>
    <div class="stat">
        <p class="stat__label">Taux RDV + devis</p>
        <p class="stat__value"><?= (int) ($counters['conversion'] ?? 0) ?><span style="font-size:1rem"> %</span></p>
        <p class="stat__hint">sur 30 jours</p>
    </div>
</div>

<div class="admin-grid admin-grid--2 mb-4">
    <section class="chart-card" aria-labelledby="week-title">
        <div class="chart-card__head">
            <h2 id="week-title">Appels des 7 derniers jours</h2>
            <span class="chart-card__hint"><?= array_sum(array_map(static fn (array $d): int => (int) $d['total'], $week)) ?> appels</span>
        </div>
        <div class="week-chart" role="img" aria-label="Appels par jour sur les 7 derniers jours">
            <?php foreach ($week as $day):
                $height = max(3, (int) round(((int) $day['total'] / $maxWeek) * 100));
                ?>
                <div class="week-chart__col" title="<?= Sanitizer::e($day['short']) ?> : <?= (int) $day['total'] ?> appel(s)">
                    <span class="week-chart__num"><?= (int) $day['total'] ?></span>
                    <div class="week-chart__bar" style="height:<?= $height ?>%"></div>
                    <span class="week-chart__day"><?= Sanitizer::e($day['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="chart-card" aria-labelledby="results-title">
        <div class="chart-card__head">
            <h2 id="results-title">Répartition des résultats</h2>
            <span class="chart-card__hint"><?= $days ?> jours</span>
        </div>
        <?php
        $totalResults = array_sum(array_map(static fn (array $r): int => (int) $r['total'], $results));
        ?>
        <?php if ($totalResults === 0): ?>
            <div class="empty" style="padding:var(--s-6) var(--s-3)">
                <p class="text-sm text-muted" style="margin:0">Aucun appel enregistré sur la période.</p>
            </div>
        <?php else: ?>
            <div class="stack stack--sm">
                <?php foreach ($results as $item): ?>
                    <div class="meter">
                        <div class="meter__top">
                            <span><?= Sanitizer::e($item['label']) ?></span>
                            <span class="meter__value">
                                <?= (int) $item['total'] ?>
                                <span class="text-xs text-muted-2">(<?= (int) $item['share'] ?> %)</span>
                            </span>
                        </div>
                        <div class="meter__bar">
                            <div class="meter__fill" style="width:<?= max(1, (int) $item['share']) ?>%;background:<?= Sanitizer::e($toneColors[$item['tone']] ?? 'var(--accent)') ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="admin-grid mb-4">
    <section class="chart-card" aria-labelledby="users-title">
        <div class="chart-card__head">
            <h2 id="users-title">Appels par commercial</h2>
            <a class="chart-card__hint" href="<?= Sanitizer::e(View::url('/admin/utilisateurs')) ?>">Gérer l'équipe →</a>
        </div>
        <?php
        $maxUser = max(1, ...array_map(static fn (array $u): int => (int) $u['total'], $byUser ?: [['total' => 1]]));
        ?>
        <?php if ($byUser === []): ?>
            <p class="text-sm text-muted">Aucun utilisateur.</p>
        <?php else: ?>
            <div class="bars">
                <?php foreach ($byUser as $user): ?>
                    <div class="bars__row">
                        <span class="bars__label" title="<?= Sanitizer::e($user['name']) ?>"><?= Sanitizer::e($user['name']) ?></span>
                        <span class="bars__track">
                            <span class="bars__fill" style="width:<?= (int) round(((int) $user['total'] / $maxUser) * 100) ?>%"></span>
                        </span>
                        <span class="bars__value"><?= (int) $user['total'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="chart-card" aria-labelledby="services-title">
        <div class="chart-card__head">
            <h2 id="services-title">Services les plus utilisés</h2>
            <a class="chart-card__hint" href="<?= Sanitizer::e(View::url('/admin/services')) ?>">Gérer →</a>
        </div>
        <?php $maxSvc = max(1, ...array_map(static fn (array $s): int => (int) $s['total'], $byService ?: [['total' => 1]])); ?>
        <div class="bars">
            <?php foreach ($byService as $service): ?>
                <div class="bars__row">
                    <span class="bars__label" title="<?= Sanitizer::e($service['name']) ?>"><?= Sanitizer::e($service['name']) ?></span>
                    <span class="bars__track">
                        <span class="bars__fill" style="width:<?= (int) round(((int) $service['total'] / $maxSvc) * 100) ?>%"></span>
                    </span>
                    <span class="bars__value"><?= (int) $service['total'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="chart-card" aria-labelledby="sectors-title">
        <div class="chart-card__head">
            <h2 id="sectors-title">Secteurs les plus utilisés</h2>
            <a class="chart-card__hint" href="<?= Sanitizer::e(View::url('/admin/secteurs')) ?>">Gérer →</a>
        </div>
        <?php $maxSec = max(1, ...array_map(static fn (array $s): int => (int) $s['total'], $bySector ?: [['total' => 1]])); ?>
        <div class="bars">
            <?php foreach ($bySector as $sector): ?>
                <div class="bars__row">
                    <span class="bars__label" title="<?= Sanitizer::e($sector['name']) ?>"><?= Sanitizer::e($sector['name']) ?></span>
                    <span class="bars__track">
                        <span class="bars__fill" style="width:<?= (int) round(((int) $sector['total'] / $maxSec) * 100) ?>%"></span>
                    </span>
                    <span class="bars__value"><?= (int) $sector['total'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="admin-grid admin-grid--2">
    <section class="chart-card" aria-labelledby="content-title">
        <div class="chart-card__head">
            <h2 id="content-title">Contenus publiés</h2>
            <a class="chart-card__hint" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">Voir tout →</a>
        </div>
        <div class="stack stack--sm">
            <?php foreach (SpeechBlock::TYPES as $type => $label): ?>
                <?php $stat = $blocksByType[$type] ?? ['total' => 0, 'active' => 0, 'generic' => 0]; ?>
                <div class="row row--between" style="gap:var(--s-2);font-size:var(--fs-sm)">
                    <span class="row" style="gap:0.4rem;min-width:0">
                        <?= Icon::svg(SpeechBlock::TYPE_ICONS[$type] ?? 'file', 'text-accent') ?>
                        <span class="nowrap" style="overflow:hidden;text-overflow:ellipsis"><?= Sanitizer::e($label) ?></span>
                    </span>
                    <span class="row" style="gap:0.4rem">
                        <?php if ((int) $stat['active'] === 0): ?>
                            <span class="badge badge--warn">Aucun actif</span>
                        <?php else: ?>
                            <span class="badge badge--ok"><?= (int) $stat['active'] ?> actif<?= (int) $stat['active'] > 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                        <span class="badge badge--muted"><?= (int) $stat['total'] ?> au total</span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="row" style="gap:var(--s-2);margin-top:var(--s-4);padding-top:var(--s-3);border-top:1px solid var(--line-soft)">
            <span class="badge badge--accent"><?= (int) ($content['objections'] ?? 0) ?> objections</span>
            <span class="badge badge--muted"><?= (int) ($content['users'] ?? 0) ?> comptes actifs</span>
        </div>
    </section>

    <section class="chart-card" aria-labelledby="recent-title">
        <div class="chart-card__head">
            <h2 id="recent-title">Derniers appels</h2>
            <a class="chart-card__hint" href="<?= Sanitizer::e(View::url('/admin/appels')) ?>">Journal complet →</a>
        </div>
        <?php if ($recentCalls === []): ?>
            <div class="empty" style="padding:var(--s-6) var(--s-3)">
                <p class="text-sm text-muted" style="margin:0">Aucun appel enregistré pour l'instant.</p>
            </div>
        <?php else: ?>
            <ul class="stack stack--sm" style="list-style:none;padding:0;margin:0">
                <?php foreach ($recentCalls as $call): ?>
                    <li style="margin:0;display:flex;gap:var(--s-2);align-items:flex-start;font-size:var(--fs-xs)">
                        <span class="badge badge--<?= Sanitizer::e(Call::resultTone((string) $call['result'])) ?>" style="flex:none">
                            <?= Sanitizer::e(Call::resultLabel((string) $call['result'])) ?>
                        </span>
                        <span class="grow text-muted" style="min-width:0">
                            <strong style="color:var(--ink)"><?= Sanitizer::e($call['entreprise_client'] !== '' ? $call['entreprise_client'] : 'Prospect') ?></strong>
                            · <?= Sanitizer::e((string) $call['user_name']) ?>
                            <span class="text-muted-2">— <?= Sanitizer::e(View::ago((string) $call['created_at'])) ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($callbacks !== []): ?>
            <div class="mt-4" style="padding-top:var(--s-3);border-top:1px solid var(--line-soft)">
                <h3 class="text-sm fw-600 mb-2"><?= Icon::svg('bell', '', 16) ?> Rappels à traiter</h3>
                <ul class="stack stack--sm" style="list-style:none;padding:0;margin:0">
                    <?php foreach ($callbacks as $callback):
                        $late = $callback['callback_date'] < date('Y-m-d');
                        ?>
                        <li style="margin:0;font-size:var(--fs-xs);display:flex;gap:var(--s-2);align-items:center">
                            <span class="badge badge--<?= $late ? 'err' : 'warn' ?>"><?= Sanitizer::e(View::dateFr((string) $callback['callback_date'])) ?></span>
                            <span class="text-muted grow" style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= Sanitizer::e($callback['entreprise_client'] !== '' ? $callback['entreprise_client'] : 'Prospect') ?>
                                · <?= Sanitizer::e((string) $callback['user_name']) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </section>
</div>
