<?php
/** Page 404 soignée. */

use App\Core\Icon;
use App\Core\View;
?>
<div class="card empty" style="max-width:560px;margin:10vh auto">
    <span class="empty__icon"><?= Icon::svg('search') ?></span>
    <h1 style="font-size:var(--fs-xl)">Page introuvable</h1>
    <p>Le lien est peut-être obsolète, ou la page a été déplacée.</p>
    <div class="btn-row" style="justify-content:center">
        <a class="btn" href="<?= View::url('/') ?>"><?= Icon::svg('home') ?> Retour à l'accueil</a>
        <a class="btn btn--secondary" href="<?= View::url('/historique') ?>"><?= Icon::svg('clock') ?> Mes appels</a>
    </div>
</div>
