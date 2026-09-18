<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectStep;
use App\Models\ProjectStepHistory;
use App\Support\Audit;
use App\Support\Database;
use App\Support\Gate;
use App\Support\Str;

/**
 * Gestion du workflow d'un dossier : étapes, timeline, historique et progrès.
 * Toute modification d'étape est tracée (historique + audit) et notifiée au client.
 */
final class WorkflowService
{
    public const ALLOWED_TRANSITIONS = [
        'a_venir' => ['en_cours', 'bloque'],
        'en_cours' => ['termine', 'bloque', 'a_venir'],
        'bloque' => ['en_cours', 'a_venir'],
        'termine' => ['a_venir', 'en_cours'],
    ];

    /**
     * Workflow par défaut selon le type de dossier (cahier des charges 7.3).
     *
     * @return array<int, array{name:string, description:string}>
     */
    public static function defaultStepsFor(string $projectType): array
    {
        $defaults = [
            'immobilier' => [
                ['name' => 'Demande', 'description' => 'Réception et qualification de la demande.'],
                ['name' => 'Recherche', 'description' => 'Recherche et présélection des biens.'],
                ['name' => 'Sélection', 'description' => 'Choix du bien avec le client.'],
                ['name' => 'Vérification', 'description' => 'Vérification juridique et technique du bien.'],
                ['name' => 'Visite', 'description' => 'Visite du bien sur place ou à distance.'],
                ['name' => 'Validation', 'description' => 'Validation du dossier et des conditions.'],
                ['name' => 'Achat', 'description' => 'Transaction d’achat / signature.'],
                ['name' => 'Finalisation', 'description' => 'Remise des clés et clôture administrative.'],
            ],
            'gestion_projets' => [
                ['name' => 'Cadrage', 'description' => 'Définition du périmètre et des objectifs.'],
                ['name' => 'Étude', 'description' => 'Études techniques, coûts et délais.'],
                ['name' => 'Exécution', 'description' => 'Réalisation des travaux ou livrables.'],
                ['name' => 'Livraison', 'description' => 'Livraison et réception.'],
            ],
            'import_export' => [
                ['name' => 'Commande', 'description' => 'Enregistrement de la commande.'],
                ['name' => 'Préparation', 'description' => 'Préparation et conditionnement.'],
                ['name' => 'Expédition', 'description' => 'Expédition et suivi transit.'],
                ['name' => 'Réception', 'description' => 'Réception et vérification.'],
            ],
            'auto' => [
                ['name' => 'Demande', 'description' => 'Réception de la demande véhicule.'],
                ['name' => 'Recherche', 'description' => 'Recherche du véhicule adapté.'],
                ['name' => 'Importation', 'description' => 'Importation et formalités.'],
                ['name' => 'Livraison', 'description' => 'Livraison du véhicule.'],
            ],
            'conciergerie' => [
                ['name' => 'Prise en charge', 'description' => 'Prise en charge de la demande.'],
                ['name' => 'Planification', 'description' => 'Planification des interventions.'],
                ['name' => 'Exécution', 'description' => 'Réalisation des services.'],
                ['name' => 'Compte rendu', 'description' => 'Compte rendu et facturation.'],
            ],
            'investissement' => [
                ['name' => 'Étude', 'description' => 'Étude de l’opportunité.'],
                ['name' => 'Proposition', 'description' => 'Proposition d’investissement.'],
                ['name' => 'Engagement', 'description' => 'Engagement et versement.'],
                ['name' => 'Suivi', 'description' => 'Suivi et reporting.'],
            ],
        ];

        return $defaults[$projectType] ?? $defaults['gestion_projets'];
    }

    /**
     * Crée les étapes par défaut d'un projet (transaction incluse).
     */
    public static function seedSteps(int $projectId, string $projectType, ?int $responsibleId = null): void
    {
        $position = 0;

        foreach (self::defaultStepsFor($projectType) as $step) {
            ProjectStep::create([
                'project_id' => $projectId,
                'name' => $step['name'],
                'description' => $step['description'],
                'position' => $position++,
                'status' => 'a_venir',
                'responsible_id' => $responsibleId,
            ]);
        }
    }

    /**
     * Progression globale du dossier (0-100).
     */
    public static function progress(array $project): int
    {
        $total = (int) ($project['steps_count'] ?? 0);
        $done = (int) ($project['steps_completed'] ?? 0);

        if ($total <= 0) {
            return 0;
        }

        return (int) round($done * 100 / $total);
    }

