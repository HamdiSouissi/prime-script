<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Accès à la configuration (config/config.php) par notation pointée.
 *
 * Config::get('database.host') renvoie la valeur de $config['database']['host'].
 */
final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    /** Charge le fichier de configuration (une seule fois). */
    public static function load(?array $data = null): void
    {
        if ($data !== null) {
            self::$data = $data;
            return;
        }
        if (self::$data !== null) {
            return;
        }

        $file = BASE_PATH . '/config/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'Configuration manquante : copiez config/config.example.php vers config/config.php.';
            exit;
        }

        /** @var array<string, mixed> $config */
        $config = require $file;
        self::$data = $config;
    }

    /** Récupère une valeur de configuration par clé pointée. */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        $value = self::$data;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /** Indique si l'application tourne en mode développement. */
    public static function isDev(): bool
    {
        return self::get('app.env', 'production') === 'development';
    }
}
