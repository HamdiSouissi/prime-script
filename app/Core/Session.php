<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Gestion des sessions sécurisées et des jetons CSRF.
 */
final class Session
{
    /** Démarre la session avec des cookies durcis. */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name = (string) Config::get('session.name', 'PRIMESCRIPT_SESSION');
        $lifetime = (int) Config::get('session.lifetime', 28800);
        $secureConfig = Config::get('session.cookie_secure', 'auto');
        $secure = $secureConfig === 'auto' ? Http::isHttps() : filter_var($secureConfig, FILTER_VALIDATE_BOOL);
        $sameSite = (string) Config::get('session.cookie_samesite', 'Lax');

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => in_array($sameSite, ['Lax', 'Strict', 'None'], true) ? $sameSite : 'Lax',
        ]);

        session_start();

        // Empreinte légère du navigateur : limite le détournement de session.
        $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . (string) Config::get('session.key'));
        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = $fingerprint;
        } elseif (!hash_equals($_SESSION['_fingerprint'], $fingerprint)) {
            self::destroy();
            session_start();
            $_SESSION['_fingerprint'] = $fingerprint;
        }

        // Expiration d'inactivité.
        if (isset($_SESSION['_last_activity']) && (time() - (int) $_SESSION['_last_activity']) > $lifetime) {
            self::regenerate(true);
        }
        $_SESSION['_last_activity'] = time();
    }

    /** Lecture d'une valeur de session. */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /** Écriture d'une valeur de session. */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /** Suppression d'une clé. */
    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Vrai si la clé existe. */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /** Message flash consommé à l'affichage suivant. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Récupère et vide les messages flash.
     *
     * @return array<int, array{type: string, message: string}>
     */
    public static function takeFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($flashes) ? $flashes : [];
    }

    /** Mémorise une ancienne saisie (pour la réafficher en cas d'erreur de formulaire). */
    public static function setOld(array $data): void
    {
        $data['_token'] = null; // jamais conservé
        unset($data['password'], $data['password_confirm'], $data['_token']);
        $_SESSION['_old'] = $data;
    }

    /** Récupère puis vide l'ancienne saisie. */
    public static function takeOld(): array
    {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return is_array($old) ? $old : [];
    }

    /** Jeton CSRF de la session courante. */
    public static function csrfToken(): string
    {
        $token = $_SESSION['_csrf']['value'] ?? null;
        $created = (int) ($_SESSION['_csrf']['created'] ?? 0);
        $ttl = (int) Config::get('security.csrf_ttl_hours', 4) * 3600;

        if (!is_string($token) || $token === '' || (time() - $created) > $ttl) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['_csrf'] = ['value' => $token, 'created' => time()];
        }
        return $token;
    }

    /** Vérifie un jeton CSRF reçu. */
    public static function validateCsrf(?string $token): bool
    {
        $expected = $_SESSION['_csrf']['value'] ?? null;
        return is_string($expected) && is_string($token) && $token !== '' && hash_equals($expected, $token);
    }

    /** Régénère l'ID de session (à la connexion, à la déconnexion, en cas d'expiration). */
    public static function regenerate(bool $destroyData = false): void
    {
        $data = $_SESSION;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        if ($destroyData) {
            $_SESSION = [];
        }
        // Conserve l'empreinte et l'activité, invalide les jetons sensibles.
        if (isset($data['_fingerprint'])) {
            $_SESSION['_fingerprint'] = $data['_fingerprint'];
        }
        $_SESSION['_last_activity'] = time();
        unset($_SESSION['_csrf']);
    }

    /** Détruit complètement la session. */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'] ?? '/',
                'domain'   => $params['domain'] ?? '',
                'secure'   => (bool) ($params['secure'] ?? false),
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
            session_destroy();
        }
    }
}
