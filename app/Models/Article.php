<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article d'actualité publié sur le site public.
 *
 * @method static array|null findByPublicId(string $publicId)
 */
final class Article extends Model
{
    protected static string $table = 'articles';

    protected static array $fillable = [
        'public_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_url',
        'status',
        'author_id',
        'published_at',
    ];

    public const STATUSES = ['brouillon', 'publie'];

    public static function findBySlug(string $slug): ?array
    {
        return self::firstWhere(['slug' => $slug]);
    }
}