<?php
/** Page 403 (accès réservé aux administrateurs). */

use App\Core\Icon;
use App\Core\View;
?>
<div class="card empty" style="max-width:560px;margin:10vh auto">
    <span class="empty__icon" style="background:var(--err-soft);color:var(--err)"><?= Icon::svg('lock') ?></span>
    <h1 style="font-size:var(--fs-xl)">Accès réservé</h1>
    <p>Cette page est réservée aux administrateurs. Votre compte commercial ne peut pas y accéder.</p>
    <div class="btn-row" style="justify-content:center">
        <a class="btn" href="<?= View::url('/') ?>"><?= Icon::svg('phone') ?> Continuer mes appels</a>
        <a class="btn btn--secondary" href="<?= View::url('/historique') ?>"><?= Icon::svg('clock') ?> Mes appels</a>
    </div>
</div>
