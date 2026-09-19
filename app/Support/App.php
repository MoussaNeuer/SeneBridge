<?php

declare(strict_types=1);

namespace App\Support;

use ErrorException;
use Throwable;
use Symfony\Component\Dotenv\Exception\PathException;

/**
 * Cœur applicatif : environnement, configuration, sessions, erreurs, cache requête.
 */
final class App
{
    /** @var array<string, mixed>|null Cache de l'utilisateur courant. */
    private static ?array $cachedUser = null;
    private static ?Request $cachedRequest = null;

    /** Identifiant du token API utilisé pour la requête courante. */
    private static ?int $apiTokenId = null;
    private static bool $booted = false;

    public static function bootstrap(): void
    {
        if (self::$booted) {
            return;
        }

        // 1. Environnement
        Env::load(base_path('.env'));

        // 2. Configuration
        Config::load(config_path());

        // 3. Fuseau horaire et locale
        date_default_timezone_set((string) config('app.timezone', 'Africa/Dakar'));
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');

        // 4. Sessions sécurisées
        $session = config('security.session', []);
        session_name((string) $session['name']);
        session_set_cookie_params([
            'lifetime' => (int) $session['lifetime_minutes'] * 60,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) $session['cookie_secure'],
            'httponly' => (bool) $session['cookie_httponly'],
            'samesite' => (string) $session['cookie_samesite'],
        ]);
        ini_set('session.gc_maxlifetime', (string) ((int) $session['lifetime_minutes'] * 60));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 5. Gestion des erreurs
        self::registerErrorHandling();

        // 6. Journalisation (init)
        Log::instance();

        self::$booted = true;
    }

    /**
     * @return array<string, mixed> Référence à la session courante.
     */
    public static function &session(): array
    {
        self::bootstrap();

        return $_SESSION;
    }

    public static function request(): Request
    {
        if (self::$cachedRequest === null) {
            self::$cachedRequest = new Request();
        }

        return self::$cachedRequest;
    }

    /**
     * Réinitialise l'état par requête (cache utilisateur, requête, token API,
     * routes). Indispensable sous le serveur intégré PHP où les statiques
     * persistent entre requêtes.
     */
    public static function resetRequestState(): void
    {
        self::$cachedUser = null;
        self::$cachedRequest = null;
        self::$apiTokenId = null;
        Router::reset();
    }

    /**
     * Mémorise le token API qui authentifie la requête courante.
     */
    public static function setApiTokenId(int $tokenId): void
    {
        self::$apiTokenId = $tokenId;
    }

    public static function apiTokenId(): ?int
    {
        return self::$apiTokenId;
    }

    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Utilisateur authentifié (depuis la base) ou null.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $session = self::session();
        $userId = $session['auth.user_id'] ?? null;

        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            return null;
        }

        $user = Database::first('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $userId]);

        if ($user === null || ($user['status'] ?? 'active') === 'suspended') {
            self::flushAuth();

            return null;
        }

        // Version de session : si elle a changé (mot de passe modifié ailleurs /
        // actions admin), la session courante est invalidée.
        $storedVersion = (int) self::getSession('auth.session_version', 0);
        $currentVersion = (int) ($user['session_version'] ?? 0);

        if ($storedVersion !== $currentVersion) {
            self::flushAuth();

            return null;
        }

        self::$cachedUser = $user;

        return $user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user !== null ? (int) $user['id'] : null;
    }

    public static function setUser(array $user): void
    {
        self::$cachedUser = $user;
        self::setSession('auth.user_id', (int) $user['id']);
        self::setSession('auth.login_at', date('Y-m-d H:i:s'));
        self::setSession('auth.session_version', (int) ($user['session_version'] ?? 0));
        session_regenerate_id(true);
    }

    /**
     * Injecte l'utilisateur pour la requête courante sans toucher à la session
     * (utilisé par l'API Bearer Token).
     */
    public static function withApiUser(array $user): void
    {
        self::$cachedUser = $user;
    }

    public static function flushAuth(): void
    {
        self::$cachedUser = null;
        $session =& self::session();
        unset(
            $session['auth.user_id'],
            $session['auth.login_at'],
            $session['_flash'],
            $session['_old'],
        );
    }

    /**
     * Message flash (affiché une seule fois).
     */
    public static function flash(string $type, string $message): void
    {
        self::setSession('_flash', array_merge((array) self::getSession('_flash', []), [$type => $message]));
    }

    /**
     * @return array<string, string>
     */
    public static function flashes(): array
    {
        $flashes = (array) self::getSession('_flash', []);
        self::clearSession('_flash');

        return $flashes;
    }

    /**
     * Données de formulaire précédentes (re-fill après erreur).
     */
    public static function remember(array $data): void
    {
        self::setSession('_old', $data);
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return self::getSession('_old', [])[$key] ?? $default;
    }

    public static function clearOld(): void
    {
        self::clearSession('_old');
    }

    /**
     * Lecture d'une clé de session sécurisée (accès dédié pour éviter les
     * copies par valeur qui perdaient les écritures).
     */
    public static function getSession(string $key, mixed $default = null): mixed
    {
        return self::session()[$key] ?? $default;
    }

    public static function setSession(string $key, mixed $value): void
    {
        self::session()[$key] = $value;
    }

    public static function clearSession(string $key): void
    {
        $session =& self::session();
        unset($session[$key]);
    }

    public static function appKey(): string
    {
        return (string) config('app.key', '');
    }

    /**
     * Raccourci d'autorisation (RBAC) sur l'utilisateur courant.
     */
    public static function can(string $permission): bool
    {
        return Gate::allows($permission);
    }

    private static function registerErrorHandling(): void
    {
        if (self::isCli()) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', config('app.debug', false) ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (Throwable $e): void {
            Log::error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $isApi = str_starts_with((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Erreur interne du serveur.',
                ], 500)->send();

                return;
            }

            if (config('app.debug', false)) {
                Response::view('errors/error', [
                    'exception' => $e,
                    'message' => $e->getMessage(),
                ], 500)->send();

                return;
            }

            Response::view('errors/error', [], 500)->send();
        });
    }
}