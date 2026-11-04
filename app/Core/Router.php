<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Routeur simple : table de routes (méthode + motif → contrôleur@action),
 * paramètres nommés « {id} », gestion centralisée des erreurs.
 */
final class Router
{
    /** @var array<int, array{method: string, pattern: string, regex: string, handler: string, middleware: string[]}> */
    private array $routes = [];

    /** Nombre maximal de redirections suivies pour les routes « legacy ». */
    private const MAX_CHAIN = 5;

    /**
     * Enregistre une route.
     *
     * @param string   $method     GET | POST | ANY
     * @param string   $path       /admin/blocs/{id}/modifier
     * @param string   $handler    Admin\BlockController@edit
     * @param string[] $middleware guest|auth|admin
     */
    public function add(string $method, string $path, string $handler, array $middleware = []): void
    {
        $pattern = '/' . trim($path, '/');
        $regex = preg_replace_callback(
            '#\{([a-z_]+)\}#',
            static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );

        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $path,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /** Raccourcis lisibles. */
    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** Résout et exécute la requête courante. */
    public function dispatch(): void
    {
        $method = Http::method();
        $path = Http::path();

        // 1) Comparaison exacte (méthode + URL).
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->invoke($route, $params);
            return;
        }

        // 2) Une route existe pour cette URL mais avec une autre méthode → 405.
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path) === 1) {
                $this->methodNotAllowed($route['method']);
            }
        }

        // 3) Redirections de compatibilité (anciennes URL sans .php, liens externes).
        if ($this->followLegacy($path)) {
            return;
        }

        $this->notFound();
    }

    /**
     * Exécute une route : garde-fous (auth/admin), puis action du contrôleur.
     *
     * @param array{handler: string, middleware: string[]} $route
     * @param array<string, string>                        $params
     */
    private function invoke(array $route, array $params): void
    {
        $middleware = $route['middleware'];
        if (in_array('admin', $middleware, true)) {
            Auth::requireAdmin();
        } elseif (in_array('auth', $middleware, true)) {
            Auth::requireLogin();
        } elseif (in_array('guest', $middleware, true) && Auth::check()) {
            Http::redirect('/');
        }

        [$controllerName, $action] = array_pad(explode('@', $route['handler'], 2), 2, 'index');
        $action = $action ?: 'index';

        try {
            $controller = App::make($controllerName);
            if (!method_exists($controller, $action)) {
                throw new \RuntimeException('Action introuvable : ' . $route['handler']);
            }
            $controller->{$action}(...array_values($params));
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /** Gestion centralisée des erreurs (aucun détail technique visible en production). */
    private function handleException(\Throwable $e): void
    {
        http_response_code(500);
        error_log('[Prime Script] ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());

        if (!headers_sent() && Config::isDev()) {
            echo '<div style="font:14px/1.5 ui-monospace,monospace;padding:24px">';
            echo '<h1 style="font-size:18px">Erreur applicative</h1>';
            echo '<p><strong>' . Sanitizer::e($e->getMessage()) . '</strong></p>';
            echo '<p>' . Sanitizer::e($e->getFile() . ':' . $e->getLine()) . '</p>';
            echo '<pre style="white-space:pre-wrap;background:#f4f4f4;padding:16px">' . Sanitizer::e($e->getTraceAsString()) . '</pre>';
            echo '</div>';
            return;
        }

        if (Http::wantsJson()) {
            Http::json(['ok' => false, 'message' => 'Une erreur est survenue. Merci de réessayer.'], 500);
        }
        try {
            App::view('errors/500', ['title' => 'Une erreur est survenue', 'currentUser' => Auth::user()]);
        } catch (\Throwable) {
            echo '<h1>Une erreur est survenue</h1><p>Merci de réessayer dans quelques instants.</p>';
        }
    }

    /** Réponse 404 soignée. */
    private function notFound(): void
    {
        http_response_code(404);
        if (Http::wantsJson()) {
            Http::json(['ok' => false, 'message' => 'Ressource introuvable.'], 404);
        }
        try {
            App::view('errors/404', ['title' => 'Page introuvable', 'currentUser' => Auth::user()]);
        } catch (\Throwable) {
            echo '<h1>Page introuvable</h1><p><a href="' . Http::url('/') . '">Retour à l\'accueil</a></p>';
        }
    }

    /** Réponse 405. */
    private function methodNotAllowed(string $allowed): void
    {
        http_response_code(405);
        header('Allow: ' . $allowed);
        if (Http::wantsJson()) {
            Http::json(['ok' => false, 'message' => 'Méthode non autorisée.'], 405);
        }
        Session::flash('error', 'Action non autorisée.');
        Http::redirect('/');
    }

    /**
     * Table de redirections permanentes (301) : permet de conserver les anciens liens
     * et d'éviter les URL en double (bon pour le référencement et l'usage interne).
     */
    private function followLegacy(string $path): bool
    {
        $legacy = [
            '/index.php'      => '/',
            '/dashboard'      => '/',
            '/accueil'        => '/',
            '/nouvel-appel'   => '/appel/nouveau',
            '/call'           => '/appel/nouveau',
            '/logout'         => '/deconnexion',
            '/admin/login'    => '/login',
        ];
        if (!isset($legacy[$path])) {
            return false;
        }
        Http::redirect($legacy[$path], 301);
    }
}
