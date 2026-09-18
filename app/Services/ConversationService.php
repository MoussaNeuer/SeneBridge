<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Repositories\ConversationRepository;
use App\Support\Audit;
use App\Support\Gate;
use App\Support\Str;

final class ConversationService
{
    private ConversationRepository $conversations;

    public function __construct(?ConversationRepository $conversations = null)
    {
        $this->conversations = $conversations ?? new ConversationRepository();
    }

    /**
     * Une seule conversation ouverte par dossier ; en crée une si absente.
     *
     * @return array<string, mixed>|null
     */
    public function ensureForProject(int $projectId, int $clientId, int $operatorId): ?array
    {
        $conversation = $this->conversations->findForProject($projectId);

        if ($conversation !== null) {
            $this->addParticipant((int) $conversation['id'], $operatorId);

            return $conversation;
        }

        $created = Conversation::create([
            'project_id' => $projectId,
            'client_id' => $clientId,
            'title' => sprintf('Dossier #%d', $projectId),
            'status' => 'ouverte',
            'created_by' => $operatorId,
        ]);

        if ($created === null) {
            return null;
        }

        $this->addParticipant((int) $created['id'], $clientId);
        $this->addParticipant((int) $created['id'], $operatorId);

        return $created;
    }

    public function addParticipant(int $conversationId, int $userId): void
    {
        if (ConversationParticipant::count(['conversation_id' => $conversationId, 'user_id' => $userId]) === 0) {
            ConversationParticipant::create([
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'last_read_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Envoie un message et notifie le client uniquement quand l'émetteur est du personnel.
     *
     * @return array{message: array<string, mixed>|null, error: ?string}
     */
    public function send(int $conversationId, array $sender, string $body): array
    {
        $conversation = $this->conversations->find($conversationId);

        if ($conversation === null || $conversation['status'] !== 'ouverte') {
            return ['message' => null, 'error' => 'Conversation fermée ou introuvable.'];
        }

        $body = trim($body);

        if ($body === '') {
            return ['message' => null, 'error' => 'Le message ne peut pas être vide.'];
        }

        $this->addParticipant($conversationId, (int) $sender['id']);

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => (int) $sender['id'],
            'body' => str_replace(["\r\n", "\r"], "\n", $body),
            'is_read' => 0,
        ]);

        if ($message === null) {
            return ['message' => null, 'error' => 'Impossible d\'envoyer le message.'];
        }

        ConversationParticipant::touchRead($conversationId, (int) $sender['id']);

        // Qui a écrit : si ce n'est pas le client, on le prévient.
        if (!Gate::isRole($sender, 'client')) {
            Notifier::push(
                (int) $conversation['client_id'],
                Notifier::TYPE_MESSAGE,
                'Nouveau message sur votre dossier',
                Str::limit($body, 120),
                $conversation['project_id'] !== null ? (int) $conversation['project_id'] : null
            );
        }

        return ['message' => $message, 'error' => null];
    }

    public function close(int $conversationId, array $user): bool
    {
        $conversation = $this->conversations->find($conversationId);

        if ($conversation === null) {
            return false;
        }

        Conversation::update($conversationId, ['status' => 'archive']);

        Audit::log('conversations.archived', 'conversations', $conversationId, [], ['conversation_id' => $conversationId]);

        return true;
    }
}