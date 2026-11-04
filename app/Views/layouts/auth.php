<?php
/**
 * Mise en page dédiée à la connexion (plein écran, sans navigation).
 *
 * Variables : $content, $title.
 */

use App\Core\Http;
use App\Core\Session;
use App\Core\Icon;
use App\Core\Sanitizer;
use App\Core\View;

$flashes = Session::takeFlashes();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= Sanitizer::e(isset($title) ? $title . ' · Prime Script' : 'Connexion · Prime Script') ?></title>
    <meta name="theme-color" content="#061230">
    <meta name="csrf-token" content="<?= Sanitizer::e(Session::csrfToken()) ?>">
    <meta name="base-url" content="<?= Sanitizer::e(Http::baseUrl()) ?>">
    <link rel="icon" type="image/png" href="<?= Sanitizer::e(View::url('assets/img/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= Sanitizer::e(View::url('assets/css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= Sanitizer::e(View::url('assets/css/app.css')) ?>">
    <script>
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
<body class="auth-body">
<a class="skip-link" href="#contenu">Aller au formulaire</a>

<div class="auth-layout">
    <aside class="auth-aside" aria-hidden="true">
        <div>
            <img class="auth-aside__logo" src="<?= Sanitizer::e(View::url('assets/img/logo-full-dark.png')) ?>" alt="Prime Vision Solutions" width="220" height="69">
        </div>
        <div>
            <h2>Le bon discours, au bon moment.</h2>
            <p>Prime Script accompagne vos commerciaux pendant l'appel : présentation, accroches, argumentaires et réponses aux objections, adaptés au secteur et à l'étape du prospect.</p>
            <ul class="auth-aside__list">
                <li><?= Icon::svg('check') ?><span>Un parcours en 3 clics, lisible d'un coup d'œil</span></li>
                <li><?= Icon::svg('check') ?><span>Les objections et leurs réponses, toujours à portée</span></li>
                <li><?= Icon::svg('check') ?><span>Un journal d'appels partagé avec l'équipe</span></li>
            </ul>
        </div>
        <p class="auth-aside__foot">Prime Vision Solutions · Agence digitale pour TPE &amp; PME · Paris</p>
    </aside>

    <main id="contenu" class="auth-main">
        <div class="auth-card">
            <div class="auth-card__brand">
                <img src="<?= Sanitizer::e(View::url('assets/img/logo-full.png')) ?>" alt="Prime Vision Solutions" width="190" height="60">
            </div>

            <?php if ($flashes !== []): ?>
                <div class="flash-stack" role="status" aria-live="polite">
                    <?php foreach ($flashes as $flash):
                        $type = in_array($flash['type'], ['success', 'error', 'warn', 'info'], true) ? $flash['type'] : 'info';
                        $icon = ['success' => 'check-circle', 'error' => 'alert', 'warn' => 'alert', 'info' => 'info'][$type];
                        ?>
                        <div class="alert alert--<?= Sanitizer::e($type) ?>">
                            <?= App\Core\Icon::svg($icon) ?>
                            <div class="alert__body"><?= Sanitizer::e($flash['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>
</div>

<script src="<?= Sanitizer::e(View::url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
