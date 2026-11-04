<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Db;

/**
 * Comptes utilisateurs (commerciaux et administrateurs).
 * Les comptes sont créés par l'administration : aucune inscription publique.
 */
final class User extends Model
{
    protected static string $table = 'users';

    /** Retrouve un compte par e-mail (insensible à la casse). */
    public static function findByEmail(string $email): ?array
    {
        return Db::one('SELECT * FROM users WHERE email = ? LIMIT 1', [strtolower(trim($email))]);
    }

    /**
     * Liste des comptes avec statistiques d'appels.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function withStats(): array
    {
        return Db::all(
            'SELECT u.*,
                    (SELECT COUNT(*) FROM calls c WHERE c.user_id = u.id) AS calls_total,
                    (SELECT COUNT(*) FROM calls c WHERE c.user_id = u.id AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) AS calls_today,
                    (SELECT COUNT(*) FROM calls c WHERE c.user_id = u.id
                        AND c.result IN (\'rdv\', \'devis\')) AS calls_positive
             FROM users u
             ORDER BY u.role ASC, u.name ASC'
        );
    }

    /** Nombre total de comptes actifs par rôle. */
    public static function countActive(string $role): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM users WHERE role = ? AND is_active = 1', [$role], 0);
    }

    /** E-mail du premier administrateur actif (affiché comme contact d'aide). */
    public static function firstAdminEmail(): ?string
    {
        $email = Db::value(
            "SELECT email FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id ASC LIMIT 1",
            [],
            null
        );
        return $email !== null ? (string) $email : null;
    }

    /** Crée un compte avec mot de passe haché. */
    public static function createWithPassword(string $name, string $email, string $password, string $role): int
    {
        return self::create([
            'name'          => $name,
            'email'         => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $role,
            'is_active'     => 1,
        ]);
    }

    /** Met à jour le mot de passe. */
    public static function updatePassword(int $id, string $password): void
    {
        Db::exec('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [
            password_hash($password, PASSWORD_DEFAULT),
            $id,
        ]);
    }

    /** Active / désactive un compte. */
    public static function setActive(int $id, bool $active): void
    {
        Db::exec('UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?', [(int) $active, $id]);
    }

    /** Met à jour la date de dernière connexion. */
    public static function touchLogin(int $id): void
    {
        Db::exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    /** Valide un mot de passe (longueur minimale + complexité minimale). */
    public static function passwordErrors(string $password, ?string $confirmation = null): ?string
    {
        $min = (int) Config::get('security.password_min_length', 10);
        if (mb_strlen($password, 'UTF-8') < $min) {
            return sprintf('Le mot de passe doit contenir au moins %d caractères.', $min);
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            return 'Le mot de passe doit contenir au moins une lettre et un chiffre.';
        }
        if ($confirmation !== null && $password !== $confirmation) {
            return 'Les deux mots de passe ne correspondent pas.';
        }
        return null;
    }

    // ------------------------------------------------- Tentatives de connexion

    /** Nombre d'échecs récents (fenêtre de blocage configurable). */
    public static function countRecentFailures(string $throttleKey): int
    {
        $minutes = (int) Config::get('security.login_lock_minutes', 15);
        return (int) Db::value(
            'SELECT COUNT(*) FROM login_attempts
             WHERE throttle_key = ? AND successful = 0
               AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $minutes . ' MINUTE)',
            [$throttleKey],
            0
        );
    }

    /** Enregistre une tentative échouée. */
    public static function recordFailure(string $throttleKey, string $ip): void
    {
        Db::insert('login_attempts', [
            'throttle_key' => $throttleKey,
            'ip'           => substr($ip, 0, 45),
            'successful'   => 0,
        ]);
        // Purge opportuniste des vieilles traces (une chance sur vingt).
        if (random_int(1, 20) === 1) {
            Db::exec('DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        }
    }

    /** Efface les échecs d'un identifiant (après connexion réussie). */
    public static function clearFailures(string $throttleKey): void
    {
        Db::exec('DELETE FROM login_attempts WHERE throttle_key = ?', [$throttleKey]);
    }
}
