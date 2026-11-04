<?php
/**
 * Administration — création / modification d'un compte + réinitialisation du mot de passe.
 */

use App\Core\Auth;
use App\Core\Config;
use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$user = $user ?? [];
$errors = $errors ?? [];
$isNew = $isNew ?? true;
$isSelf = (int) ($user['id'] ?? 0) === (int) (Auth::user()['id'] ?? 0);
$minLength = (int) Config::get('security.password_min_length', 10);
$action = $isNew ? View::url('/admin/utilisateurs') : View::url('/admin/utilisateurs/' . (int) $user['id'] . '/modifier');
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('user', '', 14) ?> <?= $isNew ? 'Création' : 'Modification' ?></span>
        <h1><?= $isNew ? 'Nouveau compte' : Sanitizer::e($user['name']) ?></h1>
        <p class="page-head__sub"><?= $isNew ? 'Le collaborateur pourra se connecter dès la création du compte.' : 'Rôle, coordonnées et mot de passe.' ?></p>
    </div>
    <div class="page-head__actions">
        <a class="btn btn--ghost" href="<?= Sanitizer::e(View::url('/admin/utilisateurs')) ?>">
            <?= Icon::svg('arrow-left') ?> Retour à l'équipe
        </a>
    </div>
</div>

<div class="admin-grid admin-grid--2" style="align-items:start;max-width:1100px">
    <form method="post" action="<?= Sanitizer::e($action) ?>" novalidate>
        <?= View::csrf() ?>
        <div class="card">
            <h2 class="card__title mb-3" style="font-size:var(--fs-md)">Informations du compte</h2>

            <div class="field">
                <label for="name">Nom complet <span aria-hidden="true">*</span></label>
                <input type="text" id="name" name="name" value="<?= Sanitizer::e($user['name'] ?? '') ?>" maxlength="120" required
                       autocomplete="off" placeholder="Prénom Nom">
                <?php if (isset($errors['name'])): ?><span class="field__error"><?= Sanitizer::e($errors['name']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="email">Adresse e-mail <span aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email" value="<?= Sanitizer::e($user['email'] ?? '') ?>" required
                       autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="prenom@primevisionsolutions.fr">
                <span class="field__hint">Sert d'identifiant de connexion.</span>
                <?php if (isset($errors['email'])): ?><span class="field__error"><?= Sanitizer::e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="role">Rôle <span aria-hidden="true">*</span></label>
                <select id="role" name="role" required>
                    <option value="commercial" <?= ($user['role'] ?? '') === 'commercial' ? 'selected' : '' ?>>Commercial — parcours d'appel uniquement</option>
                    <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur — accès complet</option>
                </select>
                <?php if (isset($errors['role'])): ?><span class="field__error"><?= Sanitizer::e($errors['role']) ?></span><?php endif; ?>
            </div>

            <?php if ($isNew): ?>
                <div class="form-grid mt-4">
                    <div class="field">
                        <label for="password">Mot de passe provisoire <span aria-hidden="true">*</span></label>
                        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="<?= $minLength ?>">
                    </div>
                    <div class="field">
                        <label for="password_confirm">Confirmation <span aria-hidden="true">*</span></label>
                        <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                    </div>
                </div>
                <p class="field__hint"><?= $minLength ?> caractères minimum, avec au moins une lettre et un chiffre.</p>
                <?php if (isset($errors['password'])): ?><span class="field__error"><?= Sanitizer::e($errors['password']) ?></span><?php endif; ?>
            <?php endif; ?>

            <div class="form-actions">
                <span class="form-actions__spacer"></span>
                <button type="submit" class="btn btn--lg" data-loading-text="Enregistrement…">
                    <?= Icon::svg('save') ?> <?= $isNew ? 'Créer le compte' : 'Enregistrer' ?>
                </button>
            </div>
        </div>
    </form>

    <div class="stack">
        <?php if (!$isNew): ?>
            <div class="card">
                <h2 class="card__title mb-3" style="font-size:var(--fs-md)"><?= Icon::svg('key', '', 18) ?> Réinitialiser le mot de passe</h2>
                <p class="text-sm text-muted mb-3" style="margin-top:0">
                    Utile si le collaborateur a oublié son mot de passe. Communiquez le nouveau mot de passe de vive voix.
                </p>
                <form method="post" action="<?= Sanitizer::e(View::url('/admin/utilisateurs/' . (int) $user['id'] . '/mot-de-passe')) ?>"
                      data-confirm="Réinitialiser le mot de passe de <?= Sanitizer::e($user['name']) ?> ? L'ancien ne fonctionnera plus.">
                    <?= View::csrf() ?>
                    <div class="field">
                        <label for="new-password">Nouveau mot de passe</label>
                        <input type="password" id="new-password" name="password" required autocomplete="new-password" minlength="<?= $minLength ?>">
                    </div>
                    <div class="field">
                        <label for="new-password-confirm">Confirmation</label>
                        <input type="password" id="new-password-confirm" name="password_confirm" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn--secondary btn--block"><?= Icon::svg('key') ?> Réinitialiser</button>
                </form>
            </div>

            <div class="card" style="background:var(--surface-2)">
                <h2 class="card__title mb-2" style="font-size:var(--fs-md)"><?= Icon::svg('info', '', 18) ?> Statut du compte</h2>
                <p class="text-sm text-muted" style="margin-bottom:var(--s-3)">
                    <?php if ((int) $user['is_active'] === 1): ?>
                        Compte <strong class="text-ok">actif</strong>. Dernière connexion :
                        <?= $user['last_login_at'] ? Sanitizer::e(View::dateFr((string) $user['last_login_at'], true)) : 'jamais' ?>.
                    <?php else: ?>
                        Compte <strong class="text-err">désactivé</strong> : la connexion est refusée.
                    <?php endif; ?>
                </p>
                <?php if (!$isSelf): ?>
                    <form method="post" action="<?= Sanitizer::e(View::url('/admin/utilisateurs/' . (int) $user['id'] . '/basculer')) ?>"
                          data-confirm="<?= (int) $user['is_active'] === 1 ? 'Désactiver ce compte ?' : 'Réactiver ce compte ?' ?>">
                        <?= View::csrf() ?>
                        <button type="submit" class="btn btn--sm <?= (int) $user['is_active'] === 1 ? 'btn--secondary' : 'btn--success' ?> btn--block">
                            <?= Icon::svg((int) $user['is_active'] === 1 ? 'x-circle' : 'check-circle') ?>
                            <?= (int) $user['is_active'] === 1 ? 'Désactiver ce compte' : 'Réactiver ce compte' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-xs text-muted-2" style="margin:0">Vous ne pouvez pas désactiver votre propre compte.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card" style="background:var(--surface-2)">
                <h2 class="card__title mb-2" style="font-size:var(--fs-md)"><?= Icon::svg('info', '', 18) ?> Droits des rôles</h2>
                <dl class="help-list">
                    <div>
                        <dt>Commercial</dt>
                        <dd>Parcours d'appel, objections et son propre historique. Aucun accès à l'administration.</dd>
                    </div>
                    <div>
                        <dt>Admin</dt>
                        <dd>Tout, plus la gestion des contenus, de l'équipe et du journal d'appels.</dd>
                    </div>
                </dl>
            </div>
        <?php endif; ?>
    </div>
</div>
