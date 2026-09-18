<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Repositories\UserRepository;
use App\Support\App;
use App\Support\Audit;
use App\Support\Database;
use App\Support\Hasher;
use App\Support\Mailer;
use App\Support\RateLimiter;
use App\Support\Request;
use App\Validators\AuthValidator;

/**
 * Flux d'authentification complets (inscription, connexion, récupération).
 * Toutes les vérifications de sécurité sont côté serveur.
 */
final class AuthService
{
    private const EMAIL_VERIFICATION_TABLE = 'email_verification_tokens';
    private const PASSWORD_RESET_TABLE = 'password_reset_tokens';

    private UserRepository $users;

    public function __construct(?UserRepository $users = null)
    {
        $this->users = $users ?? new UserRepository();
    }

    /**
     * Inscription client.
     *
     * @return array{user: array<string, mixed>|null, error: string|null, errors: array<string,string>}
     */
    public function register(array $data, Request $request): array
    {
        $validation = AuthValidator::register($data);

        if ($request->isJson() || $request->wantsJson()) {
            return ['user' => null, 'error' => $validation->firstMessage(), 'errors' => $validation->errors(), 'redirect' => null];
        }

        if (!$validation->passes()) {
            return ['user' => null, 'error' => null, 'errors' => $validation->errors(), 'redirect' => null];
        }

        // Rate limiting global par IP.
        $limit = config('security.rate_limit.register', ['max_attempts' => 10, 'decay_minutes' => 60]);
        if (!RateLimiter::attempt(
            RateLimiter::ipKey($request),
            (int) $limit['max_attempts'],
            (int) $limit['decay_minutes'] * 60
        )) {
            return ['user' => null, 'error' => 'Trop de tentatives d\'inscription. Réessayez plus tard.', 'errors' => [], 'redirect' => null];
        }

        // Unicité de l'e-mail (normalisé).
        if ($this->users->emailExists((string) $data['email'])) {
            return ['user' => null, 'error' => 'Un compte existe déjà avec cette adresse e-mail. Pouvez-vous vous connecter ?', 'errors' => [], 'redirect' => null];
        }

        // Unicité du téléphone si fourni.
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '') {
            $existingByPhone = Database::first('SELECT id FROM users WHERE phone = ? LIMIT 1', [$phone]);
            if ($existingByPhone !== null) {
                return ['user' => null, 'error' => 'Un compte existe déjà avec ce numéro de téléphone.', 'errors' => [], 'redirect' => null];
            }
        }

        $passwordHash = Hasher::make((string) $data['password']);

        $user = Database::transaction(function () use ($data, $passwordHash, $phone) {
            $created = $this->users->createClient($data, $passwordHash);

            return $created;
        });

        if ($user === null) {
            return ['user' => null, 'error' => 'Impossible de créer le compte. Veuillez réessayer.', 'errors' => [], 'redirect' => null];
        }

        // Jeton de vérification e-mail + envoi.
        $this->sendVerificationEmail((int) $user['id'], (string) $user['email']);

        Audit::log('auth.register', 'users', (int) $user['id'], [], [
            'email' => $user['email'],
            'role' => Role::CLIENT,
        ]);

        return ['user' => $user, 'error' => null, 'errors' => [], 'redirect' => null];
    }

    private function sendVerificationEmail(int $userId, string $email): void
    {
        $expiry = (int) config('mail.verification.expires_minutes', 120);
        $token = TokenService::issue(self::EMAIL_VERIFICATION_TABLE, $userId, $expiry);

        Mailer::sendTemplate(
            $email,
            'Vérifiez votre adresse e-mail — SeneBridge',
            'verify-email',
            [
                'token' => $token,
                'expiryMinutes' => $expiry,
                'appName' => config('app.name'),
            ]
        );
    }

    /**
     * Connexion.
     *
     * @return array{user: array<string, mixed>|null, error: string|null, email: string}
     */
    public function login(array $data, Request $request): array
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $validation = AuthValidator::login(['email' => $email, 'password' => $password]);

        if (!$validation->passes()) {
            return ['user' => null, 'error' => 'Identifiants invalides.', 'email' => $email];
        }

        // Protection brute force : limite par IP ET par compte.
        $limit = config('security.rate_limit.login', ['max_attempts' => 5, 'decay_minutes' => 15]);
        $decaySeconds = (int) $limit['decay_minutes'] * 60;

        $ipAllowed = RateLimiter::attempt(RateLimiter::ipKey($request), (int) $limit['max_attempts'], $decaySeconds);
        $accountAllowed = RateLimiter::attempt('login:' . $email, (int) $limit['max_attempts'], $decaySeconds);

        if (!$ipAllowed || !$accountAllowed) {
            Audit::log('auth.login.too_many', 'users', null, [], ['email' => $email]);

            return ['user' => null, 'error' => 'Trop de tentatives. Réessayez plus tard.', 'email' => $email];
        }

        $user = $this->users->findByEmail($email);

        $authenticated = $user !== null && Hasher::verify($password, (string) ($user['password_hash'] ?? ''));

        if (!$authenticated || $user === null) {
            Audit::log('auth.login.failed', 'users', $user['id'] ?? null, [], ['email' => $email]);

            return ['user' => null, 'error' => 'Identifiants invalides.', 'email' => $email];
        }

        if (($user['status'] ?? 'active') === 'suspended') {
            Audit::log('auth.login.suspended', 'users', (int) $user['id'], [], ['email' => $email]);

            return ['user' => null, 'error' => 'Ce compte est suspendu. Contactez SeneBridge.', 'email' => $email];
        }

        // Rehash si l'algorithme a évolué.
        if (Hasher::needsRehash((string) $user['password_hash'])) {
            $this->users->updatePassword((int) $user['id'], Hasher::make($password));
        }

        $this->users->touchLastLogin((int) $user['id']);

        App::setUser($user);

        Audit::log('auth.login', 'users', (int) $user['id'], [], ['email' => $email]);

        return ['user' => $user, 'error' => null, 'email' => $email];
    }

    /**
     * Déconnexion complète et sécurisée.
     */
    public function logout(Request $request): void
    {
        $userId = App::id();

        Audit::log('auth.logout', 'users', $userId);

        $name = (string) config('security.session.name', 'senebridge_session');
        $session = App::session();
        unset($session['auth.user_id'], $session['auth.login_at'], $session['_flash'], $session['_old']);

        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        session_write_close();

        if (isset($_COOKIE[$name])) {
            setcookie($name, '', time() - 42000, '/');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            session_regenerate_id(true);
        }
    }
}