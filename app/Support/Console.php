<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Console de gestion (lancement via : php public/index.php <commande>).
 */
final class Console
{
    /** @var array<int, string> */
    private array $argv;

    public function __construct(array $argv)
    {
        $tokens = array_slice($argv, 1);
        $this->argv = array_values(array_filter(
            $tokens,
            static fn (string $a): bool => !str_starts_with($a, '-')
        ));
    }

    public function run(): void
    {
        $command = $this->argv[0] ?? 'help';

        try {
            match ($command) {
                'help', '--help', '-h' => $this->help(),
                'key:generate' => $this->keyGenerate(),
                'migrate' => $this->migrate(),
                'migrate:status' => $this->migrateStatus(),
                'seed' => $this->seed(),
                'route:list' => $this->routeList(),
                'doctor' => $this->doctor(),
                'backup' => $this->backup(),
                default => $this->error(sprintf('Commande inconnue : %s', $command)),
            };
        } catch (\Throwable $e) {
            Log::error('Erreur console', ['command' => $command, 'error' => $e->getMessage()]);
            $this->error($e->getMessage());
        }
    }

    private function keyGenerate(): void
    {
        $envPath = base_path('.env');
        $key = bin2hex(random_bytes(32));

        $content = (string) file_get_contents($envPath);
        $content = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $content) ?? $content;

        if (is_bool($content) || !str_contains($content, 'APP_KEY=')) {
            $content .= "\nAPP_KEY=" . $key . "\n";
        }

        file_put_contents($envPath, $content);

