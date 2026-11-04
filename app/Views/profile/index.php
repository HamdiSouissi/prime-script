<?php
/**
 * Mon compte — informations et changement de mot de passe.
 */

use App\Core\Config;
use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$user = $user ?? [];
$stats = $stats ?? [];
$errors = $errors ?? [];
$minLength = (int) Config::get('security.password_min_length', 10);
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('user', '', 14) ?> Mon compte</span>
        <h1><?= Sanitizer::e($user['name']) ?></h1>
        <p class="page-head__sub"><?= Sanitizer::e($user['email']) ?> · <?= $user['role'] === 'admin' ? 'Administrateur' : 'Commercial' ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--secondary" href="<?= Sanitizer::e(View::url('/historique')) ?>">
            <?= Icon::svg('clock') ?> Mes appels
        </a>
    </div>
</div>

<div class="admin-grid admin-grid--2" style="align-items:start;max-width:960px">
    <div class="stack">
        <div class="card">
            <h2 class="card__title mb-3" style="font-size:var(--fs-md)">Activité</h2>
            <div class="stats">
                <div class="stat"><p class="stat__label">Aujourd'hui</p><p class="stat__value"><?= (int) ($stats['today'] ?? 0) ?></p></div>
                <div class="stat"><p class="stat__label">30 jours</p><p class="stat__value"><?= (int) ($stats['month'] ?? 0) ?></p></div>
                <div class="stat"><p class="stat__label">RDV</p><p class="stat__value text-ok"><?= (int) ($stats['rdv_month'] ?? 0) ?></p></div>
                <div class="stat"><p class="stat__label">Devis</p><p class="stat__value"><?= (int) ($stats['devis_month'] ?? 0) ?></p></div>
            </div>
            <dl class="summary mt-4">
                <div><dt>Dernière connexion</dt><dd><?= $user['last_login_at'] ? Sanitizer::e(View::dateFr((string) $user['last_login_at'], true)) : '—' ?></dd></div>
                <div><dt>Compte créé le</dt><dd><?= Sanitizer::e(View::dateFr((string) $user['created_at'])) ?></dd></div>
            </dl>
        </div>

        <div class="card" style="background:var(--surface-2)">
            <h2 class="card__title mb-2" style="font-size:var(--fs-md)"><?= Icon::svg('info', '', 18) ?> Besoin d'aide ?</h2>
            <p class="text-sm text-muted" style="margin:0">
                Pour toute question sur les contenus ou votre compte, contactez l'administrateur :
                <strong><?= Sanitizer::e(App\Models\User::firstAdminEmail() ?? 'votre responsable') ?></strong>.
            </p>
        </div>
    </div>

    <form method="post" action="<?= Sanitizer::e(View::url('/profil/mot-de-passe')) ?>" novalidate class="card">
        <?= View::csrf() ?>
        <h2 class="card__title mb-3" style="font-size:var(--fs-md)"><?= Icon::svg('lock', '', 18) ?> Changer mon mot de passe</h2>

        <div class="field">
            <label for="current_password">Mot de passe actuel <span aria-hidden="true">*</span></label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            <?php if (isset($errors['current_password'])): ?><span class="field__error"><?= Sanitizer::e($errors['current_password']) ?></span><?php endif; ?>
        </div>

        <div class="field">
            <label for="password">Nouveau mot de passe <span aria-hidden="true">*</span></label>
            <input type="password" id="password" name="password" required autocomplete="new-password" minlength="<?= $minLength ?>">
            <span class="field__hint"><?= $minLength ?> caractères minimum, au moins une lettre et un chiffre.</span>
            <?php if (isset($errors['password'])): ?><span class="field__error"><?= Sanitizer::e($errors['password']) ?></span><?php endif; ?>
        </div>

        <div class="field">
            <label for="password_confirm">Confirmation <span aria-hidden="true">*</span></label>
            <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn--block"><?= Icon::svg('save') ?> Enregistrer le mot de passe</button>
    </form>
</div>