    /**
     * Date d'échéance ISO lisible.
     */
    public static function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $ts = strtotime($date);

        return $ts !== false ? date('d/m/Y', $ts) : $date;
    }

    /**
     * Met à jour le statut d'une étape avec historique, audit et notification.
     *
     * @return array{ok: bool, error: ?string}
     */
    public static function updateStep(
        int $stepId,
        array $user,
        string $toStatus,
        ?string $comment = null,
        ?int $responsibleId = null,
        ?string $dueDate = null
    ): array {
        $step = ProjectStep::find($stepId);

        if ($step === null) {
            return ['ok' => false, 'error' => 'Étape introuvable.'];
        }

        if (!in_array($toStatus, ProjectStep::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Statut invalide.'];
        }

        $transitions = self::ALLOWED_TRANSITIONS[$step['status']] ?? [];
        if (!in_array($toStatus, $transitions, true)) {
            return ['ok' => false, 'error' => sprintf(
                'Transition de statut impossible (%s → %s).',
                $step['status'],
                $toStatus
            )];
        }

        $fromStatus = (string) $step['status'];
        $now = date('Y-m-d H:i:s');
        $data = ['status' => $toStatus];

        if ($toStatus === 'en_cours' && $step['started_at'] === null) {
            $data['started_at'] = $now;
        }

        if ($toStatus === 'termine') {
            $data['completed_at'] = $now;
        } else {
            $data['completed_at'] = null;
        }

        if ($responsibleId !== null) {
            $data['responsible_id'] = $responsibleId;
        }

        if ($dueDate !== null && $dueDate !== '' && strtotime($dueDate) !== false) {
            $data['due_date'] = date('Y-m-d', (int) strtotime($dueDate));
        }

        Database::transaction(function () use ($stepId, $data, $step, $fromStatus, $toStatus, $comment, $user, $now) {
            ProjectStep::update((int) $step['id'], $data);

            ProjectStepHistory::create([
                'project_step_id' => (int) $step['id'],
                'user_id' => (int) $user['id'],
                'action' => 'status',
                'comment' => $comment !== null && $comment !== '' ? $comment : null,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ]);

            Audit::log('steps.updated', 'project_steps', (int) $step['id'], [
                'from' => $fromStatus,
                'to' => $toStatus,
            ], ['project_id' => (int) $step['project_id']]);
        });

        // Notification au client propriétaire.
        $project = Project::find((int) $step['project_id']);
        if ($project !== null) {
            Notifier::push(
                (int) $project['client_id'],
                Notifier::TYPE_STEP,
                sprintf('Projet %s — Étape « %s » : %s', $project['reference'], $step['name'], $toStatus),
                $comment ?? '',
                (int) $project['id']
            );
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Ajoute un commentaire libre à une étape (sans changement de statut).
     */
    public static function commentStep(int $stepId, array $user, string $comment): array
    {
        $step = ProjectStep::find($stepId);
        $comment = trim($comment);

        if ($step === null) {
            return ['ok' => false, 'error' => 'Étape introuvable.'];
        }

        if ($comment === '') {
            return ['ok' => false, 'error' => 'Le commentaire ne peut pas être vide.'];
        }

        ProjectStepHistory::create([
            'project_step_id' => (int) $step['id'],
            'user_id' => (int) $user['id'],
            'action' => 'comment',
            'comment' => Str::limit($comment, 2000),
            'from_status' => $step['status'],
            'to_status' => $step['status'],
        ]);

        Audit::log('steps.commented', 'project_steps', (int) $step['id'], [], ['project_id' => (int) $step['project_id']]);

        return ['ok' => true, 'error' => null];
    }

    public static function formatLabel(string $status): string
    {
        return match ($status) {
            'a_venir' => 'À venir',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'bloque' => 'Bloqué',
            'nouveau' => 'Nouveau',
            'qualification' => 'Qualification',
            'converti' => 'Converti',
            'archive' => 'Archivé',
            'disponible' => 'Disponible',
            'sous_offre' => 'Sous offre',
            'reserve' => 'Réservé',
            'vendu' => 'Vendu',
            'loue' => 'Loué',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Étiquette courte pour l'étape courante.
     */
    public static function currentStepLabel(?array $step): string
    {
        if ($step === null) {
            return 'Dossier finalisé';
        }

        return $step['status'] === 'en_cours'
            ? 'En attente : ' . $step['name']
            : 'Prochaine étape : ' . $step['name'];
    }
}