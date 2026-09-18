<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Repositories\ProjectRepository;
use App\Support\App;
use App\Support\Gate;
use App\Support\Response;
use App\Support\Router;

final class DashboardController extends Controller
{
    private ProjectRepository $projects;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ProjectRepository();
    }

    public function index(): Response
    {
        $user = App::user();

        if ($user === null) {
            return Response::redirect(app_url('login'));
        }

        // Le personnel bascule automatiquement vers le back-office.
        if (Gate::isRole($user, 'admin', 'manager', 'counselor', 'accounting')) {
            return Response::redirect(Router::url('admin.dashboard'));
        }

        $projects = $this->projects->listForClient((int) $user['id']);

        return Response::view('client/dashboard', [
            'user' => $user,
            'projects' => $projects,
            'activeCount' => $this->projects->countActiveForClient((int) $user['id']),
            'totalCount' => $this->projects->countForClient((int) $user['id']),
            'unreadCount' => \App\Models\Notification::countUnread((int) $user['id']),
            'latestNotifications' => \App\Models\Notification::listFor((int) $user['id'], 5),
        ]);
    }
}