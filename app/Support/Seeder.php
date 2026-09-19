<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\UserRepository;

/**
 * Peuplement initial : rôles, permissions, affectations et compte administrateur.
 * Idempotent (peut être relancé sans risque).
 */
final class Seeder
{
    /** @var array<int, array{name:string,label:string,description:string}> */
    private array $roles = [
        ['name' => 'admin', 'label' => 'Administrateur', 'description' => 'Administration globale'],
        ['name' => 'manager', 'label' => 'Gestionnaire', 'description' => 'Clients et dossiers selon périmètre autorisé'],
        ['name' => 'counselor', 'label' => 'Conseiller', 'description' => 'Clients et dossiers attribués'],
        ['name' => 'accounting', 'label' => 'Comptabilité', 'description' => 'Factures et paiements selon autorisations'],
        ['name' => 'client', 'label' => 'Client', 'description' => 'Son compte et ses contenus autorisés'],
    ];

    /** @var array<int, array{name:string,label:string}> */
    private array $permissions = [
        ['name' => 'users.view', 'label' => 'Voir les utilisateurs'],
        ['name' => 'users.manage', 'label' => 'Gérer les utilisateurs'],
        ['name' => 'users.assign_counselor', 'label' => 'Affecter un conseiller'],
        ['name' => 'roles.view', 'label' => 'Voir les rôles et permissions'],
        ['name' => 'roles.manage', 'label' => 'Gérer les rôles et permissions'],
        ['name' => 'projects.view', 'label' => 'Voir les projets'],
        ['name' => 'projects.create', 'label' => 'Créer les projets'],
        ['name' => 'projects.update', 'label' => 'Mettre à jour les projets'],
        ['name' => 'projects.close', 'label' => 'Clôturer les projets'],
        ['name' => 'projects.archive', 'label' => 'Archiver les projets'],
        ['name' => 'properties.view', 'label' => 'Voir les biens'],
        ['name' => 'properties.manage', 'label' => 'Gérer les biens'],
        ['name' => 'steps.view', 'label' => 'Voir les étapes'],
        ['name' => 'steps.update', 'label' => 'Mettre à jour les étapes'],
        ['name' => 'documents.view', 'label' => 'Voir les documents'],
        ['name' => 'documents.upload', 'label' => 'Téléverser des documents'],
        ['name' => 'documents.download', 'label' => 'Télécharger des documents'],
        ['name' => 'documents.delete', 'label' => 'Supprimer des documents'],
        ['name' => 'media.view', 'label' => 'Voir les médias'],
        ['name' => 'media.upload', 'label' => 'Téléverser des médias'],
        ['name' => 'media.delete', 'label' => 'Supprimer des médias'],
        ['name' => 'invoices.view', 'label' => 'Voir les factures'],
        ['name' => 'invoices.manage', 'label' => 'Gérer les factures'],
        ['name' => 'payments.view', 'label' => 'Voir les paiements'],
        ['name' => 'payments.record', 'label' => 'Enregistrer un paiement'],
        ['name' => 'payments.validate', 'label' => 'Valider un paiement'],
        ['name' => 'messages.view', 'label' => 'Voir les messages'],
        ['name' => 'messages.send', 'label' => 'Envoyer des messages'],
        ['name' => 'appointments.view', 'label' => 'Voir les rendez-vous'],
        ['name' => 'appointments.manage', 'label' => 'Gérer les rendez-vous'],
        ['name' => 'notifications.view', 'label' => 'Voir les notifications'],
        ['name' => 'audit.view', 'label' => 'Consulter le journal d\'audit'],
        ['name' => 'settings.manage', 'label' => 'Gérer les paramètres'],
        ['name' => 'requests.view', 'label' => 'Voir les demandes de projet'],
        ['name' => 'requests.manage', 'label' => 'Gérer les demandes de projet'],
        ['name' => 'contacts.view', 'label' => 'Voir les messages de contact'],
        ['name' => 'articles.manage', 'label' => 'Gérer les actualités'],
        ['name' => 'admin.access', 'label' => 'Accéder au back-office'],
    ];

