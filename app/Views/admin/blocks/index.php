<?php
/**
 * Administration — liste filtrable des blocs de speech.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\SpeechBlock;

$blocks = $blocks ?? [];
$filters = $filters ?? [];
$taxonomy = $taxonomy ?? [];
$stats = $stats ?? ['total' => 0, 'active' => 0];
$queryBase = array_filter([
    'type'       => $filters['type'] ?? '',
    'sector_id'  => $filters['sector_id'] ?? '',
    'stage_id'   => $filters['stage_id'] ?? '',
    'service_id' => $filters['service_id'] ?? '',
    'active'     => $filters['active'] ?? '',
    'q'          => $filters['q'] ?? '',
], static fn ($v) => $v !== '' && $v !== null);
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('layers', '', 14) ?> Contenus</span>
        <h1>Blocs de speech</h1>
        <p class="page-head__sub"><?= (int) $stats['total'] ?> bloc<?= (int) $stats['total'] > 1 ? 's' : '' ?> affiché<?= (int) $stats['total'] > 1 ? 's' : '' ?>, dont <strong><?= (int) $stats['active'] ?></strong> actif<?= (int) $stats['active'] > 1 ? 's' : '' ?>. Les modifications sont visibles immédiatement côté commercial.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/admin/objections')) ?>">
            <?= Icon::svg('shield') ?> Objections
        </a>
        <a class="btn" href="<?= Sanitizer::e(View::url('/admin/blocs/nouveau')) ?>">
            <?= Icon::svg('plus') ?> Nouveau bloc
        </a>
    </div>
</div>

<details class="card card--pad-sm mb-4">
    <summary style="cursor:pointer;font-weight:600">
        <?= Icon::svg('refresh', '', 16) ?> Restaurer le contenu d'origine
    </summary>
    <p class="text-sm text-muted mt-3" style="margin-bottom:var(--s-3)">
        Le contenu livré avec l'application (présentations, accroches, questions, argumentaires,
        tarifs, appels à l'action et objections) est réappliqué depuis
        <code>database/content/</code>. Vos blocs créés à la main ne sont pas touchés, et
        chaque bloc modifié archive sa version précédente dans son historique.
    </p>
    <?= View::actionForm(
        '/admin/contenu/restaurer',
        [],
        Icon::svg('refresh') . ' Restaurer le contenu d\'origine',
        'btn btn--secondary',
        'Réappliquer le contenu livré ? Vos blocs créés à la main ne seront pas touchés.'
    ) ?>
</details>

<form method="get" action="<?= Sanitizer::e(View::url('/admin/blocs')) ?>" class="filters" aria-label="Filtrer les blocs">
    <div class="field">
        <label for="f-type">Type</label>
        <select id="f-type" name="type">
            <option value="">Tous les types</option>
            <?php foreach ($taxonomy['types'] ?? [] as $key => $label): ?>
                <option value="<?= Sanitizer::e($key) ?>" <?= ($filters['type'] ?? '') === $key ? 'selected' : '' ?>><?= Sanitizer::e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-sector">Secteur</label>
        <select id="f-sector" name="sector_id">
            <option value="">Tous</option>
            <option value="none" <?= ($filters['sector_id'] ?? '') === 'none' ? 'selected' : '' ?>>Génériques (tous secteurs)</option>
            <?php foreach ($taxonomy['sectors'] ?? [] as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['sector_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-stage">Étape</label>
        <select id="f-stage" name="stage_id">
            <option value="">Toutes</option>
            <option value="none" <?= ($filters['stage_id'] ?? '') === 'none' ? 'selected' : '' ?>>Génériques (toutes étapes)</option>
            <?php foreach ($taxonomy['stages'] ?? [] as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['stage_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-service">Service</label>
        <select id="f-service" name="service_id">
            <option value="">Tous</option>
            <option value="none" <?= ($filters['service_id'] ?? '') === 'none' ? 'selected' : '' ?>>Génériques (tous services)</option>
            <?php foreach ($taxonomy['services'] ?? [] as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['service_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-active">Statut</label>
        <select id="f-active" name="active">
            <option value="">Tous</option>
            <option value="1" <?= ($filters['active'] ?? '') === '1' ? 'selected' : '' ?>>Actifs</option>
            <option value="0" <?= ($filters['active'] ?? '') === '0' ? 'selected' : '' ?>>Inactifs</option>
        </select>
    </div>
    <div class="field" style="flex:2 1 12rem">
        <label for="f-q">Recherche</label>
        <input type="search" id="f-q" name="q" value="<?= Sanitizer::e($filters['q'] ?? '') ?>" placeholder="Titre ou contenu…" data-local-search="#blocks-table">
    </div>
    <div class="filters__actions">
        <button type="submit" class="btn btn--sm"><?= Icon::svg('filter') ?> Filtrer</button>
        <?php if ($queryBase !== []): ?>
            <a class="btn btn--sm btn--ghost" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($blocks === []): ?>
    <div class="card empty">
        <span class="empty__icon"><?= Icon::svg('layers') ?></span>
        <h3><?= $queryBase !== [] ? 'Aucun bloc ne correspond à ces filtres' : 'Aucun bloc de speech' ?></h3>
        <p><?= $queryBase !== [] ? 'Élargissez les filtres ou créez un bloc pour ce contexte.' : 'Commencez par créer une présentation, des accroches et des questions de découverte.' ?></p>
        <div class="btn-row" style="justify-content:center">
            <?php if ($queryBase !== []): ?>
                <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">Voir tous les blocs</a>
            <?php endif; ?>
            <a class="btn" href="<?= Sanitizer::e(View::url('/admin/blocs/nouveau')) ?>"><?= Icon::svg('plus') ?> Nouveau bloc</a>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrap" id="blocks-table">
        <div class="table-scroll">
            <table class="table">
                <caption class="visually-hidden">Blocs de speech</caption>
                <thead>
                    <tr>
                        <th style="width:1%">Ordre</th>
                        <th>Type / titre</th>
                        <th>Ciblage</th>
                        <th class="is-nowrap">Statut</th>
                        <th class="is-nowrap" style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocks as $block):
                        $isActive = (int) $block['is_active'] === 1;
                        $targets = array_filter([
                            $block['sector_name'] ?? null,
                            $block['stage_name'] ?? null,
                            $block['service_name'] ?? null,
                        ]);
                        ?>
                        <tr>
                            <td class="is-nowrap">
                                <div class="order-btns">
                                    <?= View::actionForm('/admin/blocs/' . (int) $block['id'] . '/deplacer', ['direction' => 'up'], Icon::svg('chevron-up', '', 16), 'btn btn--ghost btn--sm', '', 'Monter') ?>
                                    <?= View::actionForm('/admin/blocs/' . (int) $block['id'] . '/deplacer', ['direction' => 'down'], Icon::svg('chevron-down', '', 16), 'btn btn--ghost btn--sm', '', 'Descendre') ?>
                                </div>
                            </td>
                            <td>
                                <div class="row" style="gap:var(--s-2);flex-wrap:nowrap;align-items:flex-start">
                                    <span class="speech-block__icon" style="width:2rem;height:2rem;flex:none">
                                        <?= Icon::svg(SpeechBlock::TYPE_ICONS[(string) $block['type']] ?? 'file', '', 16) ?>
                                    </span>
                                    <div style="min-width:0">
                                        <span class="badge badge--accent" style="margin-bottom:.25rem"><?= Sanitizer::e(SpeechBlock::typeLabel((string) $block['type'])) ?></span>
                                        <a href="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/modifier')) ?>"
                                           style="display:block;font-weight:600;color:var(--ink);text-decoration:none">
                                            <?= Sanitizer::e($block['title'] !== '' ? $block['title'] : '(sans titre)') ?>
                                        </a>
                                        <span class="text-xs text-muted" style="display:block;max-width:42ch;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                            <?= Sanitizer::e(View::excerpt((string) $block['content'], 90)) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($targets === []): ?>
                                    <span class="badge badge--muted">Tous contextes</span>
                                <?php else: ?>
                                    <div class="row" style="gap:.25rem">
                                        <?php foreach ($targets as $target): ?>
                                            <span class="badge"><?= Sanitizer::e($target) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <span class="text-xs text-muted-2">Position <?= (int) $block['position'] ?></span>
                            </td>
                            <td class="is-nowrap">
                                <?= View::actionForm(
                                    '/admin/blocs/' . (int) $block['id'] . '/basculer',
                                    [],
                                    '<span class="badge badge--' . ($isActive ? 'ok' : 'muted') . ' badge--dot">' . ($isActive ? 'Actif' : 'Inactif') . '</span>',
                                    'btn btn--sm ' . ($isActive ? 'btn--soft' : 'btn--ghost'),
                                    '',
                                    $isActive ? 'Désactiver ce bloc' : 'Activer ce bloc'
                                ) ?>
                            </td>
                            <td>
                                <div class="cell-actions">
                                    <a class="btn btn--ghost btn--sm" href="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/modifier')) ?>" title="Modifier">
                                        <?= Icon::svg('edit', '', 16) ?><span class="visually-hidden">Modifier</span>
                                    </a>
                                    <a class="btn btn--ghost btn--sm" href="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/historique')) ?>" title="Historique des versions">
                                        <?= Icon::svg('history', '', 16) ?><span class="visually-hidden">Historique</span>
                                    </a>
                                    <?= View::actionForm('/admin/blocs/' . (int) $block['id'] . '/dupliquer', [], Icon::svg('copy', '', 16), 'btn btn--ghost btn--sm', '', 'Dupliquer') ?>
                                    <?= View::actionForm(
                                        '/admin/blocs/' . (int) $block['id'] . '/supprimer',
                                        [],
                                        Icon::svg('trash', '', 16),
                                        'btn btn--ghost btn--sm',
                                        'Supprimer définitivement « ' . ($block['title'] !== '' ? $block['title'] : 'ce bloc') . ' » ? Cette action est irréversible.',
                                        'Supprimer'
                                    ) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-sm text-muted mt-3" data-search-empty hidden>Aucun bloc ne correspond à la recherche.</p>
<?php endif; ?>
