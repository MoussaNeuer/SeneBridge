<?php
use App\Support\CSRF;
use App\Support\Router;
$this->layout('layouts/client');
$this->section('title'); ?>Conversation<?php $this->endSection();
$this->section('content');
$conv = $conversation;
?>

<div class="bg-white rounded-2xl border border-brand/10 flex flex-col min-h-[70vh]">
    <div class="px-5 py-4 border-b border-brand/10 flex items-center justify-between">
        <div>
            <h1 class="font-bold"><?= e($conv['title'] ?? 'Conversation') ?></h1>
            <p class="text-xs text-ink/60 mt-0.5">
                <?= $conv['project_reference'] ? 'Dossier ' . e($conv['project_reference']) . ' — ' : '' ?><?= e($conv['project_name'] ?? '') ?>
            </p>
        </div>
        <a href="<?= e(Router::url('client.messages')) ?>" class="text-xs text-brand hover:underline">Toutes les conversations →</a>
    </div>

    <div class="flex-1 px-5 py-4 space-y-4 overflow-y-auto max-h-[55vh]" id="thread">
        <?php if ($messages === []): ?>
            <p class="text-center text-sm text-ink/50 pt-6">Premier message : dites-nous ce dont vous avez besoin.</p>
        <?php endif; ?>
        <?php foreach ($messages as $message): ?>
        <?php $mine = (int) $message['sender_id'] === (int) $user['id']; ?>
        <div class="flex <?= $mine ? 'justify-end' : 'justify-start' ?>">
            <div class="max-w-[78%] px-4 py-2.5 rounded-2xl text-sm <?= $mine ? 'bg-brand text-brand-foreground' : 'bg-cream border border-brand/10' ?>">
                <?php if (!$mine): ?>
                    <span class="block text-[11px] font-semibold text-ink/50 mb-0.5">SeneBridge · Équipe</span>
                <?php endif; ?>
                <p class="whitespace-pre-wrap"><?= nl2br(e($message['body'])) ?></p>
                <span class="block text-[10px] mt-1 opacity-60"><?= e(date('d/m/Y H:i', strtotime($message['created_at']))) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <form method="POST" action="<?= e(Router::url('client.messages.store', ['publicId' => $conv['public_id']])) ?>" class="border-t border-brand/10 p-4 flex gap-3">
        <?= CSRF::field() ?>
        <textarea name="body" rows="2" required placeholder="Votre message…" class="flex-1 px-3 py-2 rounded-lg border border-brand/15 focus:outline-none focus:ring-2 focus:ring-brand/30"></textarea>
        <button type="submit" class="px-5 py-2 rounded-lg bg-brand text-brand-foreground text-sm font-semibold hover:bg-brand-dark transition self-end">Envoyer</button>
    </form>
</div>

<?php $this->endSection(); ?>