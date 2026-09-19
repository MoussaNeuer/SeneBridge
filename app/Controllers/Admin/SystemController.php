<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Support\Response;

final class SystemController extends Controller
{
    public function index(): Response
    {
        $info = [
            'application' => [
                ['Nom', config('app.name')],
                ['Environnement', config('app.env')],
                ['Débogage', config('app.debug') ? 'Activé' : 'Désactivé'],
                ['URL', config('app.url')],
                ['Fuseau horaire', date_default_timezone_get()],
            ],
            'runtime' => [
                ['Version PHP', PHP_VERSION],
                ['SAPI', PHP_SAPI],
                ['Système', PHP_OS . ' ' . php_uname('r')],
                ['Serveur', $_SERVER['SERVER_SOFTWARE'] ?? 'n/a'],
                ['Mémoire max', ini_get('memory_limit')],
                ['Upload max', ini_get('upload_max_filesize')],
                ['Temps d\'exécution', ini_get('max_execution_time') . ' s'],
            ],
            'base_de_donnees' => [
                ['Pilote', config('database.driver')],
                ['Hôte', config('database.host')],
                ['Base', config('database.database')],
                ['Système MySQL', $this->mysqlVersion()],
                ['Jeu de caractères', 'utf8mb4'],
            ],
            'extensions' => array_map(
                static fn (string $ext): array => [$ext, extension_loaded($ext) ? 'OK' : 'Manquante'],
                ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'fileinfo', 'gd', 'curl']
            ),
            'securite' => [
                ['Session', config('security.session', []) ? 'Configurée' : 'Actif'],
                ['Temps de session', ini_get('session.gc_maxlifetime') . ' s'],
                ['Protocole forcé', (bool) config('security.https_only', false) ? 'HTTPS uniquement' : 'HTTPS non imposé'],
            ],
        ];

        if (is_dir(storage_path('backups'))) {
            $files = glob(rtrim(storage_path('backups'), '/\\') . DIRECTORY_SEPARATOR . '*.sql');
            $info['espace'] = [
                ['Dernière sauvegarde', $files !== false && $files !== [] ? basename(end($files)) . ' (' . date('d/m/Y H:i', (int) filemtime(end($files))) . ')' : 'Aucune'],
                ['Espace libre', $this->humanBytes(disk_free_space(storage_path()) ?: 0)],
            ];
        }

        return Response::view('admin/system/index', [
            'user' => \App\Support\App::user(),
            'sections' => $info,
        ]);
    }

    private function mysqlVersion(): string
    {
        try {
            $db = \App\Support\Database::connection();

            return (string) $db->query('SELECT VERSION()')->fetchColumn();
        } catch (\Throwable $e) {
            return 'n/a';
        }
    }

    private function humanBytes(float $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];

        foreach ($units as $unit) {
            if ($bytes < 1024) {
                return round($bytes) . ' ' . $unit;
            }

            $bytes /= 1024;
        }

        return round($bytes) . ' Po';
    }
}