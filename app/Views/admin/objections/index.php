<?php
/**
 * Administration — liste des objections.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Services\SpeechRenderer;

$objections = $objections ?? [];
$filters = $filters ?? [];
$taxonomy = $taxonomy ?? [];
$queryBase = array_filter([
    'active'     => $filters['active'] ?? '',
    'service_id' => $filters['service_id'] ?? '',
    'sector_id'  => $filters['sector_id'] ?? '',
    'q'          => $filters['q'] ?? '',
], static fn ($v) => $v !== '' && $v !== null);
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('shield', '', 14) ?> Contenus</span>
        <h1>Objections</h1>
        <p class="page-head__sub"><?= count($objections) ?> objection<?= count($objections) > 1 ? 's' : '' ?> · accessibles pendant l'appel via le bouton « Objections ».</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">
            <?= Icon::svg('layers') ?> Blocs de speech
        </a>
        <a class="btn" href="<?= Sanitizer::e(View::url('/admin/objections/nouveau')) ?>">
            <?= Icon::svg('plus') ?> Nouvelle objection
        </a>
    </div>
</div>

<form method="get" action="<?= Sanitizer::e(View::url('/admin/objections')) ?>" class="filters" aria-label="Filtrer les objections">
    <div class="field">
        <label for="f-service">Service</label>
        <select id="f-service" name="service_id">
            <option value="">Tous</option>
            <option value="none" <?= ($filters['service_id'] ?? '') === 'none' ? 'selected' : '' ?>>Générales (tous services)</option>
            <?php foreach ($taxonomy['services'] ?? [] as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['service_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-sector">Secteur</label>
        <select id="f-sector" name="sector_id">
            <option value="">Tous</option>
            <option value="none" <?= ($filters['sector_id'] ?? '') === 'none' ? 'selected' : '' ?>>Générales (tous secteurs)</option>
            <?php foreach ($taxonomy['sectors'] ?? [] as $id => $name): ?>
                <option value="<?= (int) $id ?>" <?= (string) ($filters['sector_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= Sanitizer::e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-active">Statut</label>
        <select id="f-active" name="active">
            <option value="">Tous</option>
            <option value="1" <?= ($filters['active'] ?? '') === '1' ? 'selected' : '' ?>>Actives</option>
            <option value="0" <?= ($filters['active'] ?? '') === '0' ? 'selected' : '' ?>>Inactives</option>
        </select>
    </div>
    <div class="field" style="flex:2 1 12rem">
        <label for="f-q">Recherche</label>
        <input type="search" id="f-q" name="q" value="<?= Sanitizer::e($filters['q'] ?? '') ?>" placeholder="Objection ou réponse…">
    </div>
    <div class="filters__actions">
        <button type="submit" class="btn btn--sm"><?= Icon::svg('filter') ?> Filtrer</button>
        <?php if ($queryBase !== []): ?>
            <a class="btn btn--sm btn--ghost" href="<?= Sanitizer::e(View::url('/admin/objections')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($objections === []): ?>
    <div class="card empty">
        <span class="empty__icon"><?= Icon::svg('shield') ?></span>
        <h3>Aucune objection</h3>
        <p>Ajoutez au moins les objections les plus fréquentes : « J'ai déjà un site », « C'est trop cher », « Envoyez-moi un mail ».</p>
        <a class="btn" href="<?= Sanitizer::e(View::url('/admin/objections/nouveau')) ?>"><?= Icon::svg('plus') ?> Nouvelle objection</a>
    </div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($objections as $objection):
            $isActive = (int) $objection['is_active'] === 1;
            ?>
            <article class="card" style="padding:var(--s-4)">
                <div class="row row--between" style="align-items:flex-start;gap:var(--s-3)">
                    <div style="min-width:0;flex:1">
                        <div class="row" style="gap:var(--s-2);align-items:center;margin-bottom:.3rem">
                            <span class="speech-block__icon" style="width:1.9rem;height:1.9rem;flex:none"><?= Icon::svg('message', '', 15) ?></span>
                            <h2 class="mb-0" style="font-size:var(--fs-md)">« <?= Sanitizer::e($objection['question']) ?> »</h2>
                        </div>
                        <div class="row" style="gap:.35rem;margin-bottom:.5rem">
                            <span class="badge badge--<?= $isActive ? 'ok' : 'muted' ?> badge--dot"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                            <?php if (!empty($objection['service_name'])): ?>
                                <span class="badge"><?= Sanitizer::e($objection['service_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($objection['sector_name'])): ?>
                                <span class="badge"><?= Sanitizer::e($objection['sector_name']) ?></span>
                            <?php endif; ?>
                            <?php if (empty($objection['service_name']) && empty($objection['sector_name'])): ?>
                                <span class="badge badge--muted">Tous contextes</span>
                            <?php endif; ?>
                            <span class="text-xs text-muted-2">Position <?= (int) $objection['position'] ?></span>
                        </div>
                        <div class="rich rich--tight" style="color:var(--muted)">
                            <?= SpeechRenderer::render((string) $objection['answer']) ?>
                        </div>
                    </div>

                    <div class="cell-actions" style="flex-wrap:wrap;justify-content:flex-end">
                        <div class="order-btns">
                            <form method="post" action="<?= Sanitizer::e(View::url('/admin/objections/' . (int) $objection['id'] . '/deplacer')) ?>">
                                <?= View::csrf() ?><input type="hidden" name="direction" value="up">
                                <button class="btn btn--ghost btn--sm" aria-label="Monter" title="Monter"><?= Icon::svg('chevron-up', '', 16) ?></button>
                            </form>
                            <form method="post" action="<?= Sanitizer::e(View::url('/admin/objections/' . (int) $objection['id'] . '/deplacer')) ?>">
                                <?= View::csrf() ?><input type="hidden" name="direction" value="down">
                                <button class="btn btn--ghost btn--sm" aria-label="Descendre" title="Descendre"><?= Icon::svg('chevron-down', '', 16) ?></button>
                            </form>
                        </div>
                        <form method="post" action="<?= Sanitizer::e(View::url('/admin/objections/' . (int) $objection['id'] . '/basculer')) ?>">
                            <?= View::csrf() ?>
                            <button class="btn btn--sm <?= $isActive ? 'btn--soft' : 'btn--ghost' ?>" title="<?= $isActive ? 'Désactiver' : 'Activer' ?>">
                                <?= Icon::svg('refresh', '', 15) ?> <?= $isActive ? 'Désactiver' : 'Activer' ?>
                            </button>
                        </form>
                        <a class="btn btn--ghost btn--sm" href="<?= Sanitizer::e(View::url('/admin/objections/' . (int) $objection['id'] . '/modifier')) ?>" title="Modifier">
                            <?= Icon::svg('edit', '', 16) ?><span class="visually-hidden">Modifier</span>
                        </a>
                        <form method="post" action="<?= Sanitizer::e(View::url('/admin/objections/' . (int) $objection['id'] . '/supprimer')) ?>"
                              data-confirm="Supprimer définitivement cette objection ?">
                            <?= View::csrf() ?>
                            <button class="btn btn--ghost btn--sm" style="color:var(--err)" title="Supprimer">
                                <?= Icon::svg('trash', '', 16) ?><span class="visually-hidden">Supprimer</span>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
