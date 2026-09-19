<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\ConversationRepository;

/**
 * Widgets du back-office : menu latéral (sections, icônes) et compteurs
 * de badge. Centralise la structure de navigation pour le layout et le
 * endpoint /admin/api/compteurs (mise à jour en temps réel des badges).
 */
final class BackofficeWidgets
{
    /**
     * @return array<int, array{label:string, icon:string, items:array<int, array{label:string, route:string, permission:string, badge?:string}>}>
     */
    public static function menu(array $user): array
    {
        $unreadThreads = (int) (new ConversationRepository())->unreadFor((int) $user['id']);

        return [
            [
                'label' => 'Pilotage',
                'icon' => 'gauge',
                'items' => [
                    ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'permission' => 'admin.access'],
                    ['label' => 'Dossiers', 'route' => 'admin.projects', 'permission' => 'projects.view'],
                    ['label' => 'Pipeline', 'route' => 'admin.projects.kanban', 'permission' => 'projects.view'],
                    ['label' => 'Demandes reçues', 'route' => 'admin.requests', 'permission' => 'requests.view', 'badge' => self::countNewRequests()],
                ],
            ],
            [
                'label' => 'Gestion',
                'icon' => 'briefcase',
                'items' => [
                    ['label' => 'Clients', 'route' => 'admin.clients', 'permission' => 'users.view'],
                    ['label' => 'Conseillers', 'route' => 'admin.counselors', 'permission' => 'users.assign_counselor'],
                    ['label' => 'Rôles & droits', 'route' => 'admin.roles', 'permission' => 'roles.view'],
                    ['label' => 'Biens immobiliers', 'route' => 'admin.properties', 'permission' => 'properties.view'],
                ],
            ],
            [
                'label' => 'Finance',
                'icon' => 'wallet',
                'items' => [
                    ['label' => 'Factures', 'route' => 'admin.invoices', 'permission' => 'invoices.view', 'badge' => self::countLateInvoices()],
                    ['label' => 'Paiements', 'route' => 'admin.payments', 'permission' => 'payments.view', 'badge' => self::countPendingPayments()],
                ],
            ],
            [
                'label' => 'Communication',
                'icon' => 'chat',
                'items' => [
                    ['label' => 'Messagerie', 'route' => 'admin.messages', 'permission' => 'messages.view', 'badge' => $unreadThreads],
                    ['label' => 'Rendez-vous', 'route' => 'admin.appointments', 'permission' => 'appointments.view', 'badge' => self::countAppointmentRequests()],
                    ['label' => 'Agenda', 'route' => 'admin.appointments.agenda', 'permission' => 'appointments.view', 'badge' => self::countAppointmentsToday()],
                    ['label' => 'Messages contact', 'route' => 'admin.contacts', 'permission' => 'contacts.view', 'badge' => self::countUnreadContacts()],
                    ['label' => 'Actualités', 'route' => 'admin.articles', 'permission' => 'articles.manage'],
                ],
            ],
            [
                'label' => 'Supervision',
                'icon' => 'shield',
                'items' => [
                    ['label' => 'Journal d\'audit', 'route' => 'admin.audit', 'permission' => 'audit.view'],
                    ['label' => 'Système', 'route' => 'admin.system', 'permission' => 'admin.access'],
                ],
            ],
        ];
    }

    /**
     * Compteurs destinés au polling temps réel (endpoint JSON + badges).
     *
     * @return array<string, int>
     */
    public static function counters(): array
    {
        return [
            'requests_new' => self::countNewRequests(),
            'contacts_unread' => self::countUnreadContacts(),
            'payments_pending' => self::countPendingPayments(),
            'appointments_demande' => self::countAppointmentRequests(),
            'appointments_today' => self::countAppointmentsToday(),
            'invoices_late' => self::countLateInvoices(),
        ];
    }

    private static function countNewRequests(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM project_requests WHERE status = ?', ['nouveau']);
    }

    private static function countUnreadContacts(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
    }

    private static function countPendingPayments(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM payments WHERE status = ?', ['en_cours']);
    }

    private static function countAppointmentRequests(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM appointments WHERE status = ?', ['demande']);
    }

    private static function countAppointmentsToday(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM appointments WHERE status = ? AND requested_date = ?', ['confirme', date('Y-m-d')]);
    }

    private static function countLateInvoices(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM invoices WHERE status = ? AND deleted_at IS NULL', ['en_retard']);
    }
}