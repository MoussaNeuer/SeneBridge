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

    private function help(): void
    {
        $this->line('SeneBridge — Console de gestion');
        $this->line('');
        $this->line('  key:generate   Génère une nouvelle APP_KEY');
        $this->line('  migrate        Applique les migrations SQL');
        $this->line('  migrate:status Liste des migrations appliquées');
        $this->line('  seed           Rôles, permissions et admin initial');
        $this->line('  route:list     Aide routage');
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