<?php
/** Page 500 — message générique, aucun détail technique. */

use App\Core\Icon;
use App\Core\View;
?>
<div class="card empty" style="max-width:560px;margin:10vh auto">
    <span class="empty__icon" style="background:var(--err-soft);color:var(--err)"><?= Icon::svg('alert') ?></span>
    <h1 style="font-size:var(--fs-xl)">Une erreur est survenue</h1>
    <p>Merci de réessayer dans quelques instants. Si le problème persiste, contactez votre administrateur.</p>
    <div class="btn-row" style="justify-content:center">
        <a class="btn" href="<?= View::url('/') ?>"><?= Icon::svg('refresh') ?> Réessayer</a>
    </div>
</div>
