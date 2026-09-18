<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Media;
use App\Policies\ProjectPolicy;
use App\Services\FileService;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;

final class MediaController extends Controller
{
    /**
     * Téléversement d'images (photos de chantier, plans, etc.) côté dossier.
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

        $visibility = (string) $this->request->post('visibility', 'private');
        if (!in_array($visibility, Media::VISIBILITIES, true)) {
            $visibility = 'private';
        }

        $category = trim((string) $this->request->post('category', ''));
        $altText = trim((string) $this->request->post('alt_text', ''));

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
                FileService::DIR_MEDIA,
                (int) config('storage.media.max_size', 52428800),
                true
            );

            if (!$stored['ok']) {
                $errors[] = $one['name'] !== '' ? sprintf('« %s » : %s', $one['name'], $stored['error']) : $stored['error'];
                continue;
            }

            $media = Media::create([
                'project_id' => (int) $project['id'],
                'uploader_id' => (int) $user['id'],
                'type' => 'image',
                'category' => $category !== '' ? $category : null,
                'internal_name' => $stored['internal_name'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'size' => $stored['size'],
                'alt_text' => $altText !== '' ? $altText : null,
                'visibility' => $visibility,
            ]);

            if ($media !== null) {
                $uploaded++;
                Audit::log('media.uploaded', 'media', (int) $media['id'], [], [
                    'project_id' => (int) $project['id'],
                ]);
            }
        }

        if ($uploaded > 0) {
            App::flash('success', sprintf('%d image(s) ajoutée(s).', $uploaded));
        }

        foreach ($errors as $error) {
            App::flash('error', $error);
        }

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }

    /**
     * Bascule de visibilité client/privé.
     */
    public function visibility(string $publicId, string $mediaPublicId): Response
    {
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::view(App::user(), $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $media = Media::findByPublicId($mediaPublicId);

        if ($media === null || (int) $media['project_id'] !== (int) $project['id']) {
            return Response::notFound('Média introuvable.');
        }

        $visibility = (string) $this->request->post('visibility', '');
        if (!in_array($visibility, ['private', 'client', 'admin'], true)) {
            App::flash('error', 'Visibilité invalide.');

            return Response::redirectBack();
        }

        Media::update((int) $media['id'], ['visibility' => $visibility]);
        Audit::log('media.visibility', 'media', (int) $media['id'], [
            'from' => $media['visibility'],
            'to' => $visibility,
        ], ['project_id' => (int) $project['id']]);

        App::flash('success', 'Visibilité du média mise à jour.');

        return Response::redirect(route('admin.projects.show', ['publicId' => $project['public_id']]));
    }

    public function download(string $publicId, string $mediaPublicId): Response
    {
        $project = ProjectPolicy::project($publicId);

        if ($project === null || !ProjectPolicy::view(App::user(), $project)) {
            return Response::notFound('Ce dossier est introuvable ou inaccessible.');
        }

        $media = Media::findByPublicId($mediaPublicId);

        if ($media === null || (int) $media['project_id'] !== (int) $project['id']) {
            return Response::notFound('Média introuvable.');
        }

        return FileService::download(
            FileService::DIR_MEDIA,
            (string) $media['internal_name'],
            (string) $media['mime_type'],
            (string) ($media['original_name'] ?? $media['internal_name'])
        );
    }
}