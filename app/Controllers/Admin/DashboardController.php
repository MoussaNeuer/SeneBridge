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

        return Response::view('admin/dashboard', [
            'user' => $user,
            'stats' => $this->projects->stats(),
            'clientCount' => \App\Models\User::count([
                'role_id' => \App\Models\Role::findByName('client')['id'] ?? 0,
            ]),
            'counselorCount' => \App\Models\User::count([
                'role_id' => \App\Models\Role::findByName('counselor')['id'] ?? 0,
            ]),
            'requestCounts' => $this->requests->statusCounts(),
            'newRequests' => $this->requests->countNew(),
            'unreadContacts' => ContactMessage::count(['is_read' => 0]),
            'invoiceStats' => (new InvoiceRepository())->stats(),
            'paymentStats' => (new PaymentRepository())->stats(),
            'appointmentStats' => (new AppointmentRepository())->stats(),
            'unreadThreads' => (new \App\Repositories\ConversationRepository())->unreadFor((int) $user['id']),
            'recent' => $recent,
        ]);
    }
}