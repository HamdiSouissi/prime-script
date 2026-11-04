<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentification et contrôle d'accès par rôle.
 *
 * Rôles : commercial (parcours d'appel) / admin (tout + administration).
 */
final class Auth
{
    public const ROLE_COMMERCIAL = 'commercial';
    public const ROLE_ADMIN = 'admin';

    /** Utilisateur connecté (cache mémoire de la requête). */
    private static ?array $user = null;
    private static bool $resolved = false;

    /** Tente une connexion ; renvoie l'utilisateur ou null (message identique dans tous les cas d'échec). */
    public static function attempt(string $email, string $password): ?array
    {
        $user = User::findByEmail($email);
        if ($user === null || !(int) $user['is_active']) {
            // Coût constant pour limiter l'énumération des comptes par le temps de réponse.
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            return null;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        self::login($user);
        return $user;
    }

    /** Ouvre la session pour un utilisateur donné. */
    public static function login(array $user): void
    {
        Session::regenerate(true);
        Session::set('user_id', (int) $user['id']);
        Session::set('user_role', (string) $user['role']);
        Session::set('user_name', (string) $user['name']);
        User::touchLogin((int) $user['id']);
        self::$user = null;
        self::$resolved = false;
    }

    /** Déconnecte l'utilisateur courant. */
    public static function logout(): void
    {
        self::forgetAttempts(self::user()['email'] ?? null);
        Session::destroy();
        self::$user = null;
        self::$resolved = false;
    }

    /** Utilisateur connecté ou null. */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = (int) Session::get('user_id', 0);
        if ($id < 1) {
            return self::$user = null;
        }
        $user = User::find($id);
        // Compte supprimé ou désactivé → déconnexion immédiate.
        if ($user === null || !(int) $user['is_active']) {
            Session::destroy();
            return self::$user = null;
        }
        return self::$user = $user;
    }

    /** Vrai si un utilisateur est connecté. */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** Rôle de l'utilisateur connecté. */
    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    /** Vrai si l'utilisateur est administrateur. */
    public static function isAdmin(): bool
    {
        return self::role() === self::ROLE_ADMIN;
    }

    /** Prénom du commercial (variable de speech {prenom_commercial}). */
    public static function firstName(): string
    {
        $name = (string) (self::user()['name'] ?? '');
        $first = preg_split('/\s+/u', trim($name))[0] ?? '';
        return $first !== '' ? $first : 'votre conseiller';
    }

    /** Exige une session valide, sinon redirection vers la connexion. */
    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            Session::set('_intended', Http::path());
            Session::flash('error', 'Connectez-vous pour accéder à cette page.');
            Http::redirect('/login');
        }
        return $user;
    }

    /** Exige le rôle administrateur (403 pour un commercial). */
    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if (($user['role'] ?? '') !== self::ROLE_ADMIN) {
            self::deny();
        }
        return $user;
    }

    /** Refuse l'accès : page 403 propre. */
    public static function deny(): never
    {
        http_response_code(403);
        if (Http::wantsJson()) {
            Http::json(['ok' => false, 'message' => 'Accès réservé aux administrateurs.'], 403);
        }
        App::view('errors/403', [
            'title' => 'Accès refusé',
            'currentUser' => self::user(),
        ]);
        exit;
    }

    // ------------------------------------------------------ Limitation des tentatives

    /** Clé de limitation : e-mail + IP (stockée en base, table login_attempts). */
    public static function throttleKey(string $email): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        return strtolower(trim($email)) . '|' . $ip;
    }

    /** Nombre de tentatives échouées récentes. */
    public static function failedAttempts(string $email): int
    {
        return User::countRecentFailures(self::throttleKey($email));
    }

    /** Vrai si le quota de tentatives est atteint (blocage temporaire). */
    public static function isLocked(string $email): bool
    {
        return self::failedAttempts($email) >= (int) Config::get('security.login_max_attempts', 5);
    }

    /** Enregistre une tentative échouée. */
    public static function recordFailure(string $email): void
    {
        User::recordFailure(self::throttleKey($email), (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    /** Efface les tentatives après une connexion réussie. */
    public static function forgetAttempts(?string $email = null): void
    {
        $email = $email ?? (string) Session::get('_last_email', '');
        if ($email !== '') {
            User::clearFailures(self::throttleKey($email));
        }
    }
}
