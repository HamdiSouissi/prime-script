<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Requête HTTP entrante : lecture des entrées, détection de l'URL de base, garde CSRF.
 */
final class Http
{
    private static ?string $baseUrl = null;

    /** Méthode HTTP courante (GET, POST...). */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Vrai si la requête est en POST. */
    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /** Valeur nettoyée depuis $_GET. */
    public static function query(string $key, mixed $default = null): mixed
    {
        return self::clean($_GET[$key] ?? null) ?? $default;
    }

    /** Valeur nettoyée depuis $_POST (ou $_GET en repli pour les recherches). */
    public static function input(string $key, mixed $default = null): mixed
    {
        return self::clean($_POST[$key] ?? $_GET[$key] ?? null) ?? $default;
    }

    /** Valeur brute (pour les champs qui seront assainis par un traitement dédié). */
    public static function raw(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /** Champ texte nettoyé. */
    public static function text(string $key, int $maxLength = 255, string $default = ''): string
    {
        $value = self::clean($_POST[$key] ?? $_GET[$key] ?? null);
        return $value === null ? $default : Sanitizer::text($value, $maxLength);
    }

    /** Champ texte multiligne nettoyé. */
    public static function textarea(string $key, int $maxLength = 8000): string
    {
        $value = self::clean($_POST[$key] ?? $_GET[$key] ?? null);
        return $value === null ? '' : Sanitizer::textarea($value, $maxLength);
    }

    /** Identifiant entier. */
    public static function id(string $key): int
    {
        return Sanitizer::id($_POST[$key] ?? $_GET[$key] ?? null);
    }

    /** Vrai si une case à cocher est cochée. */
    public static function bool(string $key): bool
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        return $value === '1' || $value === 1 || $value === true || $value === 'on' || $value === 'true';
    }

    /** Nettoie récursivement les valeurs scalaires reçues. */
    private static function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[is_string($key) ? Sanitizer::text($key, 60) : $key] = self::clean($item);
            }
            return $out;
        }
        if (is_string($value)) {
            // Protection contre l'injection d'octets nuls et l'encodage invalide.
            $value = str_replace("\0", '', $value);
            return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
        return $value;
    }

    /** Vrai si la connexion est chiffrée. */
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }
        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /** Chemin demandé, sans la chaîne de requête (toujours commençant par /). */
    public static function path(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        // Si l'application est installée dans un sous-dossier, on retire ce préfixe.
        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
        if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        $path = '/' . ltrim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    /**
     * URL de base, sans slash final (ex. https://exemple.fr/prime-script).
     * Ordre : config.app.base_url, puis détection automatique depuis la requête.
     */
    public static function baseUrl(): string
    {
        if (self::$baseUrl !== null) {
            return self::$baseUrl;
        }

        $configured = trim((string) Config::get('app.base_url', ''));
        if ($configured !== '') {
            return self::$baseUrl = rtrim($configured, '/');
        }

        $scheme = self::isHttps() ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
        $scriptDir = rtrim($scriptDir, '/');
        if ($scriptDir === '.' || $scriptDir === '/') {
            $scriptDir = '';
        }
        return self::$baseUrl = $scheme . '://' . $host . $scriptDir;
    }

    /** Construit une URL absolue à partir d'un chemin interne. */
    public static function url(string $path = '/'): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    /** Redirige vers un chemin interne et termine le script. */
    public static function redirect(string $path, int $status = 302): never
    {
        $location = preg_match('#^https?://#i', $path) ? $path : self::url($path);
        if (!headers_sent()) {
            header('Location: ' . $location, true, $status);
        }
        exit;
    }

    /** Revenir à la page précédente (interne uniquement). */
    public static function back(string $fallback = '/'): never
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $base = self::baseUrl();
        if ($referer !== '' && str_starts_with($referer, $base)) {
            self::redirect($referer);
        }
        self::redirect($fallback);
    }

    /** Vrai si la requête attend une réponse JSON (fetch). */
    public static function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
    }

    /** Envoie une réponse JSON et termine le script. */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Vérifie le jeton CSRF de la requête POST.
     * En cas d'échec : 419 (JSON) ou retour arrière avec message d'erreur (HTML).
     */
    public static function verifyCsrf(): void
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (Session::validateCsrf(is_string($token) ? $token : null)) {
            return;
        }

        if (self::wantsJson()) {
            self::json(['ok' => false, 'message' => 'Session expirée. Rechargez la page puis réessayez.'], 419);
        }
        Session::flash('error', 'Votre session a expiré. Merci de réessayer.');
        self::back('/');
    }

    /** Champ « piège » anti-robot : doit rester vide. */
    public static function isHoneypotClean(string $field = 'site_web'): bool
    {
        return trim((string) ($_POST[$field] ?? '')) === '';
    }
}
