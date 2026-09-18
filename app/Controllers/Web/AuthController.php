<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Services\TokenService;
use App\Support\App;
use App\Support\Audit;
use App\Support\Database;
use App\Support\Hasher;
use App\Support\Mailer;
use App\Support\RateLimiter;
use App\Support\Response;
use App\Validators\AuthValidator;

final class AuthController extends Controller
{
    public function showRegister(): Response
    {
        if (App::user() !== null) {
            return Response::redirect(app_url('dashboard'));
        }

        return Response::view('auth/register');
    }

    public function register(): Response
    {
        $data = $this->request->only(['first_name', 'last_name', 'email', 'phone', 'password', 'password_confirmation']);
        $result = $this->auth->register($data, $this->request);

        if ($result['user'] !== null) {
            App::flash('success', 'Votre compte a été créé. Vérifiez votre e-mail pour confirmer votre adresse.');

            return Response::redirect(app_url('login'));
        }

        if (($result['errors'] ?? []) !== []) {
            foreach ($result['errors'] as $message) {
                App::flash('error', $message);
            }
            App::remember($this->request->only(['first_name', 'last_name', 'email', 'phone']));

            return Response::redirectBack();
        }

        App::flash('error', $result['error'] ?? 'Inscription impossible, réessayez.');
        App::remember($this->request->only(['first_name', 'last_name', 'email', 'phone']));

        return Response::redirectBack();
    }

    public function showLogin(): Response
    {
        if (App::user() !== null) {
            return Response::redirect(app_url('dashboard'));
        }

        return Response::view('auth/login');
    }

    public function login(): Response
    {
        $data = $this->request->only(['email', 'password']);
        $result = $this->auth->login($data, $this->request);

        if ($result['user'] !== null) {
            App::clearOld();

            return Response::redirect(app_url('dashboard'));
        }

        App::flash('error', $result['error']);
        App::remember(['email' => $result['email']]);

        return Response::redirectBack();
    }

    public function logout(): Response
    {
        $this->auth->logout($this->request);

        return Response::redirect(app_url('login'));
    }

    public function verifyEmail(string $token): Response
    {
        $userId = TokenService::consume('email_verification_tokens', $token);

        if ($userId === null) {
            App::flash('error', 'Ce lien de vérification est invalide ou a expiré.');

            return Response::redirect(app_url('login'));
        }

        $user = (new UserRepository())->findById($userId);

        if ($user === null) {
            App::flash('error', 'Compte introuvable.');

            return Response::redirect(app_url('register'));
        }

        if (!empty($user['email_verified_at'])) {
            App::flash('success', 'Votre adresse e-mail est déjà vérifiée.');

            return Response::redirect(app_url('login'));
        }

        (new UserRepository())->markEmailVerified($userId);
        Audit::log('auth.email_verified', 'users', $userId, [], ['email' => $user['email']], $userId);

        App::flash('success', 'Votre adresse e-mail a été confirmée. Vous pouvez vous connecter.');

        return Response::redirect(app_url('login'));
    }

    public function showForgotPassword(): Response
    {
        return Response::view('auth/forgot-password');
    }

    public function forgotPassword(): Response
    {
        $data = $this->request->only(['email']);
        $validation = AuthValidator::forgot($data);

        if (!$validation->passes()) {
            App::flash('error', $validation->firstMessage());
            App::remember(['email' => $data['email'] ?? '']);

            return Response::redirectBack();
        }

        $email = mb_strtolower(trim((string) $data['email']));
        $limit = config('security.rate_limit.password_reset_request', ['max_attempts' => 5, 'decay_minutes' => 60]);

        if (!RateLimiter::attempt(RateLimiter::ipKey($this->request), (int) $limit['max_attempts'], (int) $limit['decay_minutes'] * 60)) {
            App::flash('error', 'Trop de demandes. Réessayez plus tard.');

            return Response::redirectBack();
        }

        $user = (new UserRepository())->findByEmail($email);

        // Réponse identique que le compte existe ou non (anti-énumération).
        if ($user !== null) {
            $expiry = (int) config('mail.password_reset.expires_minutes', 60);
            $token = TokenService::issue('password_reset_tokens', (int) $user['id'], $expiry);

            Mailer::sendTemplate(
                $user['email'],
                'Réinitialisation de votre mot de passe — SeneBridge',
                'reset-password',
                [
                    'token' => $token,
                    'expiryMinutes' => $expiry,
                    'appName' => config('app.name'),
                ]
            );

            Audit::log('auth.password_reset_requested', 'users', (int) $user['id'], [], ['email' => $email]);
        }

        App::flash('success', 'Si un compte existe avec cette adresse, un lien de réinitialisation vient d\'être envoyé.');

        return Response::redirect(app_url('login'));
    }

    public function showResetPassword(string $token): Response
    {
        return Response::view('auth/reset-password', [
            'token' => $token,
        ]);
    }

    public function resetPassword(): Response
    {
        $data = $this->request->only(['email', 'token', 'password', 'password_confirmation']);
        $validation = AuthValidator::reset($data);

        if (!$validation->passes()) {
            App::flash('error', $validation->firstMessage());

            return Response::redirectBack();
        }

        $userId = TokenService::consume('password_reset_tokens', (string) $data['token'], (string) $data['email']);

        if ($userId === null) {
            App::flash('error', 'Ce lien de réinitialisation est invalide, expiré ou déjà utilisé.');

            return Response::redirect(app_url('forgot-password'));
        }

        Database::transaction(function () use ($userId, $data) {
            (new UserRepository())->updatePassword($userId, Hasher::make((string) $data['password']));

            Audit::log('auth.password_reset_completed', 'users', $userId, [], ['user_id' => $userId]);
        });

        App::flash('success', 'Votre mot de passe a été réinitialisé. Connectez-vous.');

        return Response::redirect(app_url('login'));
    }
}