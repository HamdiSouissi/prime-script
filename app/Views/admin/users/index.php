<?php
/**
 * Administration — équipe et comptes.
 */

use App\Core\Auth;
use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$users = $users ?? [];
$currentId = (int) (Auth::user()['id'] ?? 0);
?>
<div class="page-head">
    <div class="page-head__text">
        <span class="eyebrow"><?= Icon::svg('users', '', 14) ?> Administration</span>
        <h1>Équipe</h1>
        <p class="page-head__sub"><?= count($users) ?> compte<?= count($users) > 1 ? 's' : '' ?> · les comptes sont créés ici, il n'y a pas d'inscription publique.</p>
    </div>
    <div class="page-head__actions">
        <a class="btn" href="<?= Sanitizer::e(View::url('/admin/utilisateurs/nouveau')) ?>">
            <?= Icon::svg('plus') ?> Nouveau compte
        </a>
    </div>
</div>

<div class="alert alert--info mb-4">
    <?= Icon::svg('lock') ?>
    <div class="alert__body">
        <strong>Bonnes pratiques :</strong> un compte par personne, mot de passe provisoire communiqué de vive voix,
        compte désactivé dès le départ d'un collaborateur. Les mots de passe ne sont jamais affichés en clair.
    </div>
</div>

<div class="table-wrap">
    <div class="table-scroll">
        <table class="table">
            <caption class="visually-hidden">Comptes de l'équipe</caption>
            <thead>
                <tr>
                    <th>Membre</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Dernière connexion</th>
                    <th>Appels</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    $isActive = (int) $user['is_active'] === 1;
                    $isSelf = (int) $user['id'] === $currentId;
                    ?>
                    <tr>
                        <td>
                            <div class="row" style="gap:var(--s-2);flex-wrap:nowrap">
                                <span class="avatar" aria-hidden="true"><?= Sanitizer::e(View::initials((string) $user['name'])) ?></span>
                                <div style="min-width:0">
                                    <strong style="display:block"><?= Sanitizer::e($user['name']) ?><?= $isSelf ? ' <span class="badge badge--muted">vous</span>' : '' ?></strong>
                                    <span class="text-xs text-muted"><?= Sanitizer::e($user['email']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge--<?= $user['role'] === 'admin' ? 'accent' : 'muted' ?>">
                                <?= $user['role'] === 'admin' ? 'Administrateur' : 'Commercial' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge--<?= $isActive ? 'ok' : 'err' ?> badge--dot"><?= $isActive ? 'Actif' : 'Désactivé' ?></span>
                        </td>
                        <td class="text-sm">
                            <?= $user['last_login_at'] ? Sanitizer::e(View::ago((string) $user['last_login_at'])) : '<span class="text-muted-2">Jamais</span>' ?>
                        </td>
                        <td class="text-sm">
                            <strong><?= (int) $user['calls_total'] ?></strong>
                            <span class="text-xs text-muted-2">dont <?= (int) $user['calls_positive'] ?> positifs</span>
                        </td>
                        <td>
                            <div class="cell-actions">
                                <a class="btn btn--ghost btn--sm" href="<?= Sanitizer::e(View::url('/admin/utilisateurs/' . (int) $user['id'] . '/modifier')) ?>">
                                    <?= Icon::svg('edit', '', 16) ?> <span class="visually-hidden">Modifier</span>
                                </a>
                                <?php if (!$isSelf): ?>
                                    <form method="post" action="<?= Sanitizer::e(View::url('/admin/utilisateurs/' . (int) $user['id'] . '/basculer')) ?>"
                                          data-confirm="<?= $isActive ? 'Désactiver ce compte ? La connexion sera refusée.' : 'Réactiver ce compte ?' ?>">
                                        <?= View::csrf() ?>
                                        <button class="btn btn--sm <?= $isActive ? 'btn--ghost' : 'btn--soft' ?>" style="<?= $isActive ? 'color:var(--err)' : '' ?>">
                                            <?= $isActive ? 'Désactiver' : 'Réactiver' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
