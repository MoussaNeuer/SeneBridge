<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Repositories\AppointmentRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProjectRequestRepository;
use App\Repositories\ProjectRepository;
use App\Models\ContactMessage;
use App\Models\Notification;
use App\Models\Project;
use App\Support\App;
use App\Support\Database;
use App\Support\Response;

final class DashboardController extends Controller
{
    private ProjectRepository $projects;
    private ProjectRequestRepository $requests;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ProjectRepository();
        $this->requests = new ProjectRequestRepository();
    }

    public function index(): Response
    {
        $user = App::user();
        $recent = Project::where([], [['created_at', 'DESC']], 8);
        $clientRoleId = \App\Models\Role::findByName('client')['id'] ?? 0;
        $counselorRoleId = \App\Models\Role::findByName('counselor')['id'] ?? 0;

        return Response::view('admin/dashboard', [
            'user' => $user,
            'stats' => $this->projects->stats(),
            'clientCount' => \App\Models\User::count(['role_id' => $clientRoleId]),
            'counselorCount' => \App\Models\User::count(['role_id' => $counselorRoleId]),
            'requestCounts' => $this->requests->statusCounts(),
            'newRequests' => $this->requests->countNew(),
            'unreadContacts' => ContactMessage::count(['is_read' => 0]),
            'invoiceStats' => (new InvoiceRepository())->stats(),
            'paymentStats' => (new PaymentRepository())->stats(),
            'appointmentStats' => (new AppointmentRepository())->stats(),
            'unreadThreads' => (new \App\Repositories\ConversationRepository())->unreadFor((int) $user['id']),
            'recent' => $recent,
            'finance' => $this->financeKpis(),
            'monthlyRevenue' => $this->monthlyRevenue(12),
            'recentPayments' => $this->recentPayments(6),
            'upcomingAppointments' => $this->upcomingAppointments(6),
        ]);
    }

    /**
     * Indicateurs financiers de pilotage.
     *
     * @return array<string, mixed>
     */
    private function financeKpis(): array
    {
        $gross = (float) Database::scalar('SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE deleted_at IS NULL');
        $collected = (float) Database::scalar("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'valide'");
        $outstanding = (float) Database::scalar(
            "SELECT COALESCE(SUM(amount - paid_amount), 0) FROM invoices
             WHERE status IN ('envoyee', 'partielle', 'en_retard')"
        );

        return [
            'gross' => $gross,
            'collected' => $collected,
            'outstanding' => $outstanding,
            'recovery_rate' => $gross > 0 ? round($collected / $gross * 100, 1) : 0.0,
        ];
    }

    /**
     * CA encaissé par mois (paiements validés) sur les n derniers mois.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthlyRevenue(int $months = 12): array
    {
        $start = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
        $rows = [];

        foreach (Database::select(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE status = 'valide' AND payment_date >= ?
             GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
             ORDER BY month ASC",
            [$start]
        ) as $row) {
            $rows[(string) $row['month']] = (float) $row['total'];
        }

        $series = [];
        $now = new \DateTimeImmutable(date('Y-m-01'));

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->modify('-' . $i . ' months');
            $key = $date->format('Y-m');
            $series[] = [
                'month' => $key,
                'label' => $this->monthLabel($date),
                'total' => $rows[$key] ?? 0.0,
            ];
        }

        return $series;
    }

    private function monthLabel(\DateTimeImmutable $date): string
    {
        $months = [1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin',
            7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.'];

        return ($months[(int) $date->format('n')] ?? $date->format('m')) . ' ' . $date->format('y');
    }

    /**
     * Derniers paiements validés avec client.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentPayments(int $limit = 6): array
    {
        return Database::select(
            "SELECT p.public_id, p.amount, p.currency, p.payment_date, p.method,
                    c.first_name AS client_first_name, c.last_name AS client_last_name,
                    i.number AS invoice_number
             FROM payments p
             LEFT JOIN users c ON c.id = p.client_id
             LEFT JOIN invoices i ON i.id = p.invoice_id
             WHERE p.status = 'valide'
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Prochains rendez-vous confirmés.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcomingAppointments(int $limit = 6): array
    {
        return Database::select(
            "SELECT a.public_id, a.requested_date, a.requested_time, a.motive,
                    c.first_name AS client_first_name, c.last_name AS client_last_name,
                    co.first_name AS counselor_first_name, co.last_name AS counselor_last_name
             FROM appointments a
             LEFT JOIN users c ON c.id = a.client_id
             LEFT JOIN users co ON co.id = a.counselor_id
             WHERE a.status = 'confirme' AND a.requested_date >= ?
             ORDER BY a.requested_date ASC, a.requested_time ASC
             LIMIT ?",
            [date('Y-m-d'), $limit]
        );
    }
}