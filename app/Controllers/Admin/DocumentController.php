<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Document;
use App\Policies\ProjectPolicy;
use App\Services\FileService;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;

final class DocumentController extends Controller
{
    /**
     * Téléversement : les fichiers sont enregistrés immédiatement en « brouillon »,
     * l'équipe les passe ensuite en « final » (visible) depuis la page du dossier.
     */
    public function store(string $publicId): Response
    {
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::view(App::user(), $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $user = App::user();
        $files = $this->request->file('files');
        $fileItems = $files['name'] ?? [];
        $uploaded = 0;
        $errors = [];

        $category = (string) $this->request->post('category', 'autre');
        if (!in_array($category, Document::CATEGORIES, true)) {
            $category = 'autre';
        }

        $visibility = (string) $this->request->post('visibility', 'private');
        if (!in_array($visibility, Document::VISIBILITIES, true)) {
            $visibility = 'private';
        }

        $count = is_array($fileItems) ? count($fileItems) : 0;

        for ($i = 0; $i < $count; $i++) {
            $one = [
                'name' => (string) ($fileItems[$i] ?? ''),
                'type' => (string) ($files['type'][$i] ?? ''),
                'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
                'error' => (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($files['size'][$i] ?? 0),
            ];

            $stored = FileService::store(
                $one,
                FileService::DIR_DOCUMENTS,
                (int) config('storage.uploads.max_size', 10485760)
            );

            if (!$stored['ok']) {
                $errors[] = $one['name'] !== '' ? sprintf('« %s » : %s', $one['name'], $stored['error']) : $stored['error'];
                continue;
            }

            $document = Document::create([
                'project_id' => (int) $project['id'],
                'uploader_id' => (int) $user['id'],
                'category' => $category,
                'internal_name' => $stored['internal_name'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'size' => $stored['size'],
                'version' => 1,
                'visibility' => $visibility,
                'status' => 'brouillon',
            ]);

            if ($document !== null) {
                $uploaded++;
                Audit::log('documents.uploaded', 'documents', (int) $document['id'], [], [
                    'project_id' => (int) $project['id'],
                    'category' => $category,
                ]);
            }
        }

        if ($uploaded > 0) {
            App::flash('success', sprintf('%d fichier(s) ajouté(s) (brouillon).', $uploaded));
        }

        foreach ($errors as $error) {
            App::flash('error', $error);
        }

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }

    public function status(string $publicId, string $documentPublicId): Response
    {
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::view(App::user(), $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $document = Document::findByPublicId($documentPublicId);

        if ($document === null || (int) $document['project_id'] !== (int) $project['id']) {
            return Response::notFound('Document introuvable.');
        }

        $status = (string) $this->request->post('status', '');
        if (!in_array($status, ['brouillon', 'final', 'archive'], true)) {
            App::flash('error', 'Statut invalide.');

            return Response::redirectBack();
        }

        Document::update((int) $document['id'], ['status' => $status]);
        Audit::log('documents.status', 'documents', (int) $document['id'], [
            'from' => $document['status'],
            'to' => $status,
        ], ['project_id' => (int) $project['id']]);

        App::flash('success', 'Statut du document mis à jour.');

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }

    public function download(string $publicId, string $documentPublicId): Response
    {
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::view(App::user(), $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $document = Document::findByPublicId($documentPublicId);

        if ($document === null || (int) $document['project_id'] !== (int) $project['id']) {
            return Response::notFound('Document introuvable.');
        }

        return FileService::download(
            FileService::DIR_DOCUMENTS,
            (string) $document['internal_name'],
            (string) $document['mime_type'],
            (string) ($document['original_name'] ?? $document['internal_name'])
        );
    }
}