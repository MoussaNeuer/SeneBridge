<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Models\ProjectRequest;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;
use App\Support\Str;
use App\Validators\ProjectRequestValidator;

final class ProjectRequestController extends Controller
{
    public function show(): Response
    {
        return Response::view('public/start-project');
    }

    public function submit(): Response
    {
        $data = $this->request->only([
            'intent', 'project_type', 'first_name', 'last_name', 'email', 'phone',
            'locality', 'budget', 'description',
        ]);
        $validation = ProjectRequestValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $user = App::user();
        $request = ProjectRequest::create([
            'user_id' => $user !== null ? (int) $user['id'] : null,
            'intent' => $data['intent'],
            'project_type' => $data['project_type'],
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'phone' => trim((string) ($data['phone'] ?? '')) !== '' ? trim((string) $data['phone']) : null,
            'locality' => trim((string) ($data['locality'] ?? '')) !== '' ? trim((string) $data['locality']) : null,
            'budget' => isset($data['budget']) && trim((string) $data['budget']) !== '' ? (float) $data['budget'] : null,
            'currency' => 'XOF',
            'description' => trim((string) ($data['description'] ?? '')) !== '' ? Str::limit((string) $data['description'], 5000) : null,
            'status' => 'nouveau',
        ]);

        if ($request !== null) {
            Audit::log('requests.created', 'project_requests', (int) $request['id'], [], [
                'email' => $request['email'],
                'intent' => $request['intent'],
                'type' => $request['project_type'],
            ]);
        }

        App::flash('success', 'Votre demande a bien été enregistrée. Notre équipe vous contactera rapidement.');

        return Response::redirect(route('pages.start-project'));
    }
}