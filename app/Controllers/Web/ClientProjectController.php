<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Repositories\ProjectRepository;
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
            'history' => $this->projects->recentActivity((int) $project['id'], 15),
            'progress' => WorkflowService::progress($detail),
            'currentStep' => WorkflowService::currentStepLabel($this->projects->currentStep((int) $project['id'])),
        ]);
    }
}