<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Message envoyé via le formulaire de contact du site public.
 *
 * @method static array|null findByPublicId(string $publicId)
 */
final class ContactMessage extends Model
{
    protected static string $table = 'contact_messages';

    protected static array $fillable = [
        'public_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'is_read',
        'read_at',
    ];
}