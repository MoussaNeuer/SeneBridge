<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Repositories\ConversationRepository;
use App\Services\ConversationService;
use App\Support\App;
use App\Support\Response;

final class MessageController extends Controller
{
    private ConversationRepository $conversations;
    private ConversationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->conversations = new ConversationRepository();
        $this->service = new ConversationService($this->conversations);
    }

    public function index(): Response
    {
        $user = App::user();

        return Response::view('admin/messages/index', [
            'user' => $user,
            'conversations' => $this->conversations->listForUser((int) $user['id']),
            'unreadCount' => $this->conversations->unreadFor((int) $user['id']),
        ]);
    }

    public function show(string $publicId): Response
    {
        $user = App::user();
        $conversation = $this->conversations->findByPublicId($publicId);

        if ($conversation === null || !$this->conversations->isParticipant((int) $conversation['id'], (int) $user['id'])) {
            return Response::notFound('Cette conversation est introuvable ou inaccessible.');
        }

        $this->conversations->markRead((int) $conversation['id'], (int) $user['id']);

        $thread = $this->conversations->thread((int) $conversation['id']);

        return Response::view('admin/messages/show', [
            'user' => $user,
            'thread' => $thread,
            'conversation' => $thread['conversation'],
            'messages' => $thread['messages'],
            'participants' => $thread['participants'],
            'conversations' => $this->conversations->listForUser((int) $user['id']),
        ]);
    }

    public function store(string $publicId): Response
    {
        $user = App::user();
        $conversation = $this->conversations->findByPublicId($publicId);

        if ($conversation === null || !$this->conversations->isParticipant((int) $conversation['id'], (int) $user['id'])) {
            return Response::notFound('Cette conversation est introuvable ou inaccessible.');
        }

        $result = $this->service->send(
            (int) $conversation['id'],
            $user,
            (string) $this->request->post('body', '')
        );

        App::flash($result['error'] === null ? 'success' : 'error', $result['error'] ?? 'Message envoyé.');

        return Response::redirect(route('admin.messages.show', ['publicId' => $publicId]));
    }

    public function close(string $publicId): Response
    {
        $user = App::user();
        $conversation = $this->conversations->findByPublicId($publicId);

        if ($conversation === null || !$this->conversations->isParticipant((int) $conversation['id'], (int) $user['id'])) {
            return Response::notFound('Cette conversation est introuvable ou inaccessible.');
        }

        $this->service->close((int) $conversation['id'], $user);
        App::flash('success', 'Conversation archivée.');

        return Response::redirect(route('admin.messages'));
    }
}