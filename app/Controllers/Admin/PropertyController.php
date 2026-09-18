<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Property;
use App\Repositories\ProjectRepository;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;
use App\Validators\PropertyValidator;

final class PropertyController extends Controller
{
    private ProjectRepository $projects;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ProjectRepository();
    }

    public function index(): Response
    {
        $filter = [
            'status' => (string) $this->request->query('status', ''),
        ];
        $page = max(1, (int) $this->request->query('page', 1));
        $conditions = [];

        if ($filter['status'] !== '') {
            $conditions['status'] = $filter['status'];
        }

        return Response::view('admin/properties/index', [
            'user' => App::user(),
            'pagination' => Property::paginate($conditions, 20, $page, [['created_at', 'DESC']]),
            'filter' => $filter,
        ]);
    }

    public function create(): Response
    {
        return Response::view('admin/properties/create', [
            'user' => App::user(),
            'projects' => \App\Models\Project::where([], [['created_at', 'DESC']], 300),
            'clients' => (new \App\Repositories\UserRepository())->paginateByRole('client', 500),
        ]);
    }

    public function store(): Response
    {
        $data = $this->request->only([
            'project_id', 'owner_client_id', 'type', 'name', 'description',
            'locality', 'price', 'currency', 'status',
        ]);
        $validation = PropertyValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $property = Property::create([
            'project_id' => $data['project_id'] !== '' && $data['project_id'] !== null ? (int) $data['project_id'] : null,
            'owner_client_id' => $data['owner_client_id'] !== '' && $data['owner_client_id'] !== null ? (int) $data['owner_client_id'] : null,
            'type' => $data['type'],
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')) !== '' ? trim((string) $data['description']) : null,
            'features' => null,
            'locality' => trim((string) ($data['locality'] ?? '')) !== '' ? trim((string) $data['locality']) : null,
            'price' => isset($data['price']) && trim((string) $data['price']) !== '' ? (float) $data['price'] : null,
            'currency' => $data['currency'] ?? 'XOF',
            'status' => $data['status'] ?? 'disponible',
        ]);

        if ($property === null) {
            App::flash('error', 'Impossible d\'enregistrer le bien.');

            return Response::redirectBack();
        }

        Audit::log('properties.created', 'properties', (int) $property['id'], [], ['name' => $property['name']]);
        App::flash('success', sprintf('Le bien « %s » a été enregistré.', $property['name']));

        return Response::redirect(route('admin.properties'));
    }

    public function edit(string $publicId): Response
    {
        $property = Property::findByPublicId($publicId);

        if ($property === null) {
            return Response::notFound('Bien introuvable.');
        }

        return Response::view('admin/properties/create', [
            'user' => App::user(),
            'property' => $property,
            'projects' => \App\Models\Project::where([], [['created_at', 'DESC']], 300),
            'clients' => (new \App\Repositories\UserRepository())->paginateByRole('client', 500),
        ]);
    }

    public function update(string $publicId): Response
    {
        $property = Property::findByPublicId($publicId);

        if ($property === null) {
            return Response::notFound('Bien introuvable.');
        }

        $data = $this->request->only([
            'project_id', 'owner_client_id', 'type', 'name', 'description',
            'locality', 'price', 'currency', 'status',
        ]);
        $validation = PropertyValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        Property::update((int) $property['id'], [
            'project_id' => $data['project_id'] !== '' && $data['project_id'] !== null ? (int) $data['project_id'] : null,
            'owner_client_id' => $data['owner_client_id'] !== '' && $data['owner_client_id'] !== null ? (int) $data['owner_client_id'] : null,
            'type' => $data['type'],
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')) !== '' ? trim((string) $data['description']) : null,
            'locality' => trim((string) ($data['locality'] ?? '')) !== '' ? trim((string) $data['locality']) : null,
            'price' => isset($data['price']) && trim((string) $data['price']) !== '' ? (float) $data['price'] : null,
            'currency' => $data['currency'] ?? 'XOF',
            'status' => $data['status'] ?? 'disponible',
        ]);

        Audit::log('properties.updated', 'properties', (int) $property['id'], [], ['name' => $property['name']]);
        App::flash('success', 'Le bien a été mis à jour.');

        return Response::redirect(route('admin.properties'));
    }

    public function destroy(string $publicId): Response
    {
        $property = Property::findByPublicId($publicId);

        if ($property === null) {
            return Response::notFound('Bien introuvable.');
        }

        Audit::log('properties.deleted', 'properties', (int) $property['id'], [], ['name' => $property['name']]);
        Property::delete((int) $property['id']);
        App::flash('success', 'Le bien a été supprimé.');

        return Response::redirect(route('admin.properties'));
    }
}