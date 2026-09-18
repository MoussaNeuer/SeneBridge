<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Version d'un document (traçabilité des mises à jour).
 */
final class DocumentVersion extends Model
{
    protected static string $table = 'document_versions';

    protected static array $fillable = [
        'document_id', 'version_number', 'internal_name', 'original_name', 'mime_type', 'size', 'user_id',
    ];
}