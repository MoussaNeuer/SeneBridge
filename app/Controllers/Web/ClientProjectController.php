<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Models\Document;
use App\Models\Media;
use App\Repositories\ProjectRepository;
use App\Services\FileService;
use App\Services\WorkflowService;
use App\Support\App;
use App\Support\Response;

final class ClientProjectController extends Controller
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
        $projects = $this->projects->listForClient((int) $user['id']);
        $summary = [];

        foreach ($projects as $project) {
            $summary[(int) $project['id']] = WorkflowService::progress([
                'steps_count' => $this->projects->countSteps((int) $project['id']),
                'steps_completed' => $this->projects->countCompletedSteps((int) $project['id']),
            ]);
        }

        return Response::view('client/projects', [
            'projects' => $projects,
            'progress' => $summary,
        ]);
    }

    public function show(string $publicId): Response
    {
        $user = App::user();
        $project = \App\Models\Project::findByPublicId($publicId);

        // IDOR : un client ne voit que ses propres dossiers.
        if ($project === null || (int) $project['client_id'] !== (int) $user['id']) {
            return Response::notFound('Ce dossier n\'existe pas.');
        }

        $detail = $this->projects->detail((int) $project['id']);
        $detail['steps_count'] = (int) ($detail['steps_count'] ?? 0);
        $detail['steps_completed'] = (int) ($detail['steps_completed'] ?? 0);

        return Response::view('client/project-show', [
            'project' => $detail,
            'steps' => $this->projects->steps((int) $project['id']),
            'properties' => $this->projects->properties((int) $project['id']),
            'documents' => $this->clientDocuments((int) $project['id']),
            'media' => $this->clientMedia((int) $project['id']),
            'history' => $this->projects->recentActivity((int) $project['id'], 15),
            'progress' => WorkflowService::progress($detail),
            'currentStep' => WorkflowService::currentStepLabel($this->projects->currentStep((int) $project['id'])),
        ]);
    }

    public function downloadDocument(string $publicId, string $documentPublicId): Response
    {
        $project = $this->guardProject($publicId);

        if ($project === null) {
            return Response::notFound('Ce dossier n\'existe pas.');
        }

        $document = Document::findByPublicId($documentPublicId);

        if ($document === null
            || (int) $document['project_id'] !== (int) $project['id']
            || $document['visibility'] !== 'client'
            || $document['status'] !== 'final') {
            return Response::notFound('Ce document n\'est pas disponible.');
        }

        return FileService::download(
            FileService::DIR_DOCUMENTS,
            (string) $document['internal_name'],
            (string) $document['mime_type'],
            (string) ($document['original_name'] ?? $document['internal_name'])
        );
    }

    public function downloadMedia(string $publicId, string $mediaPublicId): Response
    {
        $project = $this->guardProject($publicId);

        if ($project === null) {
            return Response::notFound('Ce dossier n\'existe pas.');
        }

        $media = Media::findByPublicId($mediaPublicId);

        if ($media === null || (int) $media['project_id'] !== (int) $project['id'] || $media['visibility'] !== 'client') {
            return Response::notFound('Ce fichier n\'est pas disponible.');
        }

        return FileService::download(
            FileService::DIR_MEDIA,
            (string) $media['internal_name'],
            (string) $media['mime_type'],
            (string) ($media['original_name'] ?? $media['internal_name'])
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function guardProject(string $publicId): ?array
    {
        $user = App::user();
        $project = \App\Models\Project::findByPublicId($publicId);

        if ($project === null || (int) $project['client_id'] !== (int) $user['id']) {
            return null;
        }

        return $project;
    }

    /**
     * Documents publiés au client (final, visibilité client).
     *
     * @return array<int, array<string, mixed>>
     */
    private function clientDocuments(int $projectId): array
    {
        return Document::where(
            ['project_id' => $projectId, 'visibility' => 'client', 'status' => 'final'],
            [['created_at', 'DESC']]
        );
    }

    /**
     * Photos partagées au client.
     *
     * @return array<int, array<string, mixed>>
     */
    private function clientMedia(int $projectId): array
    {
        return Media::where(
            ['project_id' => $projectId, 'visibility' => 'client'],
            [['created_at', 'DESC']]
        );
    }
}