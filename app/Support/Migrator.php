<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * Applique les migrations SQL stockées dans database/migrations.
 * Les fichiers appliqués sont tracés dans la table `migrations`.
 */
final class Migrator
{
    public function run(): array
    {
        $pdo = Database::connection();
        $this->ensureTable($pdo);

        $applied = array_column(
            Database::select('SELECT name FROM migrations'),
            'name'
        );

        $files = glob(database_path('migrations/*.sql')) ?: [];
        sort($files);

        $count = 0;

        foreach ($files as $file) {
            $name = basename($file);
            $hash = hash_file('sha256', $file);

            if (in_array($name, $applied, true)) {
                continue;
            }

            $existing = Database::first('SELECT id FROM migrations WHERE name = ?', [$name]);
            $alreadyApplied = $existing !== null && hash_equals((string) $existing['hash'] ?? '', $hash);

            if ($existing !== null && $existing['hash'] !== $hash) {
                throw new \RuntimeException(sprintf(
                    'Migration modifiée après application : %s. Créez une nouvelle migration.',
                    $name
                ));
            }

            if ($alreadyApplied) {
                continue;
            }

            $sql = (string) file_get_contents($file);
            $statements = $this->splitStatements($sql);

            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }

            Database::statement('DELETE FROM migrations WHERE name = ?', [$name]);
            Database::insert(
                'INSERT INTO migrations (name, hash, applied_at) VALUES (?, ?, NOW())',
                [$name, $hash]
            );

            $count++;
        }

        return ['applied' => $count, 'files' => count($files)];
    }

    public function applied(): array
    {
        return Database::select('SELECT name, applied_at FROM migrations ORDER BY applied_at');
    }

    private function ensureTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                hash CHAR(64) NOT NULL COLLATE ascii_bin,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY migrations_name_unique (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Découpe un script SQL en instructions individuelles, en respectant
     * les guillemets simples/doubles, les backticks et les commentaires.
     *
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $quote = null;
        $escape = false;
        $lineComment = false;
        $blockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($lineComment) {
                $buffer .= $char;
                if ($char === "\n") {
                    $lineComment = false;
                }
                continue;
            }

            if ($blockComment) {
                $buffer .= $char;
                if ($char === '*' && $next === '/') {
                    $buffer .= $next;
                    $i++;
                    $blockComment = false;
                }
                continue;
            }

            if ($quote !== null) {
                $buffer .= $char;

                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($char === '\\' && $quote !== '`') {
                    $escape = true;
                    continue;
                }

                if ($char === $quote) {
                    // Doublage d'un quote (ex. '' en MySQL) → pas une fin de chaîne.
                    if ($next === $quote) {
                        $buffer .= $next;
                        $i++;
                        continue;
                    }
                    $quote = null;
                }

                continue;
            }

            if ($char === '-' && $next === '-') {
                $buffer .= $char;
                $lineComment = true;
                continue;
            }

            if ($char === '#') {
                $buffer .= $char;
                $lineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $buffer .= $char;
                $blockComment = true;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
}