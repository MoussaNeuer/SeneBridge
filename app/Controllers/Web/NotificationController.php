<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Models\Notification;
use App\Support\App;
use App\Support\Response;

final class NotificationController extends Controller
{
    public function index(): Response
    {
        $user = App::user();

        return Response::view('client/notifications', [
            'notifications' => Notification::listFor((int) $user['id'], 50),
        ]);
    }

    public function read(string $publicId): Response
    {
        $user = App::user();
        $notification = Notification::findByPublicId($publicId);

        if ($notification === null || (int) $notification['user_id'] !== (int) $user['id']) {
            return Response::notFound('Notification introuvable.');
        }

        Notification::markRead((int) $notification['id']);

        return Response::redirectBack();
    }
}