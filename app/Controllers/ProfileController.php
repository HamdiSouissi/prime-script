<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Call;
use App\Models\User;

/**
 * « Mon compte » : informations personnelles et changement de mot de passe.
 */
final class ProfileController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();

        $this->view('profile/index', [
            'title'  => 'Mon compte',
            'user'   => $user,
            'stats'  => Call::statsForUser((int) $user['id']),
            'errors' => $this->takeErrors(),
            'subnav' => '',
        ]);
    }

    /** Changement de mot de passe par l'utilisateur (ancien mot de passe exigé). */
    public function updatePassword(): void
    {
        Http::verifyCsrf();
        $user = Auth::user();

        $current = Http::raw('current_password');
        $password = Http::raw('password');
        $confirmation = Http::raw('password_confirm');

        $validator = new Validator([
            'current_password' => 'Le mot de passe actuel',
            'password'         => 'Le nouveau mot de passe',
        ]);
        $validator
            ->required('current_password', $current)
            ->required('password', $password)
            ->check('current_password', password_verify($current, (string) $user['password_hash']), 'Le mot de passe actuel est incorrect.')
            ->check('password', User::passwordErrors($password, $confirmation) === null, (string) User::passwordErrors($password));

        if ($validator->fails()) {
            $this->backWithErrors('/profil', $validator->errors());
        }

        User::updatePassword((int) $user['id'], $password);
        // Régénère la session : les autres onglets devront se reconnecter.
        Session::regenerate(false);
        Session::flash('success', 'Mot de passe modifié. Les autres sessions de ce compte ne sont plus valides.');
        $this->redirect('/profil');
    }
}
