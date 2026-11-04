<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Http;
use App\Core\Session;
use App\Core\Validator;

/**
 * Connexion / déconnexion.
 * Les messages d'erreur sont volontairement génériques (pas d'énumération de comptes).
 */
final class AuthController extends Controller
{
    /** Formulaire de connexion. */
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title'  => 'Connexion',
            'old'    => $this->takeOld(),
            'errors' => $this->takeErrors(),
            'locked' => Auth::isLocked((string) Session::get('_last_email', '')),
            'remaining' => max(0, (int) Config::get('security.login_max_attempts', 5) - Auth::failedAttempts((string) Session::get('_last_email', ''))),
        ], 'auth');
    }

    /** Traite la connexion. */
    public function login(): void
    {
        Http::verifyCsrf();

        $email = (string) Http::text('email', 190);
        $password = Http::raw('password');
        Session::set('_last_email', $email);

        $validator = new Validator(['email' => 'L\'adresse e-mail', 'password' => 'Le mot de passe']);
        $validator->required('email', $email)->email('email', $email)->required('password', $password);

        if ($validator->fails()) {
            $this->backWithErrors('/login', $validator->errors(), ['email' => $email]);
        }

        // Blocage temporaire après trop de tentatives.
        if (Auth::isLocked($email)) {
            $minutes = (int) Config::get('security.login_lock_minutes', 15);
            Session::flash('error', sprintf(
                'Trop de tentatives. Réessayez dans %d minutes ou contactez votre administrateur.',
                $minutes
            ));
            $this->redirect('/login');
        }

        $user = Auth::attempt($email, $password);
        if ($user === null) {
            Auth::recordFailure($email);
            $left = max(0, (int) Config::get('security.login_max_attempts', 5) - Auth::failedAttempts($email));
            $message = 'E-mail ou mot de passe incorrect.';
            if ($left > 0 && $left <= 2) {
                $message .= ' Il reste ' . $left . ' tentative' . ($left > 1 ? 's' : '') . ' avant blocage temporaire.';
            }
            Session::flash('error', $message);
            $this->redirect('/login');
        }

        Auth::forgetAttempts($email);
        Session::forget('_last_email');
        if (function_exists('session_regenerate_id')) {
            Session::regenerate(false);
        }

        Session::flash('success', sprintf('Bonjour %s, prêt pour un nouvel appel ?', Auth::firstName()));

        $intended = (string) Session::get('_intended', '');
        Session::forget('_intended');
        $safeIntended = ($intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//'))
            ? $intended
            : '/';
        $this->redirect($safeIntended);
    }

    /** Déconnexion (POST uniquement, protégée par CSRF). */
    public function logout(): void
    {
        Http::verifyCsrf();
        Auth::logout();
        Session::start();
        Session::flash('success', 'Vous êtes déconnecté. À bientôt !');
        $this->redirect('/login');
    }
}
