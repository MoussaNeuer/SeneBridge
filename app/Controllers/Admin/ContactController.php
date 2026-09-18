<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\App;
use App\Support\Response;

final class ContactController extends Controller
{
    public function index(): Response
    {
        $page = max(1, (int) $this->request->query('page', 1));

        return Response::view('admin/contacts/index', [
            'user' => App::user(),
            'pagination' => ContactMessage::paginate([], 20, $page, [['created_at', 'DESC']]),
        ]);
    }

    public function show(string $publicId): Response
    {
        $message = ContactMessage::findByPublicId($publicId);

        if ($message === null) {
            return Response::notFound('Message introuvable.');
        }

        if (!$message['is_read']) {
            ContactMessage::update((int) $message['id'], ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
            $message['is_read'] = 1;
        }

        return Response::view('admin/contacts/show', [
            'user' => App::user(),
            'message' => $message,
        ]);
    }
}