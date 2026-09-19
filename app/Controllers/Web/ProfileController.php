<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Services\ApiTokenService;
use App\Support\App;
use App\Support\Audit;
use App\Support\Hasher;
use App\Support\Response;
use App\Validators\ProfileValidator;

final class ProfileController extends Controller
{
    private UserRepository $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserRepository();
    }

    public function show(): Response
    {
        $user = App::user();

        if ($user === null) {
            return Response::redirect(app_url('login'));
        }

        return Response::view('client/profile', [
            'user' => $user,
        ]);
    }

    public function update(): Response
    {
        $user = App::user();
        $data = $this->request->only(['first_name', 'last_name', 'phone', 'locality', 'language']);
        $validation = ProfileValidator::update($data);

        if (!$validation->passes()) {
            App::flash('error', $validation->firstMessage());

            return Response::redirectBack();
        }

        UserRepository::assertUniqueContact($user['id'], null, $data['phone'] ?? null);
        $this->users->updateProfile((int) $user['id'], $data);
        Audit::log('profile.updated', 'users', (int) $user['id'], [], ['user_id' => (int) $user['id']]);

        App::flash('success', 'Votre profil a été mis à jour.');

        return Response::redirect(route('client.profile'));
    }

    public function password(): Response
    {
        $user = App::user();
        $data = $this->request->only(['current_password', 'password', 'password_confirmation']);
        $validation = ProfileValidator::password($data);

        if (!$validation->passes()) {
            App::flash('error', $validation->firstMessage());

            return Response::redirectBack();
        }

        $current = $this->users->findById((int) $user['id']);

        if ($current === null || !Hasher::verify((string) $data['current_password'], (string) $current['password_hash'])) {
            App::flash('error', 'Votre mot de passe actuel est incorrect.');

            return Response::redirectBack();
        }

        $this->users->updatePassword((int) $user['id'], Hasher::make((string) $data['password']));

        // La session courante reste valide ; les autres sessions et tokens API
        // sont invalidés (session_version incrémentée + révocation).
        $fresh = $this->users->findById((int) $user['id']);
        App::setSession('auth.session_version', (int) ($fresh['session_version'] ?? 0));
        (new ApiTokenService())->revokeForUser((int) $user['id']);

        Audit::log('profile.password_changed', 'users', (int) $user['id'], [], ['user_id' => (int) $user['id']]);

        App::flash('success', 'Votre mot de passe a été modifié.');

        return Response::redirect(route('client.profile'));
    }
}