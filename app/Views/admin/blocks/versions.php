<?php
/**
 * Administration — historique des versions d'un bloc, avec restauration.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\SpeechBlock;

$block = $block ?? [];
$versions = $versions ?? [];
$latest = $versions[0] ?? null;
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('history', '', 14) ?> Historique</span>
        <h1><?= Sanitizer::e($block['title'] !== '' ? $block['title'] : 'Bloc sans titre') ?></h1>
        <p class="page-head__sub">
            <?= count($versions) ?> version<?= count($versions) > 1 ? 's' : '' ?> archivée<?= count($versions) > 1 ? 's' : '' ?>.
            Restaurer un version rend son contenu immédiatement visible côté commercial.
        </p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">
            <?= Icon::svg('arrow-left') ?> Liste des blocs
        </a>
        <a class="btn" href="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/modifier')) ?>">
            <?= Icon::svg('edit') ?> Modifier le bloc
        </a>
    </div>
</div>

<div class="admin-grid admin-grid--2" style="align-items:start">
    <section class="card" aria-labelledby="current-title">
        <div class="card__head">
            <div>
                <h2 class="card__title" id="current-title">Version en ligne</h2>
                <p class="card__sub">Publiée le <?= Sanitizer::e(View::dateFr((string) $block['updated_at'], true)) ?></p>
            </div>
            <span class="badge badge--<?= (int) $block['is_active'] === 1 ? 'ok' : 'muted' ?> badge--dot">
                <?= (int) $block['is_active'] === 1 ? 'Active' : 'Inactive' ?>
            </span>
        </div>
        <div class="rich rich--tight"><?= App\Services\SpeechRenderer::render((string) $block['content']) ?></div>
    </section>

    <section aria-labelledby="history-title">
        <div class="section-label" style="margin-top:0">
            <h2 id="history-title">Versions précédentes</h2>
        </div>

        <?php if ($versions === []): ?>
            <div class="card empty">
                <span class="empty__icon"><?= Icon::svg('history') ?></span>
                <h3>Aucune version archivée</h3>
                <p>Une version est créée à chaque enregistrement du bloc.</p>
            </div>
        <?php else: ?>
            <ol class="timeline">
                <?php foreach ($versions as $index => $version): ?>
                    <li class="timeline__item<?= $index === 0 ? ' is-current' : '' ?>">
                        <span class="timeline__dot" aria-hidden="true"></span>
                        <div class="version<?= $index === 0 ? ' is-current' : '' ?>">
                            <div class="version__head">
                                <span class="badge badge--<?= $index === 0 ? 'accent' : 'muted' ?>">Version <?= (int) $version['version_no'] ?></span>
                                <span class="version__meta"><?= Sanitizer::e(View::dateFr((string) $version['created_at'], true)) ?></span>
                                <span class="version__meta">· <?= Sanitizer::e((string) ($version['author_name'] ?? 'Administrateur')) ?></span>
                                <?php if ($index === 0): ?>
                                    <span class="badge badge--info" style="margin-left:auto">La plus récente</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($version['change_note'])): ?>
                                <p class="text-xs text-muted mt-2" style="margin-bottom:0;margin-top:.4rem">
                                    <?= Icon::svg('edit', '', 12) ?> <?= Sanitizer::e($version['change_note']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($version['title'])): ?>
                                <p class="text-sm fw-600 mt-2" style="margin:.4rem 0 .2rem"><?= Sanitizer::e($version['title']) ?></p>
                            <?php endif; ?>
                            <div class="version__preview rich rich--tight">
                                <?= App\Services\SpeechRenderer::render((string) $version['content']) ?>
                            </div>
                            <form method="post" action="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/restaurer')) ?>"
                                  class="mt-3" data-confirm="Restaurer la version <?= (int) $version['version_no'] ?> ? Le contenu actuel sera archivé avant remplacement.">
                                <?= View::csrf() ?>
                                <input type="hidden" name="version_id" value="<?= (int) $version['id'] ?>">
                                <button type="submit" class="btn btn--sm btn--secondary">
                                    <?= Icon::svg('restore') ?> Restaurer cette version
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>
