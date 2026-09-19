<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ProjectRepository;
use App\Support\Database;
use App\Support\Response;

final class ProjectController extends ApiController
{
    private ProjectRepository $projects;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ProjectRepository();
    }

    /**
     * GET /api/v1/projects
     */
    public function index(): Response
    {
        if (!$this->isStaff()) {
            $items = $this->projects->listForClient($this->id());

            return $this->ok(['items' => $items, 'total' => count($items), 'per_page' => count($items), 'page' => 1, 'last_page' => 1]);
        }

        $filters = [
            'status' => (string) $this->request->query('status', ''),
            'type' => (string) $this->request->query('type', ''),
        ];
        ['page' => $page, 'per_page' => $perPage] = $this->paging();

        return $this->page($this->projects->listManaged($filters, $perPage, $page));
    }

    /**
     * GET /api/v1/projects/{publicId}
     */
    public function show(string $publicId): Response
    {
        $project = $this->resolve($publicId);

        if ($project === null) {
            return $this->notFound('Dossier introuvable.');
        }

        return $this->ok($project);
    }

    /**
     * GET /api/v1/projects/{publicId}/steps
     */
    public function steps(string $publicId): Response
    {
        $project = $this->resolve($publicId);

        if ($project === null) {
            return $this->notFound('Dossier introuvable.');
        }

        return $this->ok($this->projects->steps((int) $project['id']));
    }

    /**
     * GET /api/v1/projects/{publicId}/properties
     */
    public function properties(string $publicId): Response
    {
        $project = $this->resolve($publicId);

        if ($project === null) {
            return $this->notFound('Dossier introuvable.');
        }

        return $this->ok($this->projects->properties((int) $project['id']));
    }

    /**
     * GET /api/v1/projects/{publicId}/documents
     */
    public function documents(string $publicId): Response
    {
        $project = $this->resolve($publicId);

        if ($project === null) {
            return $this->notFound('Dossier introuvable.');
        }

        $visibility = $this->isStaff() ? [] : ['client', 'admin'];

        $rows = Database::select(
            'SELECT d.id, d.public_id, d.project_id, d.category, d.original_name, d.mime_type,
                    d.size, d.version, d.visibility, d.status, d.created_at,
                    u.first_name AS uploader_first_name, u.last_name AS uploader_last_name
             FROM documents d
             LEFT JOIN users u ON u.id = d.uploader_id
             WHERE d.project_id = ? AND d.deleted_at IS NULL' .
                ($visibility !== [] ? ' AND d.visibility IN (\'client\', \'admin\')' : '') . '
             ORDER BY d.created_at DESC',
            [$project['id']]
        );

        return $this->ok($rows);
    }

    /**
     * Résout un dossier par public_id avec contrôle d'accès.
     */
    private function resolve(string $publicId): ?array
    {
        $project = $this->projects->detail((int) ($this->projectsId($publicId) ?? 0));

        if ($project === null) {
            return null;
        }

        // Un client n'accède qu'à ses propres dossiers (pas de fuite d'existence).
        if (!$this->isStaff() && (int) $project['client_id'] !== $this->id()) {
            return null;
        }

        return $project;
    }

    private function projectsId(string $publicId): ?int
    {
        $row = Database::first('SELECT id FROM projects WHERE public_id = ? LIMIT 1', [$publicId]);

        return $row !== null ? (int) $row['id'] : null;
    }
}