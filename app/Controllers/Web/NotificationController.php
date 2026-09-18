<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Models\Notification;
use App\Support\App;
use App\Support\Database;
use App\Support\Response;

final class NotificationController extends Controller
{
    public function index(): Response
    {
        $user = App::user();
        $notifications = Notification::listFor((int) $user['id'], 50);

        return Response::view('client/notifications', [
            'notifications' => $notifications,
            'hasUnread' => (int) Notification::countUnread((int) $user['id']) > 0,
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

    /**
     * Marque toutes les notifications de l'utilisateur comme lues.
     */
    public function readAll(): Response
    {
        $user = App::user();

        Database::statement(
            'UPDATE notifications SET is_read = 1, read_at = ?
             WHERE user_id = ? AND is_read = 0',
            [date('Y-m-d H:i:s'), (int) $user['id']]
        );

        App::flash('success', 'Toutes vos notifications ont été marquées comme lues.');

        return Response::redirect(route('client.notifications'));
    }
}