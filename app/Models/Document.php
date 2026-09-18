<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Fichier document lié à un dossier ou à un bien.
 */
final class Document extends Model
{
    protected static string $table = 'documents';

    protected static array $fillable = [
        'public_id', 'project_id', 'property_id', 'uploader_id', 'category',
        'internal_name', 'original_name', 'mime_type', 'size', 'version', 'visibility', 'status',
    ];

    public const CATEGORIES = ['contrat', 'rapport', 'justificatif', 'recu', 'facture', 'autre'];
    public const VISIBILITIES = ['client', 'private', 'admin'];
    public const STATUSES = ['brouillon', 'final', 'archive'];
}