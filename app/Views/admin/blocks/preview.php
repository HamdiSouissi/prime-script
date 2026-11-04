<?php
/**
 * Administration — aperçu plein écran d'un bloc (sans JavaScript).
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;
use App\Models\SpeechBlock;

$block = $block ?? [];
$html = $html ?? '';
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('eye', '', 14) ?> Aperçu</span>
        <h1><?= Sanitizer::e($block['title'] !== '' ? $block['title'] : 'Bloc sans titre') ?></h1>
        <p class="page-head__sub"><?= Sanitizer::e(SpeechBlock::typeLabel((string) $block['type'])) ?> · rendu tel que le verra le commercial.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>"><?= Icon::svg('arrow-left') ?> Liste</a>
        <a class="btn" href="<?= Sanitizer::e(View::url('/admin/blocs/' . (int) $block['id'] . '/modifier')) ?>"><?= Icon::svg('edit') ?> Modifier</a>
    </div>
</div>

<div class="card card--pad-lg" style="max-width:820px">
    <span class="preview-note"><?= Icon::svg('info', '', 14) ?> Aperçu avec des valeurs d'exemple</span>
    <div class="rich rich--lg"><?= $html ?></div>
</div>
