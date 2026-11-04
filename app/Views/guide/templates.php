<?php
/**
 * Guide — page courte des modèles de mail (pratique sur mobile, pendant un appel).
 *
 * @var string $subtitle
 * @var array<int, array<string, string>> $mailTemplates
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$mailTemplates = $mailTemplates ?? [];
$subtitle = $subtitle ?? '';
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('mail', '', 14) ?> Guide</span>
        <h1>Modèles de mail</h1>
        <p class="page-head__sub"><?= Sanitizer::e($subtitle) ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/guide')) ?>">
            <?= Icon::svg('arrow-left') ?> Retour au guide
        </a>
    </div>
</div>

<div class="stack stack--lg">
    <?php foreach ($mailTemplates as $index => $template): ?>
        <article class="card">
            <div class="row row--between" style="gap:var(--s-2);align-items:center;flex-wrap:wrap">
                <div>
                    <span class="badge badge--accent"><?= Sanitizer::e($template['when']) ?></span>
                    <h2 style="margin:var(--s-2) 0 0;font-size:var(--fs-md)"><?= Sanitizer::e($template['subject']) ?></h2>
                </div>
                <button type="button" class="btn btn--secondary btn--sm" data-copy-target="#mail-<?= $index ?>">
                    <?= Icon::svg('copy') ?> Copier le mail
                </button>
            </div>
            <pre class="mail-preview" id="mail-<?= $index ?>"><?= Sanitizer::e($template['body']) ?></pre>
        </article>
    <?php endforeach; ?>
</div>
