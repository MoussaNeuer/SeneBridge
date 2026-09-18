<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Support\Database;

/**
 * Modèle de base : accès données préparés, timestamps et ULID public.
 *
 * Retourne toujours des tableaux associatifs (pas d'ORM lourd).
 */
abstract class Model
{
    use HasPublicId;

    protected static string $table;
    /** @var array<int, string> Colonnes autorisées à l'écriture. */
    protected static array $fillable = [];
    /** @var array<string, array<int, string>> */
    private static array $columnCache = [];

    public static function table(): string
    {
        if (!isset(static::$table)) {
            $class = basename(str_replace('\\', '/', static::class));
            $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $class));

            return $snake . 's';
        }

        return static::$table;
    }

    /**
     * @return array<int, string>
     */
    public static function columns(): array
    {
        $table = static::table();

        if (isset(self::$columnCache[$table])) {
            return self::$columnCache[$table];
        }

        $rows = Database::select('SHOW COLUMNS FROM `' . $table . '`');
        $columns = array_map(static fn (array $row): string => (string) $row['Field'], $rows);

        return self::$columnCache[$table] = $columns;
    }

    public static function hasColumn(string $column): bool
    {
        return in_array($column, self::columns(), true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM `' . static::table() . '` WHERE id = ? LIMIT 1', [$id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByPublicId(string $publicId): ?array
    {
        if (!self::hasColumn('public_id')) {
            return null;
        }

        return Database::first('SELECT * FROM `' . static::table() . '` WHERE public_id = ? LIMIT 1', [$publicId]);
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>|null
     */
    public static function firstWhere(array $conditions, array $orderBy = []): ?array
    {
        $params = [];
        $sql = 'SELECT * FROM `' . static::table() . '` WHERE ' . self::buildWhere($conditions, $params);

        return Database::first($sql . self::buildOrderBy($orderBy) . ' LIMIT 1', $params);
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<int, array<string, mixed>>
     */
    public static function where(array $conditions, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $params = [];
        $sql = 'SELECT * FROM `' . static::table() . '`';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . self::buildWhere($conditions, $params);
        }

        $sql .= self::buildOrderBy($orderBy);

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ($offset !== null ? ' OFFSET ' . (int) $offset : '');
        }

        return Database::select($sql, $params);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public static function count(array $conditions = []): int
    {
        $where = '';
        $params = [];

        if ($conditions !== []) {
            $where = ' WHERE ' . self::buildWhere($conditions, $params);
        }

        return (int) Database::scalar('SELECT COUNT(*) FROM `' . static::table() . '`' . $where, $params);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public static function create(array $data): ?array
    {
        $data = self::sanitizeFillable($data);
        $table = static::table();

        if (self::hasColumn('public_id') && !isset($data['public_id'])) {
            $data['public_id'] = static::newPublicId();
        }

        if (self::hasColumn('created_at')) {
            $data['created_at'] = self::now();
        }

        if (self::hasColumn('updated_at')) {
            $data['updated_at'] = self::now();
        }

        if ($data === []) {
            return null;
        }

        $columns = implode(', ', array_map(static fn (string $c): string => '`' . $c . '`', array_keys($data)));
        $placeholders = rtrim(str_repeat('?, ', count($data)), ', ');

        $id = Database::insert(
            sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table, $columns, $placeholders),
            array_values($data)
        );

        return self::find((int) $id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        $data = self::sanitizeFillable($data);

        if ($data === []) {
            return false;
        }

        if (self::hasColumn('updated_at')) {
            $data['updated_at'] = self::now();
        }

        $assignments = implode(', ', array_map(
            static fn (string $col): string => '`' . $col . '` = ?',
            array_keys($data)
        ));

        return Database::statement(
            sprintf('UPDATE `%s` SET %s WHERE id = ?', static::table(), $assignments),
            [...array_values($data), $id]
        ) > 0;
    }

    public static function delete(int $id): bool
    {
        return Database::statement('DELETE FROM `' . static::table() . '` WHERE id = ?', [$id]) > 0;
    }

    /**
     * Pagination simple (MVP) — retourne [items, total, perPage, page, lastPage].
     *
     * @param array<string, mixed> $conditions
     * @return array{items: array<int, array<string, mixed>>, total: int, per_page: int, page: int, last_page: int}
     */
    public static function paginate(array $conditions, int $perPage = 15, int $page = 1, array $orderBy = [['id', 'DESC']]): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $total = static::count($conditions);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $items = static::where($conditions, $orderBy, $perPage, ($page - 1) * $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'page' => $page,
            'last_page' => $lastPage,
        ];
    }

    /**
     * @param array<string, mixed> $conditions
     */
    private static function buildWhere(array $conditions, array &$params): string
    {
        $segments = [];

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $placeholders = rtrim(str_repeat('?, ', count($value)), ', ');
                $segments[] = '`' . $column . '` IN (' . $placeholders . ')';
                $params = [...$params, ...array_values($value)];
            } else {
                $segments[] = '`' . $column . '` = ?';
                $params[] = $value;
            }
        }

        return implode(' AND ', $segments);
    }

    /**
     * @param array<int, array{0:string,1?:string}> $orderBy
     */
    private static function buildOrderBy(array $orderBy): string
    {
        if ($orderBy === []) {
            return '';
        }

        $segments = [];

        foreach ($orderBy as $order) {
            [$column, $direction] = [$order[0], $order[1] ?? 'ASC'];
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $segments[] = '`' . $column . '` ' . $direction;
        }

        return ' ORDER BY ' . implode(', ', $segments);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function sanitizeFillable(array $data): array
    {
        if (static::$fillable === []) {
            throw new \LogicException(sprintf('%s doit définir $fillable.', static::class));
        }

        return array_intersect_key($data, array_flip(static::$fillable));
    }

    protected static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}