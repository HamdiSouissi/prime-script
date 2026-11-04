<?php
/**
 * Formulaire de connexion.
 */

use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$old = $old ?? [];
$errors = $errors ?? [];
$locked = $locked ?? false;
$remaining = $remaining ?? 5;
?>
<h1>Connexion</h1>
<p class="auth-card__sub">Accédez à votre script d'appel. Les comptes sont créés par l'administrateur.</p>

<?php if ($locked): ?>
    <div class="alert alert--error mb-4">
        <?= Icon::svg('lock') ?>
        <div class="alert__body">
            <strong>Trop de tentatives.</strong><br>
            Pour votre sécurité, la connexion est bloquée quelques minutes. Contactez votre administrateur si besoin.
        </div>
    </div>
<?php endif; ?>

<?php if (isset($errors['email']) || isset($errors['password'])): ?>
    <div class="alert alert--error mb-4" role="alert">
        <?= Icon::svg('alert') ?>
        <div class="alert__body">
            <?= Sanitizer::e($errors['email'] ?? $errors['password']) ?>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= Sanitizer::e(View::url('/login')) ?>" novalidate>
    <?= View::csrf() ?>

    <div class="field">
        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" value="<?= Sanitizer::e($old['email'] ?? '') ?>"
               autocomplete="username" inputmode="email" required
               aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"
               placeholder="prenom@primevisionsolutions.fr" autocapitalize="off" spellcheck="false">
    </div>

    <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required
               aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>"
               placeholder="••••••••">
    </div>

    <button type="submit" class="btn btn--lg btn--block mt-2">
        <?= Icon::svg('log-out') ?> Se connecter
    </button>

    <?php if ($remaining <= 3 && $remaining > 0 && !$locked): ?>
        <p class="text-xs text-muted-2 text-center mt-3" style="margin-bottom:0">
            Il reste <?= (int) $remaining ?> tentative<?= (int) $remaining > 1 ? 's' : '' ?> avant un blocage temporaire.
        </p>
    <?php endif; ?>
</form>

<p class="auth-card__foot">
    Mot de passe oublié ? Contactez votre administrateur : il peut le réinitialiser en un clic.<br>
    <span style="opacity:.8">Prime Vision Solutions · Prime Script v1.0</span>
</p>
