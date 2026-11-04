<?php
/**
 * Mise en page principale (commercial + admin).
 *
 * Variables attendues : $content (obligatoire), $title, $bodyClass, $currentUser,
 * $breadcrumb (array de ['label' => ..., 'url' => ...]).
 */

use App\Core\Auth;
use App\Core\Http;
use App\Core\Icon;
use App\Core\Session;
use App\Core\Sanitizer;
use App\Core\View;

$user = $currentUser ?? Auth::user();
$pageTitle = isset($title) ? $title . ' · Prime Script' : 'Prime Script';
$isAdminArea = str_starts_with(Http::path(), '/admin');
$flashes = Session::takeFlashes();
$themeLabel = 'Mode sombre';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= Sanitizer::e($pageTitle) ?></title>
    <meta name="description" content="Prime Script — le script d'appel de Prime Vision Solutions : présentation, accroches, argumentaires et réponses aux objections, au bon moment.">
    <meta name="theme-color" content="#061230">
    <meta name="csrf-token" content="<?= Sanitizer::e(Session::csrfToken()) ?>">
    <meta name="base-url" content="<?= Sanitizer::e(Http::baseUrl()) ?>">
    <link rel="icon" type="image/png" href="<?= Sanitizer::e(View::url('assets/img/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= Sanitizer::e(View::url('assets/css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= Sanitizer::e(View::url('assets/css/app.css')) ?>">
    <script>
        /* Applique le thème avant le premier rendu (évite le clignotement). */
        (function () {
            try {
                var stored = localStorage.getItem('prime-script-theme');
                if (stored === 'light' || stored === 'dark') {
                    document.documentElement.setAttribute('data-theme', stored);
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="<?= Sanitizer::e($bodyClass ?? '') ?>">
<a class="skip-link" href="#contenu">Aller au contenu principal</a>

<header class="app-header">
    <div class="app-header__bar">
        <a class="brand" href="<?= Sanitizer::e(View::url(Auth::isAdmin() && $isAdminArea ? '/admin' : '/')) ?>" aria-label="Prime Script — accueil">
            <img class="brand__logo" src="<?= Sanitizer::e(View::url('assets/img/logo-full.png')) ?>" alt="Prime Vision Solutions" width="166" height="52">
            <span class="brand__tag">Script</span>
        </a>

        <nav class="app-nav app-nav--desktop" aria-label="Navigation principale">
            <?php if ($user && $user['role'] === 'admin'): ?>
                <a class="app-nav__link<?= $isAdminArea && !str_starts_with(Http::path(), '/admin/appels') && !str_starts_with(Http::path(), '/admin/utilisateurs') ? ' is-active' : '' ?>" href="<?= Sanitizer::e(View::url('/admin')) ?>">
                    <?= Icon::svg('grid') ?><span>Tableau de bord</span>
                </a>
                <a class="app-nav__link<?= str_starts_with(Http::path(), '/admin/blocs') || str_starts_with(Http::path(), '/admin/objections') || str_starts_with(Http::path(), '/admin/secteurs') || str_starts_with(Http::path(), '/admin/etapes') || str_starts_with(Http::path(), '/admin/services') ? ' is-active' : '' ?>" href="<?= Sanitizer::e(View::url('/admin/blocs')) ?>">
                    <?= Icon::svg('layers') ?><span>Contenus</span>
                </a>
                <a class="app-nav__link<?= str_starts_with(Http::path(), '/admin/appels') ? ' is-active' : '' ?>" href="<?= Sanitizer::e(View::url('/admin/appels')) ?>">
                    <?= Icon::svg('history') ?><span>Journal</span>
                </a>
                <a class="app-nav__link<?= str_starts_with(Http::path(), '/admin/utilisateurs') ? ' is-active' : '' ?>" href="<?= Sanitizer::e(View::url('/admin/utilisateurs')) ?>">
                    <?= Icon::svg('users') ?><span>Équipe</span>
                </a>
            <?php endif; ?>
            <?php if ($user): ?>
                <a class="app-nav__link<?= View::active('/', true) ?>" href="<?= Sanitizer::e(View::url('/')) ?>">
                    <?= Icon::svg('phone') ?><span>Appel</span>
                </a>
                <a class="app-nav__link<?= View::active('/historique') ?>" href="<?= Sanitizer::e(View::url('/historique')) ?>">
                    <?= Icon::svg('clock') ?><span>Mes appels</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <button type="button" class="btn btn--ghost btn--icon" data-theme-toggle aria-pressed="false" title="Changer de thème" aria-label="Changer de thème (clair ou sombre)">
                <?= Icon::svg('moon', 'theme-toggle__icon') ?>
                <span class="visually-hidden"><?= Sanitizer::e($themeLabel) ?></span>
            </button>

            <?php if ($user): ?>
                <div class="user-menu" data-user-menu>
                    <button type="button" class="user-menu__button" data-user-menu-button aria-expanded="false" aria-haspopup="true">
                        <span class="avatar" aria-hidden="true"><?= Sanitizer::e(View::initials((string) $user['name'])) ?></span>
                        <span class="nowrap"><?= Sanitizer::e(explode(' ', (string) $user['name'])[0]) ?></span>
                        <?= Icon::svg('chevron-down', 'ico--xs') ?>
                    </button>
                    <div class="user-menu__dropdown" data-user-menu-dropdown role="menu">
                        <div class="user-menu__meta">
                            <strong><?= Sanitizer::e($user['name']) ?></strong>
                            <span><?= Sanitizer::e($user['email']) ?> · <?= $user['role'] === 'admin' ? 'Administrateur' : 'Commercial' ?></span>
                        </div>
                        <a class="user-menu__item" href="<?= Sanitizer::e(View::url('/profil')) ?>" role="menuitem">
                            <?= Icon::svg('user') ?> Mon compte
                        </a>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a class="user-menu__item" href="<?= Sanitizer::e(View::url('/admin')) ?>" role="menuitem">
                                <?= Icon::svg('settings') ?> Administration
                            </a>
                        <?php endif; ?>
                        <form method="post" action="<?= Sanitizer::e(View::url('/deconnexion')) ?>">
                            <?= View::csrf() ?>
                            <button type="submit" class="user-menu__item user-menu__item--danger" role="menuitem">
                                <?= Icon::svg('log-out') ?> Se déconnecter
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn btn--sm" href="<?= Sanitizer::e(View::url('/login')) ?>">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($subnav) && $subnav !== ''): ?>
    <?= App\Core\App::render('partials/admin-subnav', ['activeNav' => $subnav]) ?>
<?php endif; ?>

<main id="contenu" class="app-main<?= isset($mainClass) ? ' ' . Sanitizer::e($mainClass) : '' ?>">
    <div class="<?= isset($containerClass) ? Sanitizer::e($containerClass) : 'shell' ?>">
        <?php if ($flashes !== []): ?>
            <div class="flash-stack" role="status" aria-live="polite">
                <?php foreach ($flashes as $flash):
                    $type = in_array($flash['type'], ['success', 'error', 'warn', 'info'], true) ? $flash['type'] : 'info';
                    $icon = ['success' => 'check-circle', 'error' => 'alert', 'warn' => 'alert', 'info' => 'info'][$type];
                    ?>
                    <div class="alert alert--<?= Sanitizer::e($type) ?>">
                        <?= Icon::svg($icon) ?>
                        <div class="alert__body"><?= Sanitizer::e($flash['message']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($breadcrumb) && is_array($breadcrumb) && $breadcrumb !== []): ?>
            <nav class="crumbs" aria-label="Fil d'Ariane">
                <?php foreach ($breadcrumb as $i => $crumb): ?>
                    <?php if ($i > 0): ?><span class="crumbs__sep" aria-hidden="true"> / </span><?php endif; ?>
                    <?php if (!empty($crumb['url'])): ?>
                        <a class="crumbs__link" href="<?= Sanitizer::e(View::url($crumb['url'])) ?>"><?= Sanitizer::e($crumb['label']) ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= Sanitizer::e($crumb['label']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

<footer class="app-footer no-print">
    <div class="app-footer__inner">
        <span>© <?= date('Y') ?> Prime Vision Solutions — <strong>Prime Script</strong></span>
        <span class="row" style="gap:var(--s-4)">
            <span><?= Sanitizer::e($user ? 'Connecté : ' . $user['name'] : '') ?></span>
            <span>primevisionsolutions.fr</span>
        </span>
    </div>
</footer>

<script src="<?= Sanitizer::e(View::url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
