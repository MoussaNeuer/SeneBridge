<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Notification;
use App\Support\Database;
use App\Support\Response;

final class NotificationController extends ApiController
{
    /**
     * GET /api/v1/notifications
     */
    public function index(): Response
    {
        ['page' => $page, 'per_page' => $perPage] = $this->paging();

        $total = (int) Database::scalar('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$this->id()]);
        $offset = ($page - 1) * $perPage;

        $items = Database::select(
            'SELECT public_id, type, title, body, project_id, is_read, read_at, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ? OFFSET ?',
            [$this->id(), $perPage, $offset]
        );

        return $this->ok([
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    /**
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(): Response
    {
        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$this->id()]
        );

        return $this->ok(['count' => $count]);
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function readAll(): Response
    {
        Database::statement(
            'UPDATE notifications SET is_read = 1, read_at = ? WHERE user_id = ? AND is_read = 0',
            [date('Y-m-d H:i:s'), $this->id()]
        );

        return $this->ok(null, 'Notifications marquées comme lues.');
    }

    /**
     * POST /api/v1/notifications/{publicId}/read
     */
    public function read(string $publicId): Response
    {
        $notification = Notification::findByPublicId($publicId);

        if ($notification === null || (int) $notification['user_id'] !== $this->id()) {
            return $this->notFound('Notification introuvable.');
        }

        if ((int) $notification['is_read'] === 0) {
            Notification::update((int) $notification['id'], ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
            $notification['is_read'] = 1;
            $notification['read_at'] = date('Y-m-d H:i:s');
        }

        return $this->ok($notification);
    }
}