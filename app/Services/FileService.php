<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Response;
use App\Support\Str;

/**
 * Stockage et diffusion des fichiers privés (storage/private).
 *
 * Les fichiers ne sont jamais exposés publiquement : un chemin restreint en
 * base (internal_name) pointe vers un répertoire hors racine web, et une route
 * de téléversement contrôlée (permission + rattachement) les diffuse.
 */
final class FileService
{
    /** @var array<string, string> Extension → MIME */
    public const EXTENSIONS = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'zip' => 'application/zip',
    ];

    public const DIR_DOCUMENTS = 'documents';
    public const DIR_MEDIA = 'media';
    public const DIR_RECEIPTS = 'receipts';

    /**
     * Enregistre un fichier téléversé et retourne ses métadonnées.
     *
     * @return array{ok: bool, error: ?string, internal_name: ?string, original_name: ?string, mime_type: ?string, size: int, ext: ?string}
     */
    public static function store(array $file, string $subdir, int $maxSize, bool $imagesOnly = false, array $allowedMimes = []): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmp = (string) ($file['tmp_name'] ?? '');
        $original = (string) ($file['name'] ?? '');

        if ($error === UPLOAD_ERR_NO_FILE) {
            return self::result(false, 'Aucun fichier sélectionné.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            return self::result(false, 'Le téléversement a échoué.');
        }

        if (!is_uploaded_file($tmp)) {
            return self::result(false, 'Fichier invalide.');
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            return self::result(false, 'Le fichier est vide.');
        }

        if ($size > $maxSize) {
            return self::result(false, sprintf('Le fichier dépasse la taille maximale de %d Mo.', (int) ceil($maxSize / 1048576)));
        }

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if ($ext === '' || !isset(self::EXTENSIONS[$ext])) {
            return self::result(false, 'Type de fichier non autorisé.');
        }

        $mime = self::EXTENSIONS[$ext];

        if ($imagesOnly && !str_starts_with($mime, 'image/')) {
            return self::result(false, 'Seules les images sont acceptées ici.');
        }

        if ($allowedMimes !== [] && !in_array($mime, $allowedMimes, true)) {
            return self::result(false, 'Type MIME du fichier non autorisé.');
        }

        $internalName = date('YmdHis') . '-' . Str::random(12) . '.' . $ext;
        $directory = self::directory($subdir);

        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        if (!move_uploaded_file($tmp, $directory . DIRECTORY_SEPARATOR . $internalName)) {
            return self::result(false, 'Impossible d\'enregistrer le fichier.');
        }

        return self::result(true, null, $internalName, $original, $mime, $size, $ext);
    }

    /**
     * Retourne le chemin absolu d'un fichier stocké (préparé, aucune entrée client).
     */
    public static function absolutePath(string $subdir, string $internalName): string
    {
        return self::directory($subdir) . DIRECTORY_SEPARATOR . basename($internalName);
    }

    /**
     * MIME déduit de l'extension d'un fichier stocké (les MIME réels ne sont pas toujours persistés).
     */
    public static function mimeOf(string $internalName): string
    {
        $ext = strtolower(pathinfo($internalName, PATHINFO_EXTENSION));

        return self::EXTENSIONS[$ext] ?? 'application/octet-stream';
    }

    /**
     * Réponse de diffusion d'un fichier privé.
     */
    public static function download(string $subdir, string $internalName, string $mimeType, string $downloadName, bool $inline = false): Response
    {
        $path = self::absolutePath($subdir, $internalName);

        return Response::file($path, self::safeName($downloadName), $mimeType, !$inline);
    }

    public static function delete(string $subdir, string $internalName): void
    {
        $path = self::absolutePath($subdir, $internalName);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Nom de diffusion sanitisé (aucune injection d'en-tête ni de chemin).
     */
    public static function safeName(string $name): string
    {
        $name = str_replace(["\r", "\n", "\\", "/", "\0"], '', $name);
        $name = trim($name);

        return $name !== '' ? $name : 'fichier';
    }

    private static function directory(string $subdir): string
    {
        return storage_path('private/' . $subdir);
    }

    /**
     * @return array{ok: bool, error: ?string, internal_name: ?string, original_name: ?string, mime_type: ?string, size: int, ext: ?string}
     */
    private static function result(bool $ok, ?string $error, ?string $internalName = null, ?string $originalName = null, ?string $mime = null, int $size = 0, ?string $ext = null): array
    {
        return [
            'ok' => $ok,
            'error' => $error,
            'internal_name' => $internalName,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'size' => $size,
            'ext' => $ext,
        ];
    }
}