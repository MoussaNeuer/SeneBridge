<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Article;
use App\Support\Database;

final class ArticleRepository
{
    /**
     * Articles publiés, les plus récents d'abord.
     *
     * @return array<int, array<string, mixed>>
     */
    public function publishedPublic(int $limit = 6, int $excludeId = 0): array
    {
        $params = [];
        $sql = "SELECT * FROM articles WHERE status = 'publie' AND published_at <= NOW()";

        if ($excludeId > 0) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' ORDER BY published_at DESC LIMIT ?';
        $params[] = $limit;

        return Database::select($sql, $params);
    }

    public function adminPaginated(int $perPage = 20, int $page = 1): array
    {
        return Article::paginate([], $perPage, $page, [['created_at', 'DESC']]);
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return Database::first(
            'SELECT * FROM articles WHERE slug = ? AND status = \'publie\' AND published_at <= NOW() LIMIT 1',
            [$slug]
        );
    }
}