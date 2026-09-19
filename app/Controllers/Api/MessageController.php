<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ConversationRepository;
use App\Services\ConversationService;
use App\Support\Database;
use App\Support\Response;

final class MessageController extends ApiController
{
    private ConversationRepository $conversations;

    public function __construct()
    {
        parent::__construct();
        $this->conversations = new ConversationRepository();
    }

    /**
     * GET /api/v1/projects/{publicId}/messages — fil de discussion du dossier.
     */
    public function index(string $publicId): Response
    {
        $conversation = $this->conversationFor($publicId);

        if ($conversation === null) {
            return $this->ok(['items' => []]);
        }

        $thread = $this->conversations->thread((int) $conversation['id']);

        return $this->ok([
            'conversation' => [
                'public_id' => $conversation['public_id'],
                'title' => $conversation['title'],
                'status' => $conversation['status'],
                'created_at' => $conversation['created_at'],
            ],
            'items' => array_map(static fn (array $m): array => [
                'public_id' => $m['public_id'],
                'body' => $m['body'],
                'sender' => trim((string) ($m['first_name'] ?? '') . ' ' . (string) ($m['last_name'] ?? '')),
                'is_read' => (bool) $m['is_read'],
                'created_at' => $m['created_at'],
            ], $thread['messages']),
        ]);
    }

    /**
     * POST /api/v1/projects/{publicId}/messages
     */
    public function store(string $publicId): Response
    {
        $project = $this->projectFor($publicId);

        if ($project === null) {
            return $this->notFound('Dossier introuvable.');
        }

        $body = trim((string) $this->request->input('body', ''));

        if ($body === '') {
            return $this->fail('Le message ne peut pas être vide.', 422, ['body' => 'Le message ne peut pas être vide.']);
        }

        if (mb_strlen($body) > 2000) {
            return $this->fail('Le message est trop long (2000 caractères max).', 422, ['body' => 'Message trop long.']);
        }

        $conversation = $this->conversations->findForProject((int) $project['id']);
        $service = new ConversationService();
        $conversationId = (int) ($conversation['id'] ?? 0);

        if ($conversation === null) {
            $created = $service->ensureForProject((int) $project['id'], (int) $project['client_id'], $this->id());

            if ($created === null) {
                return $this->fail('Impossible d\'ouvrir la discussion.', 500);
            }
            $conversationId = (int) $created['id'];
        }

        $result = $service->send($conversationId, $this->authUser, $body);

        if ($result['message'] === null) {
            return $this->fail($result['error'] ?? 'Impossible d\'envoyer le message.', 422);
        }

        return $this->created($result['message']);
    }

    /**
     * Résolution du dossier avec contrôle d'accès (propriétaire ou personnel).
     */
    private function projectFor(string $publicId): ?array
    {
        $row = Database::first('SELECT id, client_id FROM projects WHERE public_id = ? LIMIT 1', [$publicId]);

        if ($row === null) {
            return null;
        }

        if (!$this->isStaff() && (int) $row['client_id'] !== $this->id()) {
            return null;
        }

        return $row;
    }

    /**
     * Conversation ouverte du dossier si l'appelant y participe (ou est du personnel).
     */
    private function conversationFor(string $publicId): ?array
    {
        $project = $this->projectFor($publicId);

        if ($project === null) {
            return null;
        }

        $conversation = $this->conversations->findForProject((int) $project['id']);

        if ($conversation === null) {
            return null;
        }

        if (!$this->isStaff() && !$this->conversations->isParticipant((int) $conversation['id'], $this->id())) {
            return null;
        }

        return $conversation;
    }
}