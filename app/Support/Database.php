<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Connexion PDO unique à la base de données (requêtes préparées uniquement).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $conn = config('database.connections.' . config('database.default', 'mysql'), []);
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $conn['host'] ?? '127.0.0.1',
            $conn['port'] ?? '3306',
            $conn['database'] ?? 'senebridge',
            $conn['charset'] ?? 'utf8mb4'
        );

        try {
            $pdo = new PDO($dsn, $conn['username'] ?? 'root', $conn['password'] ?? '', $conn['options'] ?? []);
        } catch (PDOException $e) {
            Log::critical('Connexion à la base de données impossible', [
                'exception' => $e->getMessage(),
            ]);

            throw new RuntimeException('La base de données est indisponible.', 0, $e);
        }

        // Les transactions et la cohérence reposent sur un moteur InnoDB strict.
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");

        return self::$pdo = $pdo;
    }

    /**
     * Exécute une requête préparée et retourne ses lignes.
     *
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        $stmt->closeCursor();

        return $rows;
    }

    /**
     * Exécute une requête préparée et retourne la première ligne (ou null).
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function first(string $sql, array $params = []): ?array
    {
        $rows = self::select($sql, $params);

        return $rows[0] ?? null;
    }

    /**
     * Exécute une requête préparée et retourne une seule colonne scalaire.
     *
     * @param array<int|string, mixed> $params
     * @return mixed
     */
    public static function scalar(string $sql, array $params = [])
    {
        $row = self::first($sql, $params);

        if ($row === null) {
            return null;
        }

        return reset($row);
    }

    /**
     * Exécute une requête de modification préparée, retourne le nombre de lignes affectées.
     *
     * @param array<int|string, mixed> $params
     */
    public static function statement(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Exécute une requête préparée qui insère et retourne le dernier identifiant.
     *
     * @param array<int|string, mixed> $params
     */
    public static function insert(string $sql, array $params = []): int
    {
        $conn = self::connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return (int) $conn->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $conn = self::connection();

        if ($conn->inTransaction()) {
            return $callback();
        }

        $conn->beginTransaction();

        try {
            $result = $callback();
            $conn->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            throw $e;
        }
    }
}