    /**
     * @return array{roles:int, permissions:int, mappings:int, admin:?string}
     */
    public function run(): array
    {
        $roleIds = [];
        $rolesCount = 0;

        foreach ($this->roles as $role) {
            $existing = Role::findByName($role['name']);

            if ($existing === null) {
                $created = Role::create([
                    'name' => $role['name'],
                    'label' => $role['label'],
                    'description' => $role['description'],
                    'is_system' => 1,
                ]);
                $roleIds[$role['name']] = (int) $created['id'];
                $rolesCount++;
            } else {
                $roleIds[$role['name']] = (int) $existing['id'];
                Role::update((int) $existing['id'], ['label' => $role['label'], 'description' => $role['description']]);
            }
        }

        $permissionIds = [];
        $permissionsCount = 0;

        foreach ($this->permissions as $permission) {
            $existing = Permission::findByName($permission['name']);

            if ($existing === null) {
                $created = Permission::create([
                    'name' => $permission['name'],
                    'label' => $permission['label'],
                ]);
                $permissionIds[$permission['name']] = (int) $created['id'];
                $permissionsCount++;
            } else {
                $permissionIds[$permission['name']] = (int) $existing['id'];
            }
        }

        // Affectations (moindre privilège, base MVP).
        $mapping = $this->baseMapping($permissionIds);

        // On partage les colonnes pour l'upsert groupé.
        Database::statement('DELETE FROM role_permissions');
        $mappingsCount = 0;

        foreach ($mapping as $roleName => $perms) {
            $roleId = $roleIds[$roleName] ?? null;
            if ($roleId === null) {
                continue;
            }

            foreach ($perms as $permissionId) {
                Database::insert(
                    'INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                    [$roleId, $permissionId]
                );
                $mappingsCount++;
            }
        }

        // Compte administrateur initial.
        $admin = $this->ensureAdminUser($roleIds[Role::ADMIN]);

        // Articles d'actualité (démo, idempotent par slug).
        $articlesCount = $this->ensureSampleArticles($admin);

        return [
            'roles' => $rolesCount,
            'permissions' => $permissionsCount,
            'mappings' => $mappingsCount,
            'admin' => $admin,
            'articles' => $articlesCount,
        ];
    }

    /**
     * @param array<string, int> $ids
     * @return array<string, array<int, int>>
     */
    private function baseMapping(array $ids): array
    {
        $all = array_values($ids);

        return [
            'admin' => $all,
            'manager' => $this->ids($ids, [
                'users.view', 'users.assign_counselor', 'admin.access',
                'projects.view', 'projects.create', 'projects.update', 'projects.close', 'projects.archive',
                'properties.view', 'properties.manage',
                'steps.view', 'steps.update',
                'documents.view', 'documents.upload', 'documents.download', 'documents.delete',
                'media.view', 'media.upload', 'media.delete',
                'invoices.view', 'invoices.manage',
                'payments.view', 'payments.record', 'payments.validate',
                'messages.view', 'messages.send',
                'appointments.view', 'appointments.manage',
                'notifications.view', 'audit.view',
                'requests.view', 'requests.manage', 'contacts.view',
            ]),
            'counselor' => $this->ids($ids, [
                'admin.access',
                'projects.view', 'steps.view', 'steps.update',
                'documents.view', 'documents.upload', 'documents.download',
                'media.view', 'media.upload',
                'messages.view', 'messages.send',
                'appointments.view', 'appointments.manage',
                'notifications.view',
            ]),
            'accounting' => $this->ids($ids, [
                'admin.access',
                'invoices.view',
                'payments.view', 'payments.record', 'payments.validate',
            ]),
            'client' => $this->ids($ids, [
                // L'espace client (tableau de bord, projets, factures, messagerie,
                // rendez-vous) est protégé par authentification + vérifications de
                // propriété (IDOR). Le rôle client ne détient aucune permission du
                // back-office : il ne doit pas pouvoir atteindre les routes /admin/*.
                'notifications.view',
            ]),
        ];
    }

