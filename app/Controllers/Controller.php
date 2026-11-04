<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;

/**
 * Contrôleur de base : helpers communs (rendu, redirections, erreurs de formulaire).
 */
abstract class Controller
{
    /**
     * Affiche une vue avec la mise en page par défaut.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        $data['currentUser'] = $data['currentUser'] ?? Auth::user();
        App::view($view, $data, $layout);
    }

    /** Redirige. */
    protected function redirect(string $path, int $status = 302): never
    {
        Http::redirect($path, $status);
    }

    /** Message flash de succès + redirection. */
    protected function success(string $path, string $message): never
    {
        Session::flash('success', $message);
        $this->redirect($path);
    }

    /** Message flash d'erreur + redirection. */
    protected function failure(string $path, string $message): never
    {
        Session::flash('error', $message);
        $this->redirect($path);
    }

    /**
     * Erreurs de validation : message global + ancienne saisie, puis retour au formulaire.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed>  $old
     */
    protected function backWithErrors(string $path, array $errors, array $old = []): never
    {
        $first = reset($errors);
        Session::flash('error', is_string($first) && $first !== '' ? $first : 'Merci de vérifier le formulaire.');
        Session::set('_errors', $errors);
        Session::set('_old', $old);
        $this->redirect($path);
    }

    /** Erreurs de formulaire mémorisées (consommées à l'affichage). */
    protected function takeErrors(): array
    {
        $errors = Session::get('_errors', []);
        Session::forget('_errors');
        return is_array($errors) ? $errors : [];
    }

    /** Ancienne saisie mémorisée. */
    protected function takeOld(): array
    {
        return Session::takeOld();
    }

    /** Vrai si la requête est en POST (sinon 405). */
    protected function requirePost(): void
    {
        if (!Http::isPost()) {
            $this->redirect('/');
        }
    }

    /** Récupère une constante de type sûre depuis une query string. */
    protected function queryList(string $key, array $allowed, string $default = ''): string
    {
        $value = (string) Http::query($key, $default);
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
