<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Réponse HTTP : vue, JSON, redirection, URL.
 */
final class Response
{
    public const TYPE_HTML = 'html';
    public const TYPE_JSON = 'json';
    public const TYPE_REDIRECT = 'redirect';
    public const TYPE_TEXT = 'text';
    public const TYPE_FILE = 'file';

    public string $type = self::TYPE_HTML;
    public int $status = 200;
    public string $content = '';
    /** @var array<string, string> */
    public array $headers = [];
    public string $url = '';

    private function __construct()
    {
    }

    public static function view(string $template, array $data = [], int $status = 200): self
    {
        $response = new self();
        $response->type = self::TYPE_HTML;
        $response->status = $status;
        $response->content = View::render($template, $data);

        return $response;
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $response = new self();
        $response->type = self::TYPE_JSON;
        $response->status = $status;

        // Format JSON standardisé recommandé par le cahier des charges.
        if (is_array($data) && !isset($data['success'])) {
            $data = ['success' => $status >= 200 && $status < 300, 'data' => $data, 'message' => null, 'errors' => []];
        }

        $response->content = (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);

        return $response;
    }

    public static function error(string $message, int $status = 422, array $errors = []): self
    {
        return self::json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self();
        $response->type = self::TYPE_REDIRECT;
        $response->status = $status;
        $response->url = $url;

        return $response;
    }

    public static function redirectToLogin(): self
    {
        return self::redirect(app_url('login'));
    }

    public static function redirectBack(): self
    {
        $referer = App::request()->header('Referer');

        return self::redirect(is_string($referer) && $referer !== '' ? $referer : app_url('/'));
    }

    public static function text(string $content, int $status = 200): self
    {
        $response = new self();
        $response->type = self::TYPE_TEXT;
        $response->status = $status;
        $response->content = $content;

        return $response;
    }

    /**
     * Diffusion d'un fichier privé (streaming depuis storage/ — jamais de chemin client).
     */
    public static function file(string $absolutePath, string $downloadName, string $mimeType, bool $download = true): self
    {
        $response = new self();
        $response->type = self::TYPE_FILE;
        $response->status = 200;
        $response->content = $absolutePath;
        $response->headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) filesize($absolutePath),
            'Content-Disposition' => $download
                ? 'attachment; filename*=UTF-8\'\'' . rawurlencode($downloadName)
                : 'inline; filename*=UTF-8\'\'' . rawurlencode($downloadName),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        return $response;
    }

    public static function notFound(string $message = 'Ressource introuvable.'): self
    {
        return self::view('errors/404', ['message' => $message], 404);
    }

    public static function forbidden(string $message = 'Accès non autorisé.'): self
    {
        return self::view('errors/403', ['message' => $message], 403);
    }

    public function send(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->status);

        $this->applySecurityHeaders();

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($this->type === self::TYPE_REDIRECT) {
            header('Location: ' . $this->url);

            return;
        }

        if ($this->type === self::TYPE_FILE) {
            $this->streamFile((string) $this->content);

            return;
        }

        echo $this->content;
    }

    /**
     * Lit le fichier par blocs pour éviter de charger de gros volumes en mémoire.
     */
    private function streamFile(string $absolutePath): void
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            http_response_code(404);

            return;
        }

        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            http_response_code(500);

            return;
        }

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    break;
                }
                echo $chunk;
                flush();
            }
        } finally {
            fclose($handle);
        }

        exit(0);
    }

    /**
     * Headers de sécurité appliqués globalement (TIERS : nosniff, CSP…).
     * En préproduction, le CSP peut être durci selon les besoins fonctionnels.
     */
    private function applySecurityHeaders(): void
    {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

            // HSTS : uniquement hors debug (un header strict sur localhost casserait le dev HTTP).
            if (!config('app.debug', false)) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }

            if ($this->type === self::TYPE_HTML) {
                header('X-Frame-Options: DENY');
                header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'; connect-src 'self'");
            }
        }
    }
}