    /**
     * @param array<string, int> $ids
     * @param array<int, string> $names
     * @return array<int, int>
     */
    private function ids(array $ids, array $names): array
    {
        $result = [];

        foreach ($names as $name) {
            if (isset($ids[$name])) {
                $result[] = $ids[$name];
            }
        }

        return $result;
    }

    private function ensureSampleArticles(?string $adminPassword = null): int
    {
        $articles = [
            [
                'title' => 'SeneBridge, le pont entre la diaspora et le Sénégal',
                'slug' => 'senebridge-le-pont-entre-la-diaspora-et-le-senegal',
                'excerpt' => 'Notre plateforme centralise vos projets immobiliers, import/export et d\'investissement, avec un suivi transparent en temps réel.',
                'body' => "La diaspora sénégalaise joue un rôle central dans l'économie nationale. SeneBridge simplifie la gestion de vos projets au pays : biens immobiliers, dossiers d'import/export, investissements, conciergerie.\n\nChaque dossier dispose d'une timeline claire, de documents sécurisés et d'un conseiller dédié.\n\nCréez votre compte et démarrez votre premier projet en quelques minutes.",
            ],
            [
                'title' => 'Acheter un bien immobilier à distance : nos conseils',
                'slug' => 'acheter-un-bien-immobilier-a-distance-nos-conseils',
                'excerpt' => 'Recherche, vérification, visite et transaction : les étapes clés pour sécuriser un achat immobilier depuis l\'étranger.',
                'body' => "Acquérir un bien depuis la diaspora demande rigueur et accompagnement.\n\n1. Vérifiez le titre de propriété et la situation juridique du terrain.\n2. Faites réaliser une visite indépendante.\n3. Prévoyez les frais d'enregistrement et de fiscalité.\n\nSeneBridge coordonne chaque étape avec des professionnels locaux.",
            ],
            [
                'title' => 'Investir au Sénégal : opportunités et accompagnement',
                'slug' => 'investir-au-senegal-opportunites-et-accompagnement',
                'excerpt' => 'Immobilier, agriculture, énergie : les secteurs porteurs et la façon de s\'y engager en toute confiance.',
                'body' => "Le Sénégal offre de nombreuses opportunités d'investissement.\n\nNotre équipe vous accompagne de l'étude de faisabilité jusqu'à la mise en place, avec une transparence totale sur les engagements financiers.\n\nContactez-nous pour un premier rendez-vous.",
            ],
        ];

        $authorId = null;
        $admin = Database::first("SELECT id FROM users WHERE email = 'admin@senebridge.sn' LIMIT 1");
        if ($admin !== null) {
            $authorId = (int) $admin['id'];
        }

        $created = 0;

        foreach ($articles as $article) {
            if (\App\Models\Article::findBySlug($article['slug']) !== null) {
                continue;
            }

            \App\Models\Article::create([
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'body' => $article['body'],
                'status' => 'publie',
                'author_id' => $authorId,
                'published_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }

        return $created;
    }

    private function ensureAdminUser(int $adminRoleId): ?string
    {
        $users = new UserRepository();

        if ($users->findByEmail('admin@senebridge.sn') !== null) {
            return null;
        }

        $password = (string) env('SEED_ADMIN_PASSWORD', '');

        if ($password === '') {
            $password = bin2hex(random_bytes(8));
        }

        $created = Database::transaction(function () use ($users, $adminRoleId, $password) {
            return $users->createClient([
                'first_name' => 'SeneBridge',
                'last_name' => 'Administrateur',
                'email' => 'admin@senebridge.sn',
                'phone' => null,
                'locality' => 'Dakar',
            ], Hasher::make($password));
        });

        // Le rôle par défaut (client) est remplacé par admin.
        Database::statement('UPDATE users SET role_id = ? WHERE id = ?', [$adminRoleId, $created['id']]);
        Database::statement(
            "UPDATE users SET email_verified_at = NOW() WHERE id = ?",
            [(int) $created['id']]
        );

        return $password;
    }
}