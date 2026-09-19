<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Repositories\AuditRepository;
use App\Support\App;
use App\Support\Response;

final class AuditController extends Controller
{
    public function index(): Response
    {
        $user = App::user();
        $filters = [
            'action' => (string) $this->request->query('action', ''),
            'q' => (string) $this->request->query('q', ''),
        ];
        $page = max(1, (int) $this->request->query('page', 1));

        $repository = new AuditRepository();
        $pagination = $repository->paginated($filters, 25, $page);

        return Response::view('admin/audit/index', [
            'user' => $user,
            'pagination' => $pagination,
            'filters' => $filters,
            'actions' => $repository->actions(),
        ]);
    }
}