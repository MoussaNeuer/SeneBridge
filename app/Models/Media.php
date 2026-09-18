<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Média (image/vidéo/document) lié à un dossier ou à un bien.
 */
final class Media extends Model
{
    protected static string $table = 'media';

    protected static array $fillable = [
        'public_id', 'project_id', 'property_id', 'uploader_id', 'type', 'category',
        'internal_name', 'original_name', 'mime_type', 'size', 'width', 'height', 'alt_text', 'visibility',
    ];

    public const TYPES = ['image', 'video', 'document'];
    public const VISIBILITIES = ['client', 'private'];
}