<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Support\Database;

final class ConversationRepository
{
    /**
     * Conversations d'un utilisateur avec aperçu et non-lus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return Database::select(
            'SELECT c.public_id, c.title, c.project_id, c.status,
                    cl.first_name AS client_first_name, cl.last_name AS client_last_name,
                    p.reference AS project_reference, p.name AS project_name,
                    (SELECT m.body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_at,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) AS unread
             FROM conversations c
             JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ?
             LEFT JOIN users cl ON cl.id = c.client_id
             LEFT JOIN projects p ON p.id = c.project_id
             WHERE c.status = \'ouverte\'
             ORDER BY (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) DESC,
                      c.created_at DESC',
            [$userId, $userId]
        );
    }

    /**
     * Fil de discussion (messages ordonnés + participants).
     *
     * @return array{conversation: ?array<string, mixed>, messages: array<int, array<string, mixed>>, participants: array<int, array<string, mixed>>}
     */
    public function thread(int $conversationId): array
    {
        $conversation = Database::first(
            'SELECT c.*, cl.first_name AS client_first_name, cl.last_name AS client_last_name,
                    cl.email AS client_email,
                    p.reference AS project_reference, p.name AS project_name
             FROM conversations c
             LEFT JOIN users cl ON cl.id = c.client_id
             LEFT JOIN projects p ON p.id = c.project_id
             WHERE c.id = ? LIMIT 1',
            [$conversationId]
        );

        $messages = Database::select(
            'SELECT m.*, u.first_name, u.last_name, u.role_id
             FROM messages m
             LEFT JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.id ASC',
            [$conversationId]
        );

        $participants = Database::select(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.role_id, cp.last_read_at
             FROM conversation_participants cp
             JOIN users u ON u.id = cp.user_id
             WHERE cp.conversation_id = ?
             ORDER BY u.id ASC',
            [$conversationId]
        );

        return [
            'conversation' => $conversation,
            'messages' => $messages,
            'participants' => $participants,
        ];
    }

    public function find(int $conversationId): ?array
    {
        return Conversation::find($conversationId);
    }

    public function findByPublicId(string $publicId): ?array
    {
        return Conversation::findByPublicId($publicId);
    }

    /**
     * Conversation ouverte d'un dossier (une seule par projet).
     */
    public function findForProject(int $projectId): ?array
    {
        return Database::first(
            'SELECT * FROM conversations WHERE project_id = ? AND status = \'ouverte\' ORDER BY id DESC LIMIT 1',
            [$projectId]
        );
    }

    public function isParticipant(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::count(['conversation_id' => $conversationId, 'user_id' => $userId]) > 0;
    }

    /**
     * Nombre total de messages non lus pour un utilisateur.
     */
    public function unreadFor(int $userId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*)
             FROM messages m
             JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ?
             WHERE m.sender_id != ? AND m.is_read = 0',
            [$userId, $userId]
        );
    }

    public function markRead(int $conversationId, int $userId): void
    {
        Database::statement(
            'UPDATE messages SET is_read = 1, read_at = ?
             WHERE conversation_id = ? AND sender_id != ? AND is_read = 0',
            [date('Y-m-d H:i:s'), $conversationId, $userId]
        );

        ConversationParticipant::touchRead($conversationId, $userId);
    }
}