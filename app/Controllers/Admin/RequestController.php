<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\ProjectRequest;
use App\Repositories\ProjectRequestRepository;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;
use App\Validators\ProjectRequestValidator;

final class RequestController extends Controller
{
    private ProjectRequestRepository $requests;

    public function __construct()
    {
        parent::__construct();
        $this->requests = new ProjectRequestRepository();
    }

    public function index(): Response
    {
        $filters = ['status' => (string) $this->request->query('status', '')];
        $page = max(1, (int) $this->request->query('page', 1));

        return Response::view('admin/requests/index', [
            'user' => App::user(),
            'pagination' => $this->requests->paginated(20, $page, $filters),
            'statuses' => ProjectRequest::STATUSES,
            'counts' => $this->requests->statusCounts(),
            'filter' => $filters,
        ]);
    }

    public function show(string $publicId): Response
    {
        $request = ProjectRequest::findByPublicId($publicId);

        if ($request === null) {
            return Response::notFound('Demande introuvable.');
        }

        return Response::view('admin/requests/show', [
            'user' => App::user(),
            'request' => $request,
            'statuses' => ProjectRequest::STATUSES,
        ]);
    }

    public function status(string $publicId): Response
    {
        $request = ProjectRequest::findByPublicId($publicId);

        if ($request === null) {
            return Response::notFound('Demande introuvable.');
        }

        $data = ['status' => (string) $this->request->post('status', '')];
        $validation = ProjectRequestValidator::updateStatus($data);

        if (!$validation->passes()) {
            App::flash('error', $validation->firstMessage());

            return Response::redirectBack();
        }

        ProjectRequest::update((int) $request['id'], [
            'status' => $data['status'],
            'handled_by' => App::id(),
            'handled_at' => date('Y-m-d H:i:s'),
        ]);

        Audit::log('requests.status_updated', 'project_requests', (int) $request['id'], [
            'from' => $request['status'],
            'to' => $data['status'],
        ], ['email' => $request['email']]);

        App::flash('success', 'Statut de la demande mis à jour.');

        return Response::redirect(route('admin.requests.show', ['publicId' => $request['public_id']]));
    }
}