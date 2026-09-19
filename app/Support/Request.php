<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Abstraction de la requête HTTP (GET, POST, JSON, fichiers, en-têtes).
 */
final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $headers;
    private array $cookies;
    private array $files;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $this->query = $_GET;
        $this->headers = $this->parseHeaders();
        $this->body = $this->parseBody();
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
    }

    private function parseBody(): array
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $this->isJson()) {
            $raw = file_get_contents('php://input');
            $decoded = $raw !== false ? json_decode($raw, true) : null;

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        $path = $this->path;

        // Sous WAMP/Apache, l'application est servie dans un sous-répertoire
        // (ex. /SeneBridge/public) : on retire la base pour ne router que la
        // partie applicative.
        $base = rtrim((string) parse_url((string) config('app.url', ''), PHP_URL_PATH), '/');

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return $path !== '' ? $path : '/';
    }

    public function url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $this->path;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->header('User-Agent') ?? '';
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type') ?? '';

        return str_contains(strtolower($contentType), 'application/json');
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        if ($accept !== '') {
            return str_contains(strtolower($accept), 'application/json');
        }

        return $this->isAjax();
    }

    /**
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function post(string $key, $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function query(string $key, $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        $all = $this->all();

        return array_intersect_key($all, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Jeton Bearer si présent dans l'en-tête Authorization.
     */
    public function bearerToken(): ?string
    {
        $header = (string) $this->header('Authorization');

        if (preg_match('/^Bearer\s+([A-Za-z0-9\-._~+\/=]+)$/', trim($header), $m) === 1) {
            return $m[1];
        }

        return null;
    }

    public function cookie(string $name, $default = null): mixed
    {
        return $this->cookies[$name] ?? $default;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }
}