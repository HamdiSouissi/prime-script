<?php
declare(strict_types=1);

/**
 * Vide la table des tentatives de connexion échouées.
 *
 * Utile quand plusieurs tests de sécurité (ou une série de mots de passe erronés)
 * ont déclenché le blocage temporaire avant de lancer le smoke test :
 *   php tools/clear-login-attempts.php
 *
 * En production, aucune maintenue n'est nécessaire : les tentatives expirées
 * sont ignorées automatiquement au bout de `security.login_lock_minutes`.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Db;

Db::run('DELETE FROM login_attempts');
echo 'Tentatives de connexion réinitialisées.', PHP_EOL;