        $this->line('APP_KEY généré et écrit dans .env');
    }

    private function migrate(): void
    {
        $result = (new Migrator())->run();

        $this->line(sprintf('Migrations appliquées : %d (%d fichiers présents)', $result['applied'], $result['files']));
    }

    private function migrateStatus(): void
    {
        $applied = (new Migrator())->applied();

        foreach ($applied as $migration) {
            $this->line(sprintf('✔ %s  (%s)', $migration['name'], $migration['applied_at']));
        }

        $this->line(sprintf('%d migration(s) appliquée(s).', count($applied)));
    }

    private function seed(): void
    {
        $result = (new Seeder())->run();

        $this->line(sprintf(
            'Rôles créés : %d · Permissions créées : %d · Affectations : %d',
            $result['roles'],
            $result['permissions'],
            $result['mappings']
        ));

        if ($result['admin'] !== null) {
            $this->line('Compte administrateur créé : admin@senebridge.sn');
            $this->line('Mot de passe (à changer immédiatement) : ' . $result['admin']);
        } else {
            $this->line('Compte administrateur déjà présent (admin@senebridge.sn).');
        }
    }

    private function routeList(): void
    {
        // Liste minimale : les routes sont connues au runtime statique.
        $this->line('Utilisez le navigateur ou php -S pour le routage HTTP.');
    }

    private function doctor(): void
    {
        $fatalErrors = 0;
        $warnings = 0;

        $out = static function (string $level, string $message): void {
            printf("[%-5s] %s\n", $level, $message);
        };

        $out('INFO', 'SeneBridge — diagnostic');

        $php = PHP_VERSION;
        $extensions = ['pdo_mysql', 'mbstring', 'dom', 'xml', 'json', 'openssl', 'fileinfo'];
        $missing = array_values(array_filter(
            $extensions,
            static fn (string $ext): bool => !extension_loaded($ext)
        ));
        if ($missing === []) {
            $out('OK', sprintf('PHP %s et extensions requises (%s)', $php, implode(', ', $extensions)));
        } else {
            $warnings++;
            $out('WARN', sprintf('PHP %s — extensions manquantes : %s', $php, implode(', ', $missing)));
        }

        $out('INFO', sprintf('Limites upload PHP : upload_max_filesize=%s, post_max_size=%s, memory_limit=%s',
            ini_get('upload_max_filesize'), ini_get('post_max_size'), ini_get('memory_limit')));
        $uploadMax = (int) config('storage.uploads.max_size', 10485760);
        if (self::iniBytes(ini_get('upload_max_filesize')) < $uploadMax) {
            $warnings++;
            $out('WARN', sprintf(
                'upload_max_filesize PHP (%s) inférieure à UPLOAD_MAX_SIZE (%d octets) : adapter php.ini en production.',
                ini_get('upload_max_filesize'), $uploadMax
            ));
        }
        if (self::iniBytes(ini_get('post_max_size')) < $uploadMax) {
            $warnings++;
            $out('WARN', sprintf('post_max_size PHP (%s) inférieure à UPLOAD_MAX_SIZE (%d octets).', ini_get('post_max_size'), $uploadMax));
        }

        $key = (string) config('app.key', '');
        if (strlen($key) === 64) {
            $out('OK', 'APP_KEY définie.');
        } else {
            $fatalErrors++;
            $out('ERROR', 'APP_KEY absente ou invalide — lancer : php public/index.php key:generate');
        }

        if (!file_exists(base_path('.env'))) {
            $warnings++;
            $out('WARN', 'Fichier .env absent.');
        }
        if (config('app.debug', false)) {
            $warnings++;
            $out('WARN', 'APP_DEBUG est true — à désactiver en production.');
        }
        if (config('app.env', 'development') === 'production' && !config('app.trust_proxy', false)) {
            $warnings++;
            $out('WARN', 'APP_ENV=production sans APP_TRUST_PROXY : la détection HTTPS derrière reverse-proxy est désactivée.');
        }

        foreach ([
            'logs' => storage_path('logs'),
            'private' => (string) config('storage.private_path', storage_path('private')),
        ] as $label => $dir) {
            if (!is_dir($dir) || !is_writable($dir)) {
                $warnings++;
                $out('WARN', sprintf('Répertoire %s non inscriptible : %s', $label, $dir));
            } else {
                $out('OK', sprintf('Répertoire %s inscriptible.', $label));
            }
        }

        try {
            $db = Database::connection();
            $dbVersion = (string) $db->query('SELECT VERSION()')->fetchColumn();
            $out('OK', sprintf('Connexion MySQL OK (%s).', $dbVersion));
        } catch (\Throwable $e) {
            $fatalErrors++;
            $out('ERROR', 'Connexion MySQL impossible : ' . $e->getMessage());
        }

        if ($fatalErrors > 0) {
            exit(1);
        }
        if ($warnings > 0) {
            $this->line(sprintf('%d avertissement(s) — corriger avant mise en production.', $warnings));
            exit(0);
        }
    }

    private function backup(): void
    {
        $base = storage_path('backups');
        $stamp = date('Ymd-His');
        $keep = (int) config('backup.keep', 7);

        try {
            $dumpFile = $base . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'senebridge-' . $stamp . '.sql';
            if (!is_dir(dirname($dumpFile))) {
                mkdir(dirname($dumpFile), 0775, true);
            }

            $ok = $this->dumpDatabase($dumpFile);
            if (!$ok) {
                $this->error('Sauvegarde de la base de données échouée.');
                exit(1);
            }
            $this->line('Base de données sauvegardée : ' . $dumpFile);

            $private = (string) config('storage.private_path', storage_path('private'));
            if (is_dir($private)) {
                $filesDir = $base . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $stamp;
                mkdir($filesDir, 0775, true);
                self::copyTree($private, $filesDir);
                $this->line('Fichiers privés (uploads/projets) copiés : ' . $filesDir);
            }

            $dropped = $this->pruneBackups($base, $keep);
            if ($dropped > 0) {
                $this->line(sprintf('Rétention : %d sauvegarde(s) obsolète(s) supprimée(s).', $dropped));
            }

            Log::info('Sauvegarde manuelle terminée', ['dump' => $dumpFile, 'keep' => $keep]);
            $this->line('Sauvegarde terminée.');
        } catch (\Throwable $e) {
            Log::error('Sauvegarde échouée', ['error' => $e->getMessage()]);
            $this->error('Erreur lors de la sauvegarde : ' . $e->getMessage());
            exit(1);
        }
    }

    private function dumpDatabase(string $target): bool
    {
        $bin = $this->resolveMysqldump();
        $db = config('database.connections.mysql', []);
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (string) ($db['port'] ?? '3306');
        $name = (string) ($db['database'] ?? 'senebridge');
        $user = (string) ($db['username'] ?? 'root');
        $password = (string) ($db['password'] ?? '');

        $command = sprintf(
            '%s --no-tablespaces --single-transaction --default-character-set=utf8mb4 '
            . '--host=%s --port=%s --user=%s --result-file=%s %s',
            escapeshellarg($bin),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($target),
            escapeshellarg($name)
        );

        if ($password !== '') {
            putenv('MYSQL_PWD=' . $password);
        }

        $output = [];
        $code = 0;
        try {
            $last = exec($command, $output, $code);
            unset($last);
        } finally {
            if ($password !== '') {
                putenv('MYSQL_PWD');
            }
        }

        if ($code !== 0 || !is_file($target) || filesize($target) === 0) {
            return false;
        }

        return true;
    }

    private function resolveMysqldump(): string
    {
        $bin = (string) config('backup.mysqldump_bin', 'mysqldump');
        if ($bin !== 'mysqldump' && is_file($bin)) {
            return $bin;
        }

        $searches = ['mysqldump.exe', 'mysqldump'];
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
            if ($dir === '') {
                continue;
            }
            foreach ($searches as $candidate) {
                $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $candidate;
                if (is_file($file)) {
                    return $file;
                }
            }
        }

        foreach (['C:/wamp64/bin/mysql', 'C:/wamp/bin/mysql'] as $root) {
            $found = glob($root . '/*/bin/mysqldump.exe');
            if ($found !== false && $found !== []) {
                return $found[0];
            }
        }

        return 'mysqldump';
    }

    private function pruneBackups(string $base, int $keep): int
    {
        $files = glob($base . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'senebridge-*.sql') ?: [];
        sort($files);
        $excess = count($files) - $keep;
        $dropped = 0;

        for ($i = 0; $i < $excess; $i++) {
            if (@unlink($files[$i])) {
                $dropped++;
            }
        }

        return $dropped;
    }

    private static function copyTree(string $src, string $dst): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $item) {
            $relative = substr($item->getPathname(), strlen($src));
            $dest = $dst . $relative;
            if ($item->isDir()) {
                if (!is_dir($dest)) {
                    mkdir($dest, 0775, true);
                }
            } elseif ($item->isFile()) {
                copy($item->getPathname(), $dest);
            }
        }
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;
        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function help(): void
    {
        $this->line('SeneBridge — Console de gestion');
        $this->line('');
        $this->line('  key:generate   Génère une nouvelle APP_KEY');
        $this->line('  migrate        Applique les migrations SQL');
        $this->line('  migrate:status Liste des migrations appliquées');
        $this->line('  seed           Rôles, permissions et admin initial');
        $this->line('  route:list     Aide routage');
        $this->line('  doctor         Diagnostic (PHP, extensions, DB, config)');
        $this->line('  backup         Sauvegarde base de données + fichiers privés');
    }

    private function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    private function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}