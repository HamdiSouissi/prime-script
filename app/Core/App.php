<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Conteneur de services et rendu des vues.
 *
 * Le conteneur reste volontairement extrêmement simple : une fabrique de contrôleurs
 * et un registre de services partagés (permet d'enrichir l'application sans framework).
 */
final class App
{
    /** @var array<string, mixed> */
    private static array $services = [];

    /** Fabrique de contrôleurs : « Admin\BlockController » → instance. */
    public static function make(string $class): object
    {
        $fqcn = 'App\\Controllers\\' . ltrim($class, '\\');
        if (!class_exists($fqcn)) {
            throw new \RuntimeException('Contrôleur introuvable : ' . $fqcn);
        }
        return new $fqcn();
    }

    /** Enregistre / récupère un service partagé. */
    public static function service(string $key, ?callable $factory = null): mixed
    {
        if (array_key_exists($key, self::$services)) {
            return self::$services[$key];
        }
        return self::$services[$key] = $factory ? $factory() : null;
    }

    /**
     * Affiche une vue avec une mise en page.
     *
     * @param string               $view   chemin relatif dans app/Views (sans .php)
     * @param array<string, mixed> $data   variables exposées à la vue
     * @param string               $layout mise en page de app/Views/layouts
     */
    public static function view(string $view, array $data = [], string $layout = 'app'): void
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vue introuvable : ' . $view);
        }
        $layoutFile = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';

        ViewContext::push($data);
        ob_start();
        extract($data, EXTR_SKIP);
        require $file;
        $content = (string) ob_get_clean();
        ViewContext::pop();

        if ($layout === '') {
            echo $content;
            return;
        }
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Mise en page introuvable : ' . $layout);
        }
        extract($data, EXTR_SKIP);
        $content = $content;
        require $layoutFile;
    }

    /**
     * Rend une vue partielle dans une chaîne (pour AJAX ou aperçu).
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vue introuvable : ' . $view);
        }
        ViewContext::push($data);
        ob_start();
        extract($data, EXTR_SKIP);
        require $file;
        ViewContext::pop();
        return (string) ob_get_clean();
    }
}

/**
 * Pile de contexte des vues : permet aux partiels d'accéder aux variables
 * de la vue appelante (ex. le fil d'ariane d'un bloc).
 */
final class ViewContext
{
    /** @var array<int, array<string, mixed>> */
    private static array $stack = [];

    /** @param array<string, mixed> $data */
    public static function push(array $data): void
    {
        self::$stack[] = $data;
    }

    public static function pop(): void
    {
        array_pop(self::$stack);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        for ($i = count(self::$stack) - 1; $i >= 0; $i--) {
            if (array_key_exists($key, self::$stack[$i])) {
                return self::$stack[$i][$key];
            }
        }
        return $default;
    }
}
