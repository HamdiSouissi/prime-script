<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Http;
use App\Core\Sanitizer;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

/**
 * Gestion des comptes : création, rôle, activation, réinitialisation du mot de passe.
 * Les comptes ne peuvent pas être supprimés (l'historique d'appels doit rester cohérent) :
 * on les désactive.
 */
final class UserController extends AdminController
{
    public function index(): void
    {
        $this->adminView('admin/users/index', [
            'title' => 'Équipe',
            'users' => User::withStats(),
        ], 'users');
    }

    public function create(): void
    {
        $this->adminView('admin/users/form', [
            'title'  => 'Nouveau compte',
            'user'   => ['id' => 0, 'name' => '', 'email' => '', 'role' => Auth::ROLE_COMMERCIAL, 'is_active' => 1],
            'errors' => $this->takeErrors(),
            'old'    => $this->takeOld(),
            'isNew'  => true,
        ], 'users');
    }

    public function store(): void
    {
        Http::verifyCsrf();

        $name = Http::text('name', 120);
        $email = Sanitizer::email(Http::input('email', ''));
        $role = Http::text('role', 20);
        $password = Http::raw('password');
        $confirmation = Http::raw('password_confirm');

        $validator = new Validator([
            'name'     => 'Le nom',
            'email'    => 'L\'adresse e-mail',
            'role'     => 'Le rôle',
            'password' => 'Le mot de passe',
        ]);
        $validator
            ->required('name', $name)->maxLength('name', $name, 120)
            ->required('email', Http::input('email', ''))
            ->email('email', Http::input('email', ''))
            ->inList('role', $role, [Auth::ROLE_COMMERCIAL, Auth::ROLE_ADMIN])
            ->required('password', $password)
            ->check('email', $email === null || User::findByEmail($email) === null, 'Cette adresse e-mail est déjà utilisée.')
            ->check('password', User::passwordErrors($password) === null, (string) User::passwordErrors($password))
            ->check('password', $password === $confirmation, 'Les deux mots de passe ne correspondent pas.');

        if ($validator->fails()) {
            $this->backWithErrors('/admin/utilisateurs/nouveau', $validator->errors(), [
                'name'  => $name,
                'email' => (string) Http::text('email', 190),
                'role'  => $role,
            ]);
        }

        User::createWithPassword($name, (string) $email, $password, $role);
        Session::flash('success', sprintf('Compte créé pour %s. Communiquez-lui son mot de passe provisoire.', $name));
        $this->redirect('/admin/utilisateurs');
    }

    public function edit(string $id): void
    {
        $user = User::find((int) $id);
        if ($user === null) {
            $this->failure('/admin/utilisateurs', 'Ce compte n\'existe plus.');
        }
        $old = $this->takeOld();

        $this->adminView('admin/users/form', [
            'title'  => 'Modifier le compte',
            'user'   => $old !== [] ? array_merge($user, $old) : $user,
            'errors' => $this->takeErrors(),
            'old'    => [],
            'isNew'  => false,
        ], 'users');
    }

    public function update(string $id): void
    {
        Http::verifyCsrf();
        $userId = (int) $id;
        $user = User::find($userId);
        if ($user === null) {
            $this->failure('/admin/utilisateurs', 'Ce compte n\'existe plus.');
        }

        $name = Http::text('name', 120);
        $email = Sanitizer::email(Http::input('email', ''));
        $role = Http::text('role', 20);

        $validator = new Validator(['name' => 'Le nom', 'email' => 'L\'adresse e-mail', 'role' => 'Le rôle']);
        $validator
            ->required('name', $name)->maxLength('name', $name, 120)
            ->required('email', Http::input('email', ''))
            ->email('email', Http::input('email', ''))
            ->inList('role', $role, [Auth::ROLE_COMMERCIAL, Auth::ROLE_ADMIN]);

        $existing = $email !== null ? User::findByEmail($email) : null;
        $validator->check('email', $existing === null || (int) $existing['id'] === $userId, 'Cette adresse e-mail est déjà utilisée.');

        // Sécurité : ne jamais laisser l'équipe sans administrateur actif.
        $isSelf = $userId === (int) Auth::user()['id'];
        if ((string) $user['role'] === Auth::ROLE_ADMIN && $role !== Auth::ROLE_ADMIN) {
            $validator->check('role', User::countActive(Auth::ROLE_ADMIN) > 1, 'Impossible : il doit rester au moins un administrateur actif.');
        }

        if ($validator->fails()) {
            $this->backWithErrors('/admin/utilisateurs/' . $userId . '/modifier', $validator->errors(), [
                'name'  => $name,
                'email' => (string) Http::text('email', 190),
                'role'  => $role,
            ]);
        }

        User::update($userId, [
            'name'  => $name,
            'email' => (string) $email,
            'role'  => $role,
        ]);

        Session::flash('success', 'Compte mis à jour.');
        $this->redirect('/admin/utilisateurs');
    }

    /** Active / désactive un compte. */
    public function toggle(string $id): void
    {
        Http::verifyCsrf();
        $userId = (int) $id;
        $user = User::find($userId);
        if ($user === null) {
            $this->failure('/admin/utilisateurs', 'Ce compte n\'existe plus.');
        }

        if ($userId === (int) Auth::user()['id']) {
            $this->failure('/admin/utilisateurs', 'Vous ne pouvez pas désactiver votre propre compte.');
        }
        if ((string) $user['role'] === Auth::ROLE_ADMIN && (int) $user['is_active'] === 1 && User::countActive(Auth::ROLE_ADMIN) <= 1) {
            $this->failure('/admin/utilisateurs', 'Impossible : il doit rester au moins un administrateur actif.');
        }

        $newState = (int) $user['is_active'] === 1 ? 0 : 1;
        User::setActive($userId, $newState === 1);
        Session::flash('success', $newState === 1
            ? sprintf('Compte de %s réactivé.', (string) $user['name'])
            : sprintf('Compte de %s désactivé : la connexion est désormais refusée.', (string) $user['name']));
        $this->redirect('/admin/utilisateurs');
    }

    /** Réinitialise le mot de passe (saisie manuelle). */
    public function resetPassword(string $id): void
    {
        Http::verifyCsrf();
        $userId = (int) $id;
        $user = User::find($userId);
        if ($user === null) {
            $this->failure('/admin/utilisateurs', 'Ce compte n\'existe plus.');
        }

        $password = Http::raw('password');
        $confirmation = Http::raw('password_confirm');

        $error = User::passwordErrors($password, $confirmation);
        if ($error !== null) {
            $this->failure('/admin/utilisateurs/' . $userId . '/modifier', $error);
        }

        User::updatePassword($userId, $password);
        // Sécurité : on invalide les tentatives de connexion en cours.
        User::clearFailures(Auth::throttleKey((string) $user['email']));

        Session::flash('success', sprintf('Mot de passe réinitialisé pour %s. Transmettez-le de façon sécurisée.', (string) $user['name']));
        $this->redirect('/admin/utilisateurs/' . $userId . '/modifier');
    }
